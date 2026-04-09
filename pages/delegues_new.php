<?php

declare(strict_types=1);

require_role('admin');

$success = '';
$error = '';

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
        return [false, 'Gouvernorat requis.', []];
    }

    $stmt = db()->prepare('SELECT id, name_fr FROM cities WHERE governorate_id = ?');
    $stmt->execute([$governorateId]);
    $cities = $stmt->fetchAll();
    $allIds = [];
    foreach ($cities as $city) {
        $allIds[] = (int) $city['id'];
    }

    $excludedCities = normalize_id_list($excludedCities, $allIds);
    $included = array_values(array_diff($allIds, $excludedCities));

    if (empty($allIds)) {
        return [false, 'Aucune delegation trouvee pour ce gouvernorat.', $excludedCities];
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
        return [false, 'Conflit de delegations detecte.', $excludedCities];
    }

    return [true, '', $excludedCities];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $governorateId = (int) ($_POST['governorate_id'] ?? 0);
    $excludedCities = array_map('intval', $_POST['excluded_city_ids'] ?? []);
    $excludedCities = array_values(array_filter($excludedCities));
    $active = isset($_POST['active']) ? 1 : 0;

    if ($name === '' || $email === '' || $password === '' || $governorateId <= 0) {
        $error = 'Nom, email, mot de passe et gouvernorat requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } else {
        try {
            [$ok, $msg, $filteredExcluded] = validate_delegation_overlap($governorateId, $excludedCities, null);
            if (!$ok) {
                $error = $msg;
            } else {
                $excludedJson = $filteredExcluded ? json_encode($filteredExcluded) : null;
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = db()->prepare('INSERT INTO users (name, email, password, role, phone, governorate_id, excluded_city_ids, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hash, 'rep', $phone ?: null, $governorateId, $excludedJson, $active]);
                $success = 'Delegue cree.';
            }
        } catch (Throwable $e) {
            $error = 'Impossible de creer le delegue (email deja utilise?).';
        }
    }
}

try {
    $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $governorates = [];
}

$page_title = 'Creer un delegue';
$show_back = true;
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
    <h2 class="text-base font-semibold text-slate-900">Creer un delegue</h2>
    <form method="post" class="mt-3 grid grid-cols-1 gap-3" data-delegue-zone data-conflict-form data-conflict-rep="0">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-medium text-slate-700">Nom</label>
        <input type="text" name="name" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Mot de passe</label>
        <input type="password" name="password" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Telephone</label>
        <input type="text" name="phone" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Gouvernorat</label>
        <select name="governorate_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" data-governorate-select required>
          <option value="">Choisir</option>
          <?php foreach ($governorates as $gov): ?>
            <option value="<?= (int) $gov['id'] ?>"><?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Delegations exclues</label>
        <select name="excluded_city_ids[]" multiple class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 h-40" data-excluded-select data-excluded-selected="[]"></select>
        <p class="text-xs text-slate-500 mt-1">Les delegations ne peuvent pas se chevaucher entre delegues.</p>
      </div>
      <div data-conflict-preview class="hidden text-xs rounded-xl border border-amber-200 bg-amber-50 text-amber-700 p-3"></div>
      <label class="inline-flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="active" checked>
        Actif
      </label>
      <button type="submit" class="h-12 rounded-xl bg-blue-600 text-white font-semibold active:scale-[0.98]" data-conflict-submit>Creer</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
