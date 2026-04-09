<?php

declare(strict_types=1);

$visitId = (int) ($_GET['id'] ?? 0);
$user = current_user();
$role = $user['role'] ?? 'rep';

if ($visitId <= 0) {
    $page_title = 'Visite';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="bg-white rounded-2xl shadow-sm p-4 text-sm text-slate-600">Visite introuvable.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$params = [$visitId];
$where = 'v.id = ?';
if ($role !== 'admin') {
    $where .= ' AND v.user_id = ?';
    $params[] = $user['id'];
}

try {
    $stmt = db()->prepare("SELECT v.*, c.id AS contact_id, c.name AS contact_name, c.type AS contact_type, c.phone, c.address, g.name_fr AS governorate_name, ci.name_fr AS city_name, u.name AS rep_name\n        FROM visits v\n        JOIN contacts c ON c.id = v.contact_id\n        JOIN governorates g ON g.id = c.governorate_id\n        JOIN cities ci ON ci.id = c.city_id\n        JOIN users u ON u.id = v.user_id\n        WHERE $where\n        LIMIT 1");
    $stmt->execute($params);
    $visit = $stmt->fetch();
} catch (Throwable $e) {
    $visit = null;
}

if (!$visit) {
    $page_title = 'Visite';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="bg-white rounded-2xl shadow-sm p-4 text-sm text-slate-600">Visite introuvable.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

try {
    $stmt = db()->prepare('SELECT vs.quantity, p.name FROM visit_samples vs JOIN products p ON p.id = vs.product_id WHERE vs.visit_id = ?');
    $stmt->execute([$visitId]);
    $samples = $stmt->fetchAll();
} catch (Throwable $e) {
    $samples = [];
}

$page_title = 'Detail visite';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="text-sm text-slate-500">Contact</div>
    <div class="text-lg font-semibold text-slate-900">
      <a href="/contacts/view?id=<?= (int) $visit['contact_id'] ?? 0 ?>" class="hover:text-blue-600">
        <?= htmlspecialchars($visit['contact_name'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    </div>
    <div class="text-xs text-slate-500 mt-1">
      <?= htmlspecialchars($visit['contact_type'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($visit['governorate_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($visit['city_name'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="text-xs text-slate-500 mt-1">Rep: <?= htmlspecialchars($visit['rep_name'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="text-xs text-slate-500 mt-1">Date: <?= htmlspecialchars($visit['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Echantillons donnes</h2>
    <?php if (empty($samples)): ?>
      <p class="text-sm text-slate-600 mt-2">Aucun echantillon.</p>
    <?php else: ?>
      <ul class="mt-3 space-y-2 text-sm text-slate-700">
        <?php foreach ($samples as $sample): ?>
          <li><?= htmlspecialchars($sample['name'], ENT_QUOTES, 'UTF-8') ?> · x<?= (int) $sample['quantity'] ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Produits discutes</h2>
    <p class="text-sm text-slate-600 mt-2"><?= htmlspecialchars($visit['products_discussed'] ?: '-', ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Formation</h2>
    <p class="text-sm text-slate-600 mt-2"><?= htmlspecialchars($visit['training_content'] ?: '-', ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Notes</h2>
    <p class="text-sm text-slate-600 mt-2"><?= htmlspecialchars($visit['notes'] ?: '-', ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <a href="/visits" class="inline-flex items-center text-sm text-blue-600">&larr; Retour aux visites</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
