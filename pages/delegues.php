<?php

declare(strict_types=1);

require_role('admin');

$success = '';
$error = '';
$export = ((string) ($_GET['export'] ?? '')) === 'csv';
$dateFrom = (string) ($_GET['date_from'] ?? '');
$dateTo = (string) ($_GET['date_to'] ?? '');

function normalize_id_list(array $ids, array $allowed): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if ($allowed) {
        $allowedMap = array_flip($allowed);
        $ids = array_values(array_filter($ids, function ($id) use ($allowedMap) {
            return isset($allowedMap[$id]);
        }));
    }
    return $ids;
}

function validate_delegation_overlap(int $governorateId, array $excludedCities, ?int $currentRepId = null): array
{
    if ($governorateId <= 0) {
        return [false, 'Gouvernorat requis.', [], []];
    }

    $stmt = db()->prepare('SELECT id, name_fr FROM cities WHERE governorate_id = ?');
    $stmt->execute([$governorateId]);
    $cities = $stmt->fetchAll();
    $allIds = [];
    $nameMap = [];
    foreach ($cities as $city) {
        $id = (int) $city['id'];
        $allIds[] = $id;
        $nameMap[$id] = $city['name_fr'];
    }

    $excludedCities = normalize_id_list($excludedCities, $allIds);
    $included = array_values(array_diff($allIds, $excludedCities));

    if (empty($allIds)) {
        return [false, 'Aucune delegation trouvee pour ce gouvernorat.', $excludedCities, $nameMap];
    }

    $params = [$governorateId];
    $sql = "SELECT id, excluded_city_ids FROM users WHERE role = 'rep' AND governorate_id = ?";
    if ($currentRepId) {
        $sql .= ' AND id <> ?';
        $params[] = $currentRepId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $conflicts = [];
    foreach ($stmt->fetchAll() as $rep) {
        $otherExcluded = [];
        if (!empty($rep['excluded_city_ids'])) {
            $decoded = json_decode((string) $rep['excluded_city_ids'], true);
            if (is_array($decoded)) {
                $otherExcluded = normalize_id_list($decoded, $allIds);
            }
        }
        $otherIncluded = array_diff($allIds, $otherExcluded);
        $overlap = array_intersect($included, $otherIncluded);
        foreach ($overlap as $cityId) {
            $conflicts[$cityId] = true;
        }
    }

    if ($conflicts) {
        $names = [];
        foreach (array_keys($conflicts) as $cityId) {
            if (isset($nameMap[$cityId])) {
                $names[] = $nameMap[$cityId];
            }
            if (count($names) >= 5) {
                break;
            }
        }
        $suffix = count($conflicts) > 5 ? ' ...' : '';
        return [false, 'Conflit de delegations: ' . implode(', ', $names) . $suffix, $excludedCities, $nameMap];
    }

    return [true, '', $excludedCities, $nameMap];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'reset_password') {
        $repId = (int) ($_POST['rep_id'] ?? 0);
        if ($repId <= 0) {
            $error = 'Delegue invalide.';
        } else {
            try {
                $newPassword = substr(bin2hex(random_bytes(6)), 0, 10);
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'rep'");
                $stmt->execute([$hash, $repId]);
                if ($stmt->rowCount() > 0) {
                    $success = 'Mot de passe reinitialise: ' . $newPassword;
                } else {
                    $error = 'Delegue introuvable.';
                }
            } catch (Throwable $e) {
                $error = 'Erreur lors de la reinitialisation.';
            }
        }
    } elseif ($action === 'toggle_active') {
        $repId = (int) ($_POST['rep_id'] ?? 0);
        $newActive = isset($_POST['active']) ? (int) $_POST['active'] : 0;
        if ($repId <= 0) {
            $error = 'Delegue invalide.';
        } else {
            try {
                $stmt = db()->prepare("UPDATE users SET active = ? WHERE id = ? AND role = 'rep'");
                $stmt->execute([$newActive, $repId]);
                $success = $newActive ? 'Delegue active.' : 'Delegue desactive.';
            } catch (Throwable $e) {
                $error = 'Erreur lors de la mise a jour.';
            }
        }
    } elseif ($action === 'update_zone') {
        $repId = (int) ($_POST['rep_id'] ?? 0);
        $governorateId = (int) ($_POST['governorate_id'] ?? 0);
        $excludedCities = array_map('intval', $_POST['excluded_city_ids'] ?? []);

        if ($repId <= 0) {
            $error = 'Delegue invalide.';
        } else {
            try {
                [$ok, $msg, $filteredExcluded] = validate_delegation_overlap($governorateId, $excludedCities, $repId);
                if (!$ok) {
                    $error = $msg;
                } else {
                    $excludedJson = $filteredExcluded ? json_encode($filteredExcluded) : null;
                    $stmt = db()->prepare("UPDATE users SET governorate_id = ?, excluded_city_ids = ? WHERE id = ? AND role = 'rep'");
                    $stmt->execute([$governorateId, $excludedJson, $repId]);
                    $success = 'Zone mise a jour.';
                }
            } catch (Throwable $e) {
                $error = 'Erreur lors de la mise a jour de la zone.';
            }
        }
    } else {
        $error = 'Action invalide.';
    }
}

try {
    $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $governorates = [];
}

try {
    $reps = db()->query("SELECT u.id, u.name, u.email, u.phone, u.zone, u.active, u.created_at, u.governorate_id, u.excluded_city_ids, g.name_fr AS governorate_name
        FROM users u
        LEFT JOIN governorates g ON g.id = u.governorate_id
        WHERE u.role = 'rep'
        ORDER BY u.id DESC")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

$repStats = [];
$summary = [
    'total' => 0,
    'active' => 0,
    'visits_30d' => 0,
    'visits_total' => 0,
    'samples_total' => 0,
];

$visitDateWhere = '';
$visitDateParams = [];
if ($dateFrom !== '' && $dateTo !== '') {
    $visitDateWhere = ' WHERE created_at BETWEEN ? AND ?';
    $visitDateParams[] = $dateFrom . ' 00:00:00';
    $visitDateParams[] = $dateTo . ' 23:59:59';
} elseif ($dateFrom !== '') {
    $visitDateWhere = ' WHERE created_at >= ?';
    $visitDateParams[] = $dateFrom . ' 00:00:00';
} elseif ($dateTo !== '') {
    $visitDateWhere = ' WHERE created_at <= ?';
    $visitDateParams[] = $dateTo . ' 23:59:59';
}

try {
    $summary['total'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'rep'")->fetchColumn();
    $summary['active'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'rep' AND active = 1")->fetchColumn();
} catch (Throwable $e) {
}

try {
    $stmt = db()->prepare("SELECT user_id,
        COUNT(*) AS visits_total,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS visits_30d,
        MAX(created_at) AS last_visit
        FROM visits$visitDateWhere GROUP BY user_id");
    $stmt->execute($visitDateParams);
    foreach ($stmt->fetchAll() as $row) {
        $repStats[(int) $row['user_id']]['visits_total'] = (int) $row['visits_total'];
        $repStats[(int) $row['user_id']]['visits_30d'] = (int) $row['visits_30d'];
        $repStats[(int) $row['user_id']]['last_visit'] = $row['last_visit'];
        $summary['visits_total'] += (int) $row['visits_total'];
        $summary['visits_30d'] += (int) $row['visits_30d'];
    }
} catch (Throwable $e) {
}

try {
    $samplesDateWhere = '';
    $samplesDateParams = [];
    if ($dateFrom !== '' && $dateTo !== '') {
        $samplesDateWhere = ' WHERE v.created_at BETWEEN ? AND ?';
        $samplesDateParams[] = $dateFrom . ' 00:00:00';
        $samplesDateParams[] = $dateTo . ' 23:59:59';
    } elseif ($dateFrom !== '') {
        $samplesDateWhere = ' WHERE v.created_at >= ?';
        $samplesDateParams[] = $dateFrom . ' 00:00:00';
    } elseif ($dateTo !== '') {
        $samplesDateWhere = ' WHERE v.created_at <= ?';
        $samplesDateParams[] = $dateTo . ' 23:59:59';
    }
    $stmt = db()->prepare("SELECT v.user_id, SUM(vs.quantity) AS samples_total
        FROM visit_samples vs
        JOIN visits v ON v.id = vs.visit_id
        $samplesDateWhere
        GROUP BY v.user_id");
    $stmt->execute($samplesDateParams);
    foreach ($stmt->fetchAll() as $row) {
        $repStats[(int) $row['user_id']]['samples_total'] = (int) $row['samples_total'];
        $summary['samples_total'] += (int) $row['samples_total'];
    }
} catch (Throwable $e) {
}

try {
    $stmt = db()->query("SELECT assigned_rep_id AS user_id, COUNT(*) AS contacts_total
        FROM contacts
        WHERE assigned_rep_id IS NOT NULL
        GROUP BY assigned_rep_id");
    foreach ($stmt->fetchAll() as $row) {
        $repStats[(int) $row['user_id']]['contacts_total'] = (int) $row['contacts_total'];
    }
} catch (Throwable $e) {
}

try {
    $stmt = db()->query("SELECT user_id, SUM(quantity) AS stock_total
        FROM stock
        GROUP BY user_id");
    foreach ($stmt->fetchAll() as $row) {
        $repStats[(int) $row['user_id']]['stock_total'] = (int) $row['stock_total'];
    }
} catch (Throwable $e) {
}

$maxVisits30 = 1;
$maxStock = 1;
foreach ($reps as $rep) {
    $stats = $repStats[(int) $rep['id']] ?? [];
    $maxVisits30 = max($maxVisits30, (int) ($stats['visits_30d'] ?? 0));
    $maxStock = max($maxStock, (int) ($stats['stock_total'] ?? 0));
}

$repTrends = [];
$trendWeeks = [];
$trendMax = 1;
$weekStart = new DateTime('monday this week');
for ($i = 5; $i >= 0; $i--) {
    $start = (clone $weekStart)->modify('-' . $i . ' week');
    $yearWeek = (int) $start->format('oW');
    $trendWeeks[] = [
        'label' => $start->format('d/m'),
        'yearweek' => $yearWeek,
        'start' => $start->format('Y-m-d'),
    ];
}
$trendStart = $trendWeeks[0]['start'] . ' 00:00:00';
$trendEnd = (clone $weekStart)->modify('+1 week')->format('Y-m-d') . ' 00:00:00';
try {
    $stmt = db()->prepare("SELECT v.user_id, YEARWEEK(v.created_at, 3) AS yw, SUM(vs.quantity) AS qty
        FROM visit_samples vs
        JOIN visits v ON v.id = vs.visit_id
        WHERE v.created_at >= ? AND v.created_at < ?
        GROUP BY v.user_id, yw");
    $stmt->execute([$trendStart, $trendEnd]);
    foreach ($stmt->fetchAll() as $row) {
        $uid = (int) $row['user_id'];
        $yw = (int) $row['yw'];
        $qty = (int) $row['qty'];
        if (!isset($repTrends[$uid])) {
            $repTrends[$uid] = [];
        }
        $repTrends[$uid][$yw] = $qty;
        $trendMax = max($trendMax, $qty);
    }
} catch (Throwable $e) {
}

if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=delegues_kpi.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nom', 'Email', 'Actif', 'Visites 30j', 'Visites total', 'Echantillons', 'Contacts', 'Stock', 'Derniere visite']);
    foreach ($reps as $rep) {
        $stats = $repStats[(int) $rep['id']] ?? [];
        fputcsv($out, [
            $rep['id'],
            $rep['name'],
            $rep['email'],
            (int) $rep['active'],
            (int) ($stats['visits_30d'] ?? 0),
            (int) ($stats['visits_total'] ?? 0),
            (int) ($stats['samples_total'] ?? 0),
            (int) ($stats['contacts_total'] ?? 0),
            (int) ($stats['stock_total'] ?? 0),
            $stats['last_visit'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$page_title = 'Delegues';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <?php if ($success !== ''): ?>
    <div class="bg-green-50 text-green-700 rounded-2xl p-4 text-sm"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="bg-red-50 text-red-700 rounded-2xl p-4 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-slate-900">KPIs delegues</h2>
      <a href="/delegues/new" class="text-xs text-blue-600">Creer un delegue</a>
      <a href="/delegues/map" class="text-xs text-blue-600">Carte des zones</a>
      <?php
      $exportUrl = '/delegues?export=csv';
      if ($dateFrom !== '') {
          $exportUrl .= '&date_from=' . urlencode($dateFrom);
      }
      if ($dateTo !== '') {
          $exportUrl .= '&date_to=' . urlencode($dateTo);
      }
    ?>
    <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-xs text-blue-600">Exporter KPI CSV</a>
    </div>
    <form method="get" class="mt-3 grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-slate-500">Du</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-10 rounded-xl border border-slate-200 px-3 text-sm">
      </div>
      <div>
        <label class="block text-xs text-slate-500">Au</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-10 rounded-xl border border-slate-200 px-3 text-sm">
      </div>
      <button type="submit" class="col-span-2 h-10 rounded-xl bg-slate-900 text-white text-sm">Appliquer</button>
    </form>
    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
      <div class="rounded-xl border border-slate-100 p-3">
        <div class="text-xs text-slate-500">Delegues actifs</div>
        <div class="text-lg font-semibold"><?= (int) $summary['active'] ?> / <?= (int) $summary['total'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-3">
        <div class="text-xs text-slate-500">Visites (30j)</div>
        <div class="text-lg font-semibold"><?= (int) $summary['visits_30d'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-3">
        <div class="text-xs text-slate-500">Visites totales</div>
        <div class="text-lg font-semibold"><?= (int) $summary['visits_total'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-3">
        <div class="text-xs text-slate-500">Echantillons donnes</div>
        <div class="text-lg font-semibold"><?= (int) $summary['samples_total'] ?></div>
      </div>
    </div>
  </div>

<div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Liste des delegues</h2>
    <p class="text-xs text-slate-500 mt-1">Stats: visites, echantillons, contacts, stock, derniere visite.</p>
    <?php if (empty($reps)): ?>
      <p class="text-sm text-slate-600 mt-3">Aucun delegue.</p>
    <?php else: ?>
      <div class="mt-3 space-y-3">
        <?php foreach ($reps as $rep): ?>
          <?php
            $stats = $repStats[(int) $rep['id']] ?? [];
            $visits30 = (int) ($stats['visits_30d'] ?? 0);
            $visitsTotal = (int) ($stats['visits_total'] ?? 0);
            $samplesTotal = (int) ($stats['samples_total'] ?? 0);
            $contactsTotal = (int) ($stats['contacts_total'] ?? 0);
            $stockTotal = (int) ($stats['stock_total'] ?? 0);
            $lastVisit = $stats['last_visit'] ?? null;
            $visitsUrl = '/visits?rep_id=' . (int) $rep['id'];
          ?>
          <div class="border border-slate-100 rounded-xl px-3 py-3 space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($rep['email'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="text-xs text-slate-400">Zone: <?= htmlspecialchars($rep['governorate_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?><?php $excludedCount = 0; if (!empty($rep['excluded_city_ids'])) { $excludedIds = json_decode((string) $rep['excluded_city_ids'], true); if (is_array($excludedIds)) { $excludedCount = count($excludedIds); } } ?><?= $excludedCount ? ' (exclusions: ' . $excludedCount . ')' : '' ?></div>
              </div>
              <div class="text-xs text-slate-500 text-right">
                <div><?= htmlspecialchars($rep['phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                <div><?= (int) $rep['active'] === 1 ? 'Actif' : 'Inactif' ?></div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs text-slate-600">
              <div class="rounded-lg border border-slate-100 p-2">Visites 30j: <span class="font-semibold"><?= $visits30 ?></span></div>
              <div class="rounded-lg border border-slate-100 p-2">Visites total: <span class="font-semibold"><?= $visitsTotal ?></span></div>
              <div class="rounded-lg border border-slate-100 p-2">Echantillons: <span class="font-semibold"><?= $samplesTotal ?></span></div>
              <div class="rounded-lg border border-slate-100 p-2">Contacts: <span class="font-semibold"><?= $contactsTotal ?></span></div>
              <div class="rounded-lg border border-slate-100 p-2">Stock: <span class="font-semibold"><?= $stockTotal ?></span></div>
              <div class="rounded-lg border border-slate-100 p-2">Derniere visite: <span class="font-semibold"><?= $lastVisit ? htmlspecialchars($lastVisit, ENT_QUOTES, 'UTF-8') : '-' ?></span></div>
            </div>
            <div class="space-y-2">
              <div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>Tendance echantillons (6 sem.)</span>
                </div>
                <div class="flex items-end gap-1 h-10 mt-1">
                  <?php foreach ($trendWeeks as $week): ?>
                    <?php $val = (int) (($repTrends[(int) $rep['id']][$week['yearweek']] ?? 0)); ?>
                    <div class="flex-1 flex flex-col items-center">
                      <div class="w-full rounded bg-emerald-500" style="height: <?= (int) max(2, round(($val / max(1, $trendMax)) * 40)) ?>px"></div>
                      <div class="text-[10px] text-slate-400 mt-1"><?= $week['label'] ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>Visites 30j</span>
                  <span><?= $visits30 ?></span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                  <div class="h-full bg-blue-500" style="width: <?= (int) round(($visits30 / max(1, $maxVisits30)) * 100) ?>%"></div>
                </div>
              </div>
              <div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>Stock</span>
                  <span><?= $stockTotal ?></span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                  <div class="h-full bg-amber-500" style="width: <?= (int) round(($stockTotal / max(1, $maxStock)) * 100) ?>%"></div>
                </div>
              </div>
            </div>
            <details class="rounded-xl border border-slate-100 p-2">
              <summary class="text-xs text-slate-600 cursor-pointer">Modifier zone / exclusions</summary>
              <form method="post" class="mt-3 grid grid-cols-1 gap-2" data-delegue-zone data-conflict-form data-conflict-rep="<?= (int) $rep['id'] ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_zone">
                <input type="hidden" name="rep_id" value="<?= (int) $rep['id'] ?>">
                <div>
                  <label class="block text-xs text-slate-500">Gouvernorat</label>
                  <select name="governorate_id" class="mt-1 w-full h-10 rounded-xl border border-slate-200 px-3 text-sm" data-governorate-select required>
                    <option value="">Choisir</option>
                    <?php foreach ($governorates as $gov): ?>
                      <option value="<?= (int) $gov['id'] ?>" <?= (int) $rep['governorate_id'] === (int) $gov['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php
                  $excludedIds = [];
                  if (!empty($rep['excluded_city_ids'])) {
                      $decoded = json_decode((string) $rep['excluded_city_ids'], true);
                      if (is_array($decoded)) {
                          $excludedIds = array_map('intval', $decoded);
                      }
                  }
                ?>
                <div>
                  <label class="block text-xs text-slate-500">Delegations exclues</label>
                  <select name="excluded_city_ids[]" multiple class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 h-32 text-sm" data-excluded-select data-excluded-selected="<?= htmlspecialchars(json_encode($excludedIds), ENT_QUOTES, 'UTF-8') ?>">
                  </select>
                </div>
                <div data-conflict-preview class="hidden text-xs rounded-xl border border-amber-200 bg-amber-50 text-amber-700 p-3"></div>
                <button type="submit" class="h-9 rounded-xl bg-slate-900 text-white text-xs" data-conflict-submit>Sauvegarder zone</button>
              </form>
            </details>
            <div class="grid grid-cols-1 gap-2">
              <form method="post" class="flex items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="rep_id" value="<?= (int) $rep['id'] ?>">
                <button type="submit" class="h-9 px-3 rounded-xl border border-slate-200 text-xs">Reset password</button>
              </form>
              <form method="post" class="flex items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="rep_id" value="<?= (int) $rep['id'] ?>">
                <input type="hidden" name="active" value="<?= (int) $rep['active'] === 1 ? 0 : 1 ?>">
                <button type="submit" class="h-9 px-3 rounded-xl border border-slate-200 text-xs">
                  <?= (int) $rep['active'] === 1 ? 'Desactiver' : 'Activer' ?>
                </button>
              </form>
            </div>
            <div class="flex gap-3 text-xs">
              <a href="<?= htmlspecialchars($visitsUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-blue-600">Voir visites</a>
              <a href="/stock/history" class="text-blue-600">Mouvements stock</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
