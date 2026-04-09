<?php

declare(strict_types=1);

require_role('admin');

$success = '';
$error = '';
$export = ((string) ($_GET['export'] ?? '')) === 'csv';

// Default to last 30 days
$dateFrom = (string) ($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo = (string) ($_GET['date_to'] ?? date('Y-m-d'));

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

function validate_delegation_overlap(array $governorateIds, array $excludedCities, ?int $currentRepId = null): array
{
    if (empty($governorateIds)) {
        return [false, 'Gouvernorat requis.', [], []];
    }

    $allCityIds = [];
    $nameMap = [];
    
    // Get all cities from selected governorates
    $placeholders = str_repeat('?,', count($governorateIds));
    $placeholders = rtrim($placeholders, ',');
    $stmt = db()->prepare("SELECT id, name_fr FROM cities WHERE governorate_id IN ($placeholders)");
    $stmt->execute($governorateIds);
    $cities = $stmt->fetchAll();
    
    foreach ($cities as $city) {
        $id = (int) $city['id'];
        $allCityIds[] = $id;
        $nameMap[$id] = $city['name_fr'];
    }

    $excludedCities = normalize_id_list($excludedCities, $allCityIds);
    $included = array_values(array_diff($allCityIds, $excludedCities));

    if (empty($allCityIds)) {
        return [false, 'Aucune delegation trouvee pour ce gouvernorat.', $excludedCities, $nameMap];
    }

    $params = array_merge($governorateIds);
    $sql = "SELECT id, excluded_city_ids FROM users WHERE role = 'rep' AND governorate_id IN ($placeholders)";
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
                $otherExcluded = normalize_id_list($decoded, $allCityIds);
            }
        }
        $otherIncluded = array_diff($allCityIds, $otherExcluded);
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

// Handle POST requests
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
        $governorateIds = array_map('intval', $_POST['governorate_ids'] ?? []);
        $excludedCities = array_map('intval', $_POST['excluded_city_ids'] ?? []);
        
        if ($repId <= 0) {
            $error = 'Delegue invalide.';
        } else {
            try {
                [$ok, $msg, $filteredExcluded] = validate_delegation_overlap($governorateIds, $excludedCities, $repId);
                if (!$ok) {
                    $error = $msg;
                } else {
                    $excludedJson = $filteredExcluded ? json_encode($filteredExcluded) : null;
                    $firstGov = $governorateIds[0] ?? 0;
                    
                    // Store governorate IDs as JSON for multiple support
                    $governorateJson = json_encode($governorateIds);
                    
                    $stmt = db()->prepare("UPDATE users SET governorate_id = ?, governorate_ids = ?, excluded_city_ids = ? WHERE id = ? AND role = 'rep'");
                    $stmt->execute([$firstGov, $governorateJson, $excludedJson, $repId]);
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

// Fetch data
try {
    $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $governorates = [];
}

try {
    // Try to query with new columns
    $reps = db()->query("SELECT u.id, u.name, u.email, u.phone, u.zone, u.active, u.created_at, u.governorate_id, u.governorate_ids, u.excluded_city_ids, g.name_fr AS governorate_name
        FROM users u
        LEFT JOIN governorates g ON g.id = u.governorate_id
        WHERE u.role = 'rep'
        ORDER BY u.id DESC")->fetchAll();
} catch (Throwable $e) {
    // Fallback to old columns if new columns don't exist
    try {
        $reps = db()->query("SELECT u.id, u.name, u.email, u.phone, u.zone, u.active, u.created_at, u.governorate_id, NULL as governorate_ids, NULL as excluded_city_ids, g.name_fr AS governorate_name
            FROM users u
            LEFT JOIN governorates g ON g.id = u.governorate_id
            WHERE u.role = 'rep'
            ORDER BY u.id DESC")->fetchAll();
    } catch (Throwable $e2) {
        $reps = [];
    }
}

// Calculate stats for last 30 days
$repStats = [];
$summary = [
    'total' => 0,
    'active' => 0,
    'visits_30d' => 0,
    'visits_total' => 0,
    'samples_30d' => 0,
    'samples_total' => 0,
];

$visitDateFrom = date('Y-m-d H:i:s', strtotime($dateFrom . ' 00:00:00'));
$visitDateTo = date('Y-m-d H:i:s', strtotime($dateTo . ' 23:59:59'));

try {
    $summary['total'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'rep'")->fetchColumn();
    $summary['active'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'rep' AND active = 1")->fetchColumn();
} catch (Throwable $e) {
}

// Visits
try {
    $stmt = db()->prepare("SELECT user_id,
        COUNT(*) AS visits_total,
        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS visits_30d,
        MAX(created_at) AS last_visit
        FROM visits
        WHERE created_at BETWEEN ? AND ?
        GROUP BY user_id");
    $stmt->execute([$visitDateFrom, $visitDateFrom, $visitDateTo]);
    foreach ($stmt->fetchAll() as $row) {
        $uid = (int) $row['user_id'];
        $repStats[$uid]['visits_total'] = (int) $row['visits_total'];
        $repStats[$uid]['visits_30d'] = (int) $row['visits_30d'];
        $repStats[$uid]['last_visit'] = $row['last_visit'];
        $summary['visits_total'] += (int) $row['visits_total'];
        $summary['visits_30d'] += (int) $row['visits_30d'];
    }
} catch (Throwable $e) {
}

// Samples
try {
    $stmt = db()->prepare("SELECT v.user_id, 
        SUM(CASE WHEN v.created_at >= ? THEN vs.quantity ELSE 0 END) AS samples_30d,
        SUM(vs.quantity) AS samples_total
        FROM visit_samples vs
        JOIN visits v ON v.id = vs.visit_id
        WHERE v.created_at BETWEEN ? AND ?
        GROUP BY v.user_id");
    $stmt->execute([$visitDateFrom, $visitDateFrom, $visitDateTo]);
    foreach ($stmt->fetchAll() as $row) {
        $uid = (int) $row['user_id'];
        $repStats[$uid]['samples_30d'] = (int) $row['samples_30d'];
        $repStats[$uid]['samples_total'] = (int) $row['samples_total'];
        $summary['samples_30d'] += (int) $row['samples_30d'];
        $summary['samples_total'] += (int) $row['samples_total'];
    }
} catch (Throwable $e) {
}

// Contacts
try {
    $stmt = db()->prepare("SELECT assigned_rep_id AS user_id, COUNT(*) AS contacts_total
        FROM contacts
        WHERE assigned_rep_id IS NOT NULL
        GROUP BY assigned_rep_id");
    foreach ($stmt->fetchAll() as $row) {
        $repStats[(int) $row['user_id']]['contacts_total'] = (int) $row['contacts_total'];
    }
} catch (Throwable $e) {
}

// Stock
try {
    $stmt = db()->query("SELECT user_id, SUM(quantity) AS stock_total
        FROM stock
        GROUP BY user_id");
    foreach ($stmt->fetchAll() as $row) {
        $repStats[(int) $row['user_id']]['stock_total'] = (int) $row['stock_total'];
    }
} catch (Throwable $e) {
}

// Calculate max values for progress bars
$maxVisits30 = 1;
$maxSamples30 = 1;
$maxStock = 1;
foreach ($reps as $rep) {
    $stats = $repStats[(int) $rep['id']] ?? [];
    $maxVisits30 = max($maxVisits30, (int) ($stats['visits_30d'] ?? 0));
    $maxSamples30 = max($maxSamples30, (int) ($stats['samples_30d'] ?? 0));
    $maxStock = max($maxStock, (int) ($stats['stock_total'] ?? 0));
}

// CSV Export
if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=delegues_kpi.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nom', 'Email', 'Actif', 'Visites 30j', 'Visites total', 'Echantillons 30j', 'Echantillons total', 'Contacts', 'Stock', 'Derniere visite']);
    foreach ($reps as $rep) {
        $stats = $repStats[(int) $rep['id']] ?? [];
        fputcsv($out, [
            $rep['id'],
            $rep['name'],
            $rep['email'],
            (int) $rep['active'],
            (int) ($stats['visits_30d'] ?? 0),
            (int) ($stats['visits_total'] ?? 0),
            (int) ($stats['samples_30d'] ?? 0),
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
    <div class="p-3 rounded-xl bg-green-50 text-green-700 text-sm">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="p-3 rounded-xl bg-red-50 text-red-700 text-sm">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <!-- KPIs Section -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-semibold text-slate-900">KPIs delegues (30 derniers jours)</h2>
      <div class="flex gap-2">
        <a href="/delegues/new" class="text-xs text-blue-600">Creer un delegue</a>
        <a href="/delegues/map" class="text-xs text-blue-600">Carte des zones</a>
        <a href="/delegues?export=csv&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="text-xs text-blue-600">Exporter KPI CSV</a>
      </div>
    </div>
    
    <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div>
        <label class="block text-xs text-slate-500">Du</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
      </div>
      <div>
        <label class="block text-xs text-slate-500">Au</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
      </div>
      <div class="md:col-span-2">
        <button type="submit" class="h-10 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700 transition-colors">
          Appliquer
        </button>
      </div>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Delegues actifs</div>
        <div class="text-2xl font-bold text-slate-900"><?= (int) $summary['active'] ?> / <?= (int) $summary['total'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Visites (30j)</div>
        <div class="text-2xl font-bold text-blue-600"><?= (int) $summary['visits_30d'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Echantillons (30j)</div>
        <div class="text-2xl font-bold text-green-600"><?= (int) $summary['samples_30d'] ?></div>
      </div>
      <div class="rounded-xl border border-slate-100 p-4">
        <div class="text-xs text-slate-500">Delegues inactifs</div>
        <div class="text-2xl font-bold text-red-600"><?= (int) $summary['total'] - (int) $summary['active'] ?></div>
      </div>
    </div>
  </div>

  <!-- Delegues List Section -->
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900 mb-4">Liste des delegues</h2>
    
    <?php if (empty($reps)): ?>
      <p class="text-sm text-slate-600 text-center py-8">Aucun delegue.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($reps as $rep): ?>
          <?php
            $stats = $repStats[(int) $rep['id']] ?? [];
            $visits30 = (int) ($stats['visits_30d'] ?? 0);
            $visitsTotal = (int) ($stats['visits_total'] ?? 0);
            $samples30 = (int) ($stats['samples_30d'] ?? 0);
            $samplesTotal = (int) ($stats['samples_total'] ?? 0);
            $contactsTotal = (int) ($stats['contacts_total'] ?? 0);
            $stockTotal = (int) ($stats['stock_total'] ?? 0);
            $lastVisit = $stats['last_visit'] ?? null;
            
            // Parse governorate IDs
            $governorateIds = [];
            if (!empty($rep['governorate_ids'])) {
                $decoded = json_decode((string) $rep['governorate_ids'], true);
                if (is_array($decoded)) {
                    $governorateIds = array_map('intval', $decoded);
                }
            }
            if (empty($governorateIds) && $rep['governorate_id']) {
                $governorateIds = [(int) $rep['governorate_id']];
            }
            
            // Get governorate names
            $govNames = [];
            foreach ($governorateIds as $govId) {
                foreach ($governorates as $gov) {
                    if ((int) $gov['id'] === $govId) {
                        $govNames[] = $gov['name_fr'];
                        break;
                    }
                }
            }
            
            // Parse excluded cities
            $excludedIds = [];
            if (!empty($rep['excluded_city_ids'])) {
                $decoded = json_decode((string) $rep['excluded_city_ids'], true);
                if (is_array($decoded)) {
                    $excludedIds = array_map('intval', $decoded);
                }
            }
          ?>
          <div class="border border-slate-200 rounded-xl p-4 space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
              <div>
                <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($rep['email'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-400">
                  Zone: <?= !empty($govNames) ? htmlspecialchars(implode(', ', $govNames), ENT_QUOTES, 'UTF-8') : '-' ?>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                  <?= (int) $rep['active'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                  <?= (int) $rep['active'] === 1 ? 'Actif' : 'Inactif' ?>
                </span>
              </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <div class="rounded-lg border border-slate-100 p-3">
                <div class="text-xs text-slate-500 mb-1">Visites 30j</div>
                <div class="flex items-center gap-2">
                  <div class="text-xl font-bold text-slate-900"><?= $visits30 ?></div>
                  <?php if ($visitsTotal > 0): ?>
                    <div class="text-xs text-slate-400">/ <?= $visitsTotal ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="rounded-lg border border-slate-100 p-3">
                <div class="text-xs text-slate-500 mb-1">Echantillons 30j</div>
                <div class="flex items-center gap-2">
                  <div class="text-xl font-bold text-slate-900"><?= $samples30 ?></div>
                  <?php if ($samplesTotal > 0): ?>
                    <div class="text-xs text-slate-400">/ <?= $samplesTotal ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="rounded-lg border border-slate-100 p-3">
                <div class="text-xs text-slate-500 mb-1">Contacts</div>
                <div class="text-xl font-bold text-slate-900"><?= $contactsTotal ?></div>
              </div>
              <div class="rounded-lg border border-slate-100 p-3">
                <div class="text-xs text-slate-500 mb-1">Stock</div>
                <div class="text-xl font-bold text-slate-900"><?= $stockTotal ?></div>
              </div>
            </div>

            <!-- Last Visit -->
            <div class="flex items-center justify-between text-xs text-slate-500 bg-slate-50 rounded-lg p-3">
              <span>Derniere visite</span>
              <span class="font-medium text-slate-900">
                <?= $lastVisit ? htmlspecialchars($lastVisit, ENT_QUOTES, 'UTF-8') : '-' ?>
              </span>
            </div>

            <!-- Zone/Exclusions Edit -->
            <details class="rounded-xl border border-slate-200">
              <summary class="text-sm font-medium text-slate-700 cursor-pointer px-4 py-3 hover:bg-slate-50">
                Modifier zone / exclusions
              </summary>
              <form method="post" class="p-4 space-y-4" data-delegue-zone data-conflict-form data-conflict-rep="<?= (int) $rep['id'] ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_zone">
                <input type="hidden" name="rep_id" value="<?= (int) $rep['id'] ?>">
                
                <!-- Governorates (Multiple) -->
                <div>
                  <label class="block text-sm font-medium text-slate-700">Gouvernorats</label>
                  <select name="governorate_ids[]" multiple class="mt-2 w-full h-32 rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                    <?php foreach ($governorates as $gov): ?>
                      <option value="<?= (int) $gov['id'] ?>" <?= in_array((int) $gov['id'], $governorateIds) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Excluded Cities (Live Search) -->
                <div>
                  <label class="block text-sm font-medium text-slate-700">Delegations exclues</label>
                  <input type="text" 
                         class="mt-2 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm" 
                         placeholder="Rechercher une delegation..."
                         data-city-search
                         autocomplete="off">
                  <select name="excluded_city_ids[]" multiple class="mt-2 w-full h-40 rounded-lg border border-slate-200 px-3 py-2 text-sm" data-excluded-select data-rep-id="<?= (int) $rep['id'] ?>">
                    <?php
                    // Get all cities from selected governorates
                    $placeholder = str_repeat('?,', count($governorateIds));
                    $placeholder = rtrim($placeholder, ',');
                    if (!empty($governorateIds)) {
                        $stmt = db()->prepare("SELECT id, name_fr FROM cities WHERE governorate_id IN ($placeholder) ORDER BY name_fr");
                        $stmt->execute($governorateIds);
                        $allCities = $stmt->fetchAll();
                    } else {
                        $allCities = [];
                    }
                    ?>
                    <?php foreach ($allCities as $city): ?>
                      <option value="<?= (int) $city['id'] ?>" <?= in_array((int) $city['id'], $excludedIds) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($city['name_fr'], ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div data-conflict-preview class="hidden text-xs rounded-lg border border-amber-200 bg-amber-50 text-amber-700 p-3"></div>
                <button type="submit" class="w-full h-10 rounded-lg bg-slate-900 text-white text-sm hover:bg-slate-800 transition-colors" data-conflict-submit>
                  Sauvegarder zone
                </button>
              </form>
            </details>

            <!-- Actions -->
            <div class="grid grid-cols-2 gap-2">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="rep_id" value="<?= (int) $rep['id'] ?>">
                <button type="submit" class="w-full h-9 rounded-lg border border-slate-200 text-xs hover:bg-slate-50 transition-colors">
                  Reset password
                </button>
              </form>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="rep_id" value="<?= (int) $rep['id'] ?>">
                <input type="hidden" name="active" value="<?= (int) $rep['active'] === 1 ? 0 : 1 ?>">
                <button type="submit" class="w-full h-9 rounded-lg border border-slate-200 text-xs hover:bg-slate-50 transition-colors">
                  <?= (int) $rep['active'] === 1 ? 'Desactiver' : 'Activer' ?>
                </button>
              </form>
            </div>

            <!-- Quick Links -->
            <div class="flex gap-2 text-xs">
              <a href="/visits?rep_id=<?= (int) $rep['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">Voir visites</a>
              <a href="/stock/history?rep_id=<?= (int) $rep['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">Mouvements stock</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live search for excluded cities
    const searchInputs = document.querySelectorAll('[data-city-search]');
    searchInputs.forEach(input => {
        const select = input.closest('form').querySelector('[data-excluded-select]');
        const repId = select.dataset.repId;
        const originalOptions = Array.from(select.options);
        
        input.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            
            // Clear current options
            select.innerHTML = '';
            
            // Filter and add matching options
            const filtered = originalOptions.filter(option => {
                const text = option.textContent.toLowerCase();
                return text.includes(query);
            });
            
            filtered.forEach(option => select.appendChild(option.cloneNode(true)));
        });
    });

    // Conflict form handling
    const zoneForms = document.querySelectorAll('[data-delegue-zone]');
    zoneForms.forEach(form => {
        const repId = form.dataset.conflictRep;
        const preview = form.querySelector('[data-conflict-preview]');
        const submitBtn = form.querySelector('[data-conflict-submit]');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            preview.innerHTML = 'Verification en cours...';
            preview.classList.remove('hidden');
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('/api/check_zone_conflict?' + params, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.conflict) {
                    preview.innerHTML = '⚠️ ' + result.message;
                    preview.classList.add('border-red-300', 'bg-red-50', 'text-red-700');
                    preview.classList.remove('border-amber-200', 'bg-amber-50', 'text-amber-700');
                } else {
                    preview.innerHTML = '✓ ' + result.message || 'Zone disponible';
                    preview.classList.add('border-green-300', 'bg-green-50', 'text-green-700');
                    preview.classList.remove('border-amber-200', 'bg-amber-50', 'text-amber-700', 'border-red-300', 'bg-red-50', 'text-red-700');
                    
                    // Submit after short delay
                    setTimeout(() => form.submit(), 500);
                }
                
                submitBtn.disabled = false;
            } catch (error) {
                preview.innerHTML = 'Erreur de verification';
                preview.classList.add('border-red-300', 'bg-red-50', 'text-red-700');
                submitBtn.disabled = false;
            }
        });
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>