<?php

declare(strict_types=1);

$user = current_user();
$role = $user['role'] ?? 'rep';

$conditions = [];
$params = [];

if ($role !== 'admin') {
    $conditions[] = 'sm.user_id = ?';
    $params[] = $user['id'];
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

try {
    $stmt = db()->prepare("SELECT sm.id, sm.movement_type, sm.quantity, sm.created_at, p.name AS product_name, u.name AS rep_name, sm.visit_id\n        FROM stock_movements sm\n        JOIN products p ON p.id = sm.product_id\n        JOIN users u ON u.id = sm.user_id\n        $whereSql\n        ORDER BY sm.created_at DESC\n        LIMIT 100");
    $stmt->execute($params);
    $movements = $stmt->fetchAll();
} catch (Throwable $e) {
    $movements = [];
}

$page_title = 'Historique stock';
require __DIR__ . '/../includes/header.php';
?>

<div class="bg-white rounded-2xl shadow-sm p-4">
  <h2 class="text-base font-semibold text-slate-900">Historique des mouvements</h2>
  <?php if (empty($movements)): ?>
    <p class="text-sm text-slate-600 mt-3">Aucun mouvement.</p>
  <?php else: ?>
    <div class="mt-3 space-y-3">
      <?php foreach ($movements as $move): ?>
        <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2">
          <div>
            <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars($move['product_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-xs text-slate-500">
              <?= htmlspecialchars($move['movement_type'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $move['quantity'] ?> · <?= htmlspecialchars($move['created_at'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
          <div class="text-xs text-slate-500 text-right">
            <?= htmlspecialchars($move['rep_name'], ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($move['visit_id'])): ?>
              <div class="text-blue-600">Visite #<?= (int) $move['visit_id'] ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
