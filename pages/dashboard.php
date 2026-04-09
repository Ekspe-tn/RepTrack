<?php

declare(strict_types=1);

require __DIR__ . '/../includes/kpi_widgets.php';

$user = current_user();
$role = $user['role'] ?? 'rep';
$selectedType = (string) ($_GET['type'] ?? 'all');
$allowedTypes = ['all', 'rappel', 'presentation', 'formation'];
if (!in_array($selectedType, $allowedTypes, true)) {
    $selectedType = 'all';
}

$conditions = [];
$params = [];

if ($role !== 'admin') {
    $conditions[] = 'user_id = ?';
    $params[] = $user['id'];
}

if ($selectedType !== 'all') {
    $conditions[] = 'visit_type = ?';
    $params[] = $selectedType;
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$range30Sql = $whereSql ? ($whereSql . ' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)') : 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
$todaySql = $whereSql ? ($whereSql . ' AND DATE(created_at) = CURDATE()') : 'WHERE DATE(created_at) = CURDATE()';
$weekSql = $whereSql ? ($whereSql . ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)') : 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';

try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM visits $range30Sql");
    $stmt->execute($params);
    $total30 = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM visits $weekSql");
    $stmt->execute($params);
    $totalWeek = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM visits $todaySql");
    $stmt->execute($params);
    $totalToday = (int) $stmt->fetchColumn();
} catch (Throwable $e) {
    $total30 = 0;
    $totalWeek = 0;
    $totalToday = 0;
}

$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="text-sm text-slate-500">Hello</div>
    <div class="text-lg font-semibold text-slate-900">
      <?= htmlspecialchars($user['name'] ?? 'Rep', ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="text-xs text-slate-500 mt-1">Role: <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <label class="block text-sm font-medium text-slate-700">Filtrer par type de visite</label>
    <form method="get" class="mt-2">
      <select name="type" class="w-full h-12 rounded-xl border border-slate-200 px-3" onchange="this.form.submit()">
        <option value="all" <?= $selectedType === 'all' ? 'selected' : '' ?>>Tous</option>
        <option value="rappel" <?= $selectedType === 'rappel' ? 'selected' : '' ?>>Rappel</option>
        <option value="presentation" <?= $selectedType === 'presentation' ? 'selected' : '' ?>>Presentation</option>
        <option value="formation" <?= $selectedType === 'formation' ? 'selected' : '' ?>>Formation</option>
      </select>
    </form>
  </div>

  <div class="grid grid-cols-2 gap-3">
    <?= kpi_card('Visites 30j', (string) $total30, 'blue') ?>
    <?= kpi_card('Visites 7j', (string) $totalWeek, 'amber') ?>
    <?= kpi_card('Visites aujourd\'hui', (string) $totalToday, 'green') ?>
    <?= kpi_card('Type', strtoupper($selectedType === 'all' ? 'TOUS' : $selectedType), 'slate') ?>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="text-sm font-semibold text-slate-900">Actions rapides</div>
    <div class="mt-3 flex gap-2 flex-wrap">
      <a href="/visits/new" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm">Nouvelle visite</a>
      <a href="/visits" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm">Voir visites</a>
      <a href="/itinerary" class="px-4 py-2 rounded-xl bg-green-600 text-white text-sm flex items-center gap-2">
        <i class="fas fa-route"></i>
        <span>Planificateur d'itineraire</span>
      </a>

    <?php if ($role === 'admin'): ?>
      <a href="/delegues" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm">Gestion delegues</a>
    <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
