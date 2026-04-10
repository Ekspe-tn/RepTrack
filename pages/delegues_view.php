<?php

declare(strict_types=1);

require_role('admin');

$repId = (int) ($_GET['id'] ?? 0);
if ($repId <= 0) {
    header('Location: /delegues');
    exit;
}

$success = '';
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_deposit') {
        try {
            $stmt = db()->prepare("INSERT INTO deposits (user_id, amount, description, deposit_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $repId,
                (float) ($_POST['amount'] ?? 0),
                $_POST['description'] ?? '',
                $_POST['deposit_date'] ?? date('Y-m-d'),
                current_user()['id']
            ]);
            $success = 'Depot ajoute avec succes.';
        } catch (Throwable $e) {
            $error = 'Erreur lors de l\'ajout du depot.';
        }
    } elseif ($action === 'update_car') {
        try {
            $stmt = db()->prepare("UPDATE users SET 
                car_make = ?, 
                car_model = ?, 
                car_leasing_start = ?, 
                car_leasing_end = ?, 
                car_monthly_cost = ?, 
                car_fuel_cost = ?, 
                car_weekly_km = ? 
                WHERE id = ? AND role = 'rep'");
            $stmt->execute([
                !empty($_POST['car_make']) ? $_POST['car_make'] : null,
                !empty($_POST['car_model']) ? $_POST['car_model'] : null,
                !empty($_POST['car_leasing_start']) ? $_POST['car_leasing_start'] : null,
                !empty($_POST['car_leasing_end']) ? $_POST['car_leasing_end'] : null,
                !empty($_POST['car_monthly_cost']) ? (float) $_POST['car_monthly_cost'] : null,
                !empty($_POST['car_fuel_cost']) ? (float) $_POST['car_fuel_cost'] : null,
                !empty($_POST['car_weekly_km']) ? (int) $_POST['car_weekly_km'] : null,
                $repId
            ]);
            $success = 'Informations voiture mises a jour.';
        } catch (Throwable $e) {
            $error = 'Erreur lors de la mise a jour de la voiture.';
        }
    }
}

// Fetch delegate info with car details
try {
    $stmt = db()->prepare("SELECT u.*, g.name_fr AS governorate_name 
        FROM users u 
        LEFT JOIN governorates g ON g.id = u.governorate_id 
        WHERE u.id = ? AND u.role = 'rep'");
    $stmt->execute([$repId]);
    $rep = $stmt->fetch();
    
    if (!$rep) {
        header('Location: /delegues');
        exit;
    }
} catch (Throwable $e) {
    header('Location: /delegues');
    exit;
}

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

// Get all governorate names
$allGovernorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
$govNames = [];
foreach ($governorateIds as $govId) {
    foreach ($allGovernorates as $gov) {
        if ((int) $gov['id'] === $govId) {
            $govNames[] = $gov['name_fr'];
            break;
        }
    }
}

// Calculate stats for this delegate
$stats = [
    'visits_30d' => 0,
    'visits_total' => 0,
    'samples_30d' => 0,
    'samples_total' => 0,
    'contacts_total' => 0,
    'stock_total' => 0,
    'deposits_total' => 0,
];

$visitDateFrom = date('Y-m-d H:i:s', strtotime('-30 days'));
$visitDateTo = date('Y-m-d H:i:s');

// Visits
try {
    $stmt = db()->prepare("SELECT COUNT(*) AS visits_total, 
        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS visits_30d 
        FROM visits WHERE user_id = ?");
    $stmt->execute([$visitDateFrom, $repId]);
    $row = $stmt->fetch();
    $stats['visits_total'] = (int) $row['visits_total'];
    $stats['visits_30d'] = (int) $row['visits_30d'];
} catch (Throwable $e) {
}

// Samples
try {
    $stmt = db()->prepare("SELECT SUM(vs.quantity) AS samples_total,
        SUM(CASE WHEN v.created_at >= ? THEN vs.quantity ELSE 0 END) AS samples_30d
        FROM visit_samples vs
        JOIN visits v ON v.id = vs.visit_id
        WHERE v.user_id = ?");
    $stmt->execute([$visitDateFrom, $repId]);
    $row = $stmt->fetch();
    $stats['samples_total'] = (int) ($row['samples_total'] ?? 0);
    $stats['samples_30d'] = (int) ($row['samples_30d'] ?? 0);
} catch (Throwable $e) {
}

// Contacts
try {
    $stmt = db()->prepare("SELECT COUNT(*) AS contacts_total FROM contacts WHERE assigned_rep_id = ?");
    $stmt->execute([$repId]);
    $stats['contacts_total'] = (int) $stmt->fetchColumn();
} catch (Throwable $e) {
}

// Stock
try {
    $stmt = db()->prepare("SELECT SUM(quantity) AS stock_total FROM stock WHERE user_id = ?");
    $stmt->execute([$repId]);
    $stats['stock_total'] = (int) ($stmt->fetchColumn() ?? 0);
} catch (Throwable $e) {
}

// Deposits
try {
    $stmt = db()->prepare("SELECT id, amount, description, deposit_date, created_at 
        FROM deposits 
        WHERE user_id = ? 
        ORDER BY deposit_date DESC, created_at DESC");
    $stmt->execute([$repId]);
    $deposits = $stmt->fetchAll();
    
    // Calculate total
    foreach ($deposits as $deposit) {
        $stats['deposits_total'] += (float) $deposit['amount'];
    }
} catch (Throwable $e) {
    $deposits = [];
}

$page_title = 'Fiche delegue - ' . htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8');
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center gap-4">
    <a href="/delegues" class="text-slate-500 hover:text-slate-700">
      <i class="fas fa-arrow-left text-xl"></i>
    </a>
    <div>
      <h1 class="text-2xl font-bold text-slate-900">
        <?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <p class="text-sm text-slate-500 mt-1">
        <?= htmlspecialchars($rep['email'], ENT_QUOTES, 'UTF-8') ?>
        <?php if ($rep['phone']): ?>
          | <?= htmlspecialchars($rep['phone'], ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <?php if ($success !== ''): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 text-sm flex items-center gap-3 shadow-sm">
      <i class="fas fa-check-circle text-xl"></i>
      <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 text-sm flex items-center gap-3 shadow-sm">
      <i class="fas fa-exclamation-circle text-xl"></i>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <!-- Stats Grid -->
  <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl shadow-sm p-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center">
          <i class="fas fa-route text-white text-xl"></i>
        </div>
        <div>
          <div class="text-xs text-slate-500">Visites (30j)</div>
          <div class="text-2xl font-bold text-slate-900"><?= $stats['visits_30d'] ?></div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-purple-600 flex items-center justify-center">
          <i class="fas fa-gift text-white text-xl"></i>
        </div>
        <div>
          <div class="text-xs text-slate-500">Echantillons (30j)</div>
          <div class="text-2xl font-bold text-slate-900"><?= $stats['samples_30d'] ?></div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-green-600 flex items-center justify-center">
          <i class="fas fa-users text-white text-xl"></i>
        </div>
        <div>
          <div class="text-xs text-slate-500">Contacts</div>
          <div class="text-2xl font-bold text-slate-900"><?= $stats['contacts_total'] ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Car Information Section -->
  <div class="bg-white rounded-2xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
        <i class="fas fa-car text-blue-600"></i>
        Informations Voiture
      </h2>
      <button onclick="document.getElementById('carForm').classList.toggle('hidden')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
        <i class="fas fa-edit"></i> Modifier
      </button>
    </div>

    <?php if ($rep['car_make'] || $rep['car_model']): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <div class="text-xs text-slate-500 mb-1">Constructeur</div>
          <div class="text-sm font-medium text-slate-900">
            <?= htmlspecialchars($rep['car_make'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Modele</div>
          <div class="text-sm font-medium text-slate-900">
            <?= htmlspecialchars($rep['car_model'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Debut leasing</div>
          <div class="text-sm font-medium text-slate-900">
            <?= $rep['car_leasing_start'] ? date('d/m/Y', strtotime($rep['car_leasing_start'])) : '-' ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Fin leasing</div>
          <div class="text-sm font-medium text-slate-900">
            <?= $rep['car_leasing_end'] ? date('d/m/Y', strtotime($rep['car_leasing_end'])) : '-' ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Cout mensuel</div>
          <div class="text-sm font-medium text-slate-900">
            <?= $rep['car_monthly_cost'] ? number_format((float) $rep['car_monthly_cost'], 2) . ' TND' : '-' ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Essence mensuelle</div>
          <div class="text-sm font-medium text-slate-900">
            <?= $rep['car_fuel_cost'] ? number_format((float) $rep['car_fuel_cost'], 2) . ' TND' : '-' ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Kilometrage hebdomadaire</div>
          <div class="text-sm font-medium text-slate-900">
            <?= $rep['car_weekly_km'] ? (int) $rep['car_weekly_km'] . ' km' : '-' ?>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Zone</div>
          <div class="text-sm font-medium text-slate-900">
            <?= !empty($govNames) ? htmlspecialchars(implode(', ', $govNames), ENT_QUOTES, 'UTF-8') : '-' ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <p class="text-sm text-slate-500">Aucune information sur la voiture.</p>
    <?php endif; ?>

    <!-- Edit Car Form -->
    <form id="carForm" method="post" class="hidden mt-6 pt-6 border-t border-slate-200">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_car">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Constructeur</label>
          <input type="text" name="car_make"
                 value="<?= htmlspecialchars($rep['car_make'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="ex: Renault, Peugeot">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Modele</label>
          <input type="text" name="car_model"
                 value="<?= htmlspecialchars($rep['car_model'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="ex: Clio, 308">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Debut leasing</label>
          <input type="date" name="car_leasing_start"
                 value="<?= $rep['car_leasing_start'] ?? '' ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Fin leasing</label>
          <input type="date" name="car_leasing_end"
                 value="<?= $rep['car_leasing_end'] ?? '' ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Cout mensuel (TND)</label>
          <input type="number" name="car_monthly_cost"
                 value="<?= $rep['car_monthly_cost'] ?? '' ?>"
                 step="0.01" min="0"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="0.00">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Essence mensuelle (TND)</label>
          <input type="number" name="car_fuel_cost"
                 value="<?= $rep['car_fuel_cost'] ?? '' ?>"
                 step="0.01" min="0"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="0.00">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Kilometrage hebdomadaire (km)</label>
          <input type="number" name="car_weekly_km"
                 value="<?= $rep['car_weekly_km'] ?? '' ?>"
                 min="0"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="0">
        </div>
      </div>
      
      <div class="flex gap-2 mt-4">
        <button type="submit" class="flex-1 h-10 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
          Sauvegarder
        </button>
        <button type="button" onclick="document.getElementById('carForm').classList.add('hidden')" class="h-10 px-4 rounded-lg border border-slate-200 text-sm hover:bg-slate-50">
          Annuler
        </button>
      </div>
    </form>
  </div>

  <!-- Deposits Section -->
  <div class="bg-white rounded-2xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
        <i class="fas fa-wallet text-green-600"></i>
        Depots
        <span class="text-sm font-normal text-slate-500">
          (Total: <?= number_format($stats['deposits_total'], 2) ?> TND)
        </span>
      </h2>
    </div>

    <!-- Add Deposit Form -->
    <form method="post" class="mb-6 p-4 bg-slate-50 rounded-xl">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_deposit">
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Montant (TND)</label>
          <input type="number" name="amount" required step="0.01" min="0"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="0.00">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Date</label>
          <input type="date" name="deposit_date"
                 value="<?= date('Y-m-d') ?>"
                 required
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
          <input type="text" name="description"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="Description du depot">
        </div>
      </div>
      
      <button type="submit" class="mt-4 h-10 px-6 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">
        <i class="fas fa-plus mr-2"></i>
        Ajouter depot
      </button>
    </form>

    <!-- Deposits List -->
    <?php if (empty($deposits)): ?>
      <p class="text-sm text-slate-600 text-center py-8">Aucun depot.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($deposits as $deposit): ?>
          <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
            <div>
              <div class="text-sm font-semibold text-slate-900">
                <?= number_format((float) $deposit['amount'], 2) ?> TND
              </div>
              <div class="text-xs text-slate-500 mt-1">
                <?= date('d/m/Y', strtotime($deposit['deposit_date'])) ?>
                <?php if ($deposit['description']): ?>
                  | <?= htmlspecialchars($deposit['description'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="text-xs text-slate-400">
              <?= date('H:i', strtotime($deposit['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Quick Actions -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <a href="/visits?rep_id=<?= $repId ?>" class="h-12 rounded-xl bg-blue-600 text-white text-sm font-semibold flex items-center justify-center gap-2 hover:bg-blue-700 shadow-sm">
      <i class="fas fa-route"></i>
      <span>Voir visites</span>
    </a>
    <a href="/contacts?rep_id=<?= $repId ?>" class="h-12 rounded-xl bg-purple-600 text-white text-sm font-semibold flex items-center justify-center gap-2 hover:bg-purple-700 shadow-sm">
      <i class="fas fa-users"></i>
      <span>Voir contacts</span>
    </a>
    <a href="/stock/history?rep_id=<?= $repId ?>" class="h-12 rounded-xl bg-amber-600 text-white text-sm font-semibold flex items-center justify-center gap-2 hover:bg-amber-700 shadow-sm">
      <i class="fas fa-boxes"></i>
      <span>Mouvements stock</span>
    </a>
    <a href="/delegues" class="h-12 rounded-xl border-2 border-slate-200 text-slate-700 text-sm font-semibold flex items-center justify-center gap-2 hover:bg-slate-50 shadow-sm">
      <i class="fas fa-arrow-left"></i>
      <span>Retour</span>
    </a>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>