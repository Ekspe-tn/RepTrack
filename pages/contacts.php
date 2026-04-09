<?php

declare(strict_types=1);

$success = '';
$error = '';

$filterGov = (int) ($_GET['gov'] ?? 0);
$filterRep = (int) ($_GET['rep'] ?? 0);
$filterType = (string) ($_GET['type'] ?? '');
$filterPotential = (string) ($_GET['potential'] ?? '');
$search = trim((string) ($_GET['q'] ?? ''));

try {
    $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $governorates = [];
}

try {
    $reps = db()->query("SELECT id, name FROM users WHERE role = 'rep' AND active = 1 ORDER BY name")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'quick_update') {
        $contactId = (int) ($_POST['contact_id'] ?? 0);
        $potential = (string) ($_POST['potential'] ?? 'B');
        $active = isset($_POST['active']) ? 1 : 0;

        if ($contactId > 0 && in_array($potential, ['A', 'B', 'C'], true)) {
            try {
                $stmt = db()->prepare('UPDATE contacts SET potential = ?, active = ? WHERE id = ?');
                $stmt->execute([$potential, $active, $contactId]);
                $success = 'Contact mis a jour.';
            } catch (Throwable $e) {
                $error = 'Impossible de mettre a jour le contact.';
            }
        } else {
            $error = 'Donnees invalides.';
        }
    }
}

$conditions = [];
$params = [];

if ($filterGov > 0) {
    $conditions[] = 'c.governorate_id = ?';
    $params[] = $filterGov;
}
if ($filterRep > 0) {
    $conditions[] = 'c.assigned_rep_id = ?';
    $params[] = $filterRep;
}
if ($filterType !== '') {
    $conditions[] = 'c.type = ?';
    $params[] = $filterType;
}
if ($filterPotential !== '') {
    $conditions[] = 'c.potential = ?';
    $params[] = $filterPotential;
}
if ($search !== '') {
    $conditions[] = '(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

try {
    $stmt = db()->prepare("SELECT c.id, c.name, c.type, c.phone, c.active, c.potential, c.assigned_rep_id, c.latitude, c.longitude, u.name AS rep_name, g.name_fr AS governorate_name, ci.name_fr AS city_name
        FROM contacts c
        LEFT JOIN users u ON u.id = c.assigned_rep_id
        JOIN governorates g ON g.id = c.governorate_id
        JOIN cities ci ON ci.id = c.city_id
        $whereSql
        ORDER BY c.id DESC
        LIMIT 100");
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();
} catch (Throwable $e) {
    $contacts = [];
}

$page_title = 'Contacts';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold text-slate-900">Contacts</h1>
    <a href="/contacts_new" class="h-10 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
      + Nouveau contact
    </a>
  </div>

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

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <form method="get" class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm" placeholder="Rechercher...">
        </div>
        <div>
          <select name="gov" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
            <option value="">Tous gouvernorats</option>
            <?php foreach ($governorates as $gov): ?>
              <option value="<?= (int) $gov['id'] ?>" <?= $filterGov === (int) $gov['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <button type="submit" class="w-full h-10 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
            Filtrer
          </button>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3">
        <div>
          <select name="type" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
            <option value="">Tous types</option>
            <?php foreach (['doctor' => 'Medecin', 'pharmacy' => 'Pharmacie', 'parapharmacie' => 'Parapharmacie', 'depot' => 'Depot', 'clinic' => 'Clinique', 'hospital' => 'Hopital'] as $value => $label): ?>
              <option value="<?= $value ?>" <?= $filterType === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <select name="potential" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
            <option value="">Tous potentiels</option>
            <?php foreach (['A', 'B', 'C'] as $value): ?>
              <option value="<?= $value ?>" <?= $filterPotential === $value ? 'selected' : '' ?>><?= $value ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <select name="rep" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
            <option value="">Tous representants</option>
            <?php foreach ($reps as $rep): ?>
              <option value="<?= (int) $rep['id'] ?>" <?= $filterRep === (int) $rep['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </form>

    <div class="space-y-2">
      <?php if (empty($contacts)): ?>
        <p class="text-sm text-slate-600 text-center py-8">Aucun contact pour le moment.</p>
      <?php else: ?>
        <div class="overflow-hidden rounded-lg border border-slate-200">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Localisation</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Potentiel</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Assigner a</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <?php foreach ($contacts as $contact): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3">
                    <div class="text-sm font-medium text-slate-900">
                      <a href="/contacts/view?id=<?= (int) $contact['id'] ?>" class="hover:text-blue-600">
                        <?= htmlspecialchars($contact['name'], ENT_QUOTES, 'UTF-8') ?>
                      </a>
                    </div>
                    <div class="text-xs text-slate-500">
                      <?= htmlspecialchars($contact['phone'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-sm text-slate-700">
                      <?= htmlspecialchars($contact['governorate_name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-xs text-slate-500">
                      <?= htmlspecialchars($contact['city_name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      <?= htmlspecialchars($contact['type'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                      <?= $contact['potential'] === 'A' ? 'bg-green-100 text-green-800' : 
                         ($contact['potential'] === 'B' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                      <?= htmlspecialchars($contact['potential'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-sm text-slate-700">
                      <?= htmlspecialchars($contact['rep_name'] ?: 'Non assigne', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex flex-col gap-1">
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                        <?= (int) $contact['active'] === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= (int) $contact['active'] === 1 ? 'Actif' : 'Inactif' ?>
                      </span>
                      <?php if (!empty($contact['latitude']) && !empty($contact['longitude'])): ?>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-location-dot mr-1 text-xs"></i>GPS OK
                      </span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <a href="/contacts_new?edit=<?= (int) $contact['id'] ?>" 
                         class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Modifier
                      </a>
                      <form method="post" class="inline-flex items-center gap-2" x-data="{ open: false }">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="quick_update">
                        <input type="hidden" name="contact_id" value="<?= (int) $contact['id'] ?>">
                        <select name="potential" class="h-8 rounded border border-slate-200 px-2 text-xs" 
                                onchange="this.form.submit()">
                          <?php foreach (['A', 'B', 'C'] as $value): ?>
                            <option value="<?= $value ?>" <?= $contact['potential'] === $value ? 'selected' : '' ?>><?= $value ?></option>
                          <?php endforeach; ?>
                        </select>
                        <label class="inline-flex items-center gap-1 cursor-pointer">
                          <input type="checkbox" name="active" <?= (int) $contact['active'] === 1 ? 'checked' : '' ?> 
                                 class="rounded border-slate-300"
                                 onchange="this.form.submit()">
                          <span class="text-xs text-slate-600">Actif</span>
                        </label>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
