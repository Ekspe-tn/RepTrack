<?php

declare(strict_types=1);

$user = current_user();
$role = $user['role'] ?? 'rep';
$selectedType = (string) ($_GET['type'] ?? 'all');
$repId = (int) ($_GET['rep_id'] ?? 0);
$contactId = (int) ($_GET['contact_id'] ?? 0);
$search = trim((string) ($_GET['q'] ?? ''));
$export = ((string) ($_GET['export'] ?? '')) === 'csv';
$allowedTypes = ['all', 'rappel', 'presentation', 'formation'];
if (!in_array($selectedType, $allowedTypes, true)) {
    $selectedType = 'all';
}

$conditions = [];
$params = [];

$reps = [];
if ($role === 'admin') {
    try {
        $reps = db()->query("SELECT id, name FROM users WHERE role = 'rep' ORDER BY name")->fetchAll();
    } catch (Throwable $e) {
        $reps = [];
    }
}

if ($role !== 'admin') {
    $conditions[] = 'v.user_id = ?';
    $params[] = $user['id'];
}

if ($role === 'admin' && $repId > 0) {
    $conditions[] = 'v.user_id = ?';
    $params[] = $repId;
}

if ($selectedType !== 'all') {
    $conditions[] = 'v.visit_type = ?';
    $params[] = $selectedType;
}

if ($contactId > 0) {
    $conditions[] = 'v.contact_id = ?';
    $params[] = $contactId;
}

if ($search !== '') {
    $conditions[] = '(c.name LIKE ? OR u.name LIKE ? OR v.visit_type LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$limitSql = $export ? 'LIMIT 5000' : 'LIMIT 50';

try {
    $stmt = db()->prepare("SELECT v.id, v.visit_type, v.created_at, c.id AS contact_id, c.name AS contact_name, c.type AS contact_type, u.name AS rep_name\n        FROM visits v\n        JOIN contacts c ON c.id = v.contact_id\n        JOIN users u ON u.id = v.user_id\n        $whereSql\n        ORDER BY v.created_at DESC\n        $limitSql");
    $stmt->execute($params);
    $visits = $stmt->fetchAll();
} catch (Throwable $e) {
    $visits = [];
}


if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=visits.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Contact', 'Type contact', 'Type visite', 'Delegue', 'Date']);
    foreach ($visits as $visit) {
        fputcsv($out, [
            $visit['id'],
            $visit['contact_name'],
            $visit['contact_type'],
            $visit['visit_type'],
            $visit['rep_name'],
            $visit['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

$page_title = 'Visites';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-slate-900">Dernieres visites</h2>
      <a href="/visits/new" class="text-xs text-blue-600">Nouvelle visite</a>
    </div>
    <form method="get" class="mt-3 grid grid-cols-1 gap-3">
      <?php if ($contactId > 0): ?>
        <input type="hidden" name="contact_id" value="<?= (int) $contactId ?>">
      <?php endif; ?>
      <div>
        <label class="block text-sm font-medium text-slate-700">Recherche</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" placeholder="Contact ou delegue">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Filtrer par type</label>
        <select name="type" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3">
          <option value="all" <?= $selectedType === 'all' ? 'selected' : '' ?>>Tous</option>
          <option value="rappel" <?= $selectedType === 'rappel' ? 'selected' : '' ?>>Rappel</option>
          <option value="presentation" <?= $selectedType === 'presentation' ? 'selected' : '' ?>>Presentation</option>
          <option value="formation" <?= $selectedType === 'formation' ? 'selected' : '' ?>>Formation</option>
        </select>
      </div>
      <?php if ($role === 'admin'): ?>
      <div>
        <label class="block text-sm font-medium text-slate-700">Delegue</label>
        <select name="rep_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3">
          <option value="">Tous</option>
          <?php foreach ($reps as $rep): ?>
            <option value="<?= (int) $rep['id'] ?>" <?= $repId === (int) $rep['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <button type="submit" class="h-12 rounded-xl bg-slate-900 text-white font-semibold">Filtrer</button>
    </form>
    <?php
      $exportUrl = '/visits?export=csv&type=' . urlencode($selectedType);
    if ($repId > 0) {
        $exportUrl .= '&rep_id=' . urlencode((string) $repId);
    }
    if ($contactId > 0) {
        $exportUrl .= '&contact_id=' . urlencode((string) $contactId);
    }
      if ($search !== '') {
          $exportUrl .= '&q=' . urlencode($search);
      }
    ?>
    <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="mt-3 inline-flex items-center text-xs text-blue-600">Exporter CSV</a>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <?php if (empty($visits)): ?>
      <p class="text-sm text-slate-600">Aucune visite enregistree.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($visits as $visit): ?>
          <div class="border border-slate-100 rounded-xl px-3 py-2 hover:bg-slate-50">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm font-medium text-slate-900">
                  <a href="/contacts/view?id=<?= (int) $visit['contact_id'] ?? 0 ?>" class="hover:text-blue-600">
                    <?= htmlspecialchars($visit['contact_name'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <span class="text-xs text-slate-500">(<?= htmlspecialchars($visit['contact_type'], ENT_QUOTES, 'UTF-8') ?>)</span>
                </div>
                <div class="text-xs text-slate-500">
                  <?= htmlspecialchars($visit['visit_type'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($visit['created_at'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
              <div class="text-xs text-slate-500 text-right">
                <?= htmlspecialchars($visit['rep_name'], ENT_QUOTES, 'UTF-8') ?>
              </div>
            </div>
            <div class="mt-2">
              <a href="/visits/view?id=<?= (int) $visit['id'] ?>" class="text-xs text-blue-600 hover:text-blue-800">
                Voir visite
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
