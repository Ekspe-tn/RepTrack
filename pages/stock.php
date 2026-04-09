<?php

declare(strict_types=1);

$user = current_user();
$userId = $user['id'] ?? null;
$role = $user['role'] ?? 'rep';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $action = (string) ($_POST['action'] ?? '');
    
    if ($action === 'update_stock') {
        $qtyMap = $_POST['qty'] ?? [];
        try {
            if ($role === 'admin') {
                $stmt = db()->prepare('INSERT INTO stock_global (product_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()');
                foreach ($qtyMap as $productId => $qty) {
                    $productId = (int) $productId;
                    $qty = (int) $qty;
                    if ($productId <= 0) continue;
                    if ($qty < 0) $qty = 0;
                    $stmt->execute([$productId, $qty]);
                }
            } else {
                $stmt = db()->prepare('INSERT INTO stock (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()');
                foreach ($qtyMap as $productId => $qty) {
                    $productId = (int) $productId;
                    $qty = (int) $qty;
                    if ($productId <= 0) continue;
                    if ($qty < 0) $qty = 0;
                    $stmt->execute([$userId, $productId, $qty]);
                }
            }
            $success = 'Stock mis a jour avec succes.';
        } catch (Throwable $e) {
            $error = 'Erreur lors de la mise a jour du stock.';
        }
    }
    
    if ($action === 'assign_stock' && $role === 'admin') {
        $repId = (int) ($_POST['rep_id'] ?? 0);
        $productId = (int) ($_POST['assign_product_id'] ?? 0);
        $qty = (int) ($_POST['assign_qty'] ?? 0);
        
        if ($repId <= 0 || $productId <= 0 || $qty <= 0) {
            $error = 'Selection de delegue/produit/quantite invalide.';
        } else {
            try {
                db()->beginTransaction();
                
                $stmt = db()->prepare('SELECT quantity FROM stock_global WHERE product_id = ? FOR UPDATE');
                $stmt->execute([$productId]);
                $globalQty = (int) ($stmt->fetchColumn() ?? 0);
                
                if ($globalQty < $qty) {
                    throw new RuntimeException('Stock global insuffisant.');
                }
                
                $stmt = db()->prepare('UPDATE stock_global SET quantity = quantity - ?, updated_at = NOW() WHERE product_id = ?');
                $stmt->execute([$qty, $productId]);
                
                $stmt = db()->prepare('INSERT INTO stock (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = NOW()');
                $stmt->execute([$repId, $productId, $qty]);
                
                $stmt = db()->prepare('INSERT INTO stock_movements (user_id, product_id, visit_id, movement_type, quantity) VALUES (?, ?, NULL, ?, ?)');
                $stmt->execute([$repId, $productId, 'add', $qty]);
                
                db()->commit();
                $success = 'Stock affecte au delegue avec succes.';
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                $error = 'Erreur lors de l\'affectation: ' . $e->getMessage();
            }
        }
    }
}

// Fetch products with all fields
try {
    $products = db()->query('SELECT id, name, photo, cost, price, gtin13, specialities, active FROM products ORDER BY name')->fetchAll();
} catch (Throwable $e) {
    $products = [];
}

// Fetch stock data
try {
    if ($role === 'admin') {
        $stock = db()->query('SELECT p.id, p.name, p.photo, p.cost, p.price, p.gtin13, p.specialities, COALESCE(g.quantity, 0) AS quantity FROM products p LEFT JOIN stock_global g ON g.product_id = p.id ORDER BY p.name')->fetchAll();
    } else {
        $stmt = db()->prepare('SELECT p.id, p.name, p.photo, p.cost, p.price, p.gtin13, p.specialities, COALESCE(s.quantity, 0) AS quantity FROM products p LEFT JOIN stock s ON s.product_id = p.id AND s.user_id = ? ORDER BY p.name');
        $stmt->execute([$userId]);
        $stock = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    $stock = [];
}

// Calculate statistics
$totalProducts = count($stock);
$totalQuantity = array_sum(array_column($stock, 'quantity'));
$totalValue = 0;
foreach ($stock as $item) {
    if (!empty($item['cost']) && !empty($item['quantity'])) {
        $totalValue += (float)$item['cost'] * (int)$item['quantity'];
    }
}

// Calculate total samples distributed from the beginning
try {
    $totalSamplesDistributed = (int) db()->query('SELECT SUM(quantity) FROM stock_movements WHERE movement_type = "add"')->fetchColumn();
} catch (Throwable $e) {
    $totalSamplesDistributed = 0;
}

// Fetch delegates for assignment
try {
    $reps = db()->query("SELECT id, name FROM users WHERE role = 'rep' AND active = 1 ORDER BY name")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

$page_title = 'Stock & Produits';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Stock & Produits</h1>
      <p class="text-sm text-slate-500 mt-1">Gerer votre inventaire et les produits</p>
    </div>
    <div class="flex gap-3">
      <a href="/stock/history" class="h-10 px-4 rounded-xl border border-slate-200 text-sm text-slate-700 flex items-center gap-2 hover:bg-slate-50">
        <i class="fas fa-history"></i>
        <span>Historique</span>
      </a>
      <?php if ($role === 'admin'): ?>
        <a href="/products/new" class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-semibold flex items-center gap-2 hover:bg-blue-700">
          <i class="fas fa-plus"></i>
          <span>Nouveau Produit</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($success !== ''): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 text-sm flex items-center gap-3">
      <i class="fas fa-check-circle text-xl"></i>
      <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 text-sm flex items-center gap-3">
      <i class="fas fa-exclamation-circle text-xl"></i>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <!-- Statistics Cards -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Total Produits</div>
          <div class="text-2xl font-bold text-slate-900 mt-1"><?= $totalProducts ?></div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
          <i class="fas fa-box text-blue-600 text-xl"></i>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Quantite Totale</div>
          <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($totalQuantity) ?></div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
          <i class="fas fa-cubes text-green-600 text-xl"></i>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Valeur Stock</div>
          <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($totalValue, 2) ?> DT</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
          <i class="fas fa-coins text-purple-600 text-xl"></i>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Produits Actifs</div>
          <div class="text-2xl font-bold text-green-600 mt-1"><?= count(array_filter($stock, fn($p) => $p['active'] ?? 1)) ?></div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
          <i class="fas fa-check-double text-emerald-600 text-xl"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Samples Distributed Card -->
  <div class="bg-white rounded-2xl shadow-sm p-5">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Echantillons Distribues (depuis le debut)</div>
        <div class="text-2xl font-bold text-blue-600 mt-1"><?= number_format($totalSamplesDistributed) ?></div>
      </div>
      <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
        <i class="fas fa-hand-holding text-blue-600 text-xl"></i>
      </div>
    </div>
  </div>

  <!-- Products Grid -->
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100">
      <h2 class="text-lg font-semibold text-slate-900">Liste des Produits</h2>
      <p class="text-sm text-slate-500 mt-1">Gerer le stock pour chaque produit</p>
    </div>
    
    <form method="post" class="divide-y divide-slate-100">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_stock">
      
      <?php if (empty($stock)): ?>
        <div class="p-12 text-center">
          <i class="fas fa-box-open text-6xl text-slate-300 mb-4"></i>
          <h3 class="text-lg font-semibold text-slate-900 mb-2">Aucun produit</h3>
          <p class="text-sm text-slate-500 mb-4">Commencez par creer votre premier produit</p>
          <?php if ($role === 'admin'): ?>
            <a href="/products/new" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
              <i class="fas fa-plus"></i>
              Creer un produit
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="divide-y divide-slate-100">
          <?php foreach ($stock as $product): ?>
            <div class="p-5 hover:bg-slate-50 transition-colors">
              <div class="flex items-start gap-4">
                <!-- Product Photo -->
                <div class="w-20 h-20 rounded-xl bg-slate-100 flex-shrink-0 overflow-hidden">
                  <?php if (!empty($product['photo'])): ?>
                    <img src="<?= htmlspecialchars($product['photo'], ENT_QUOTES, 'UTF-8') ?>" 
                         alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                         class="w-full h-full object-cover">
                  <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                      <i class="fas fa-box text-slate-400 text-2xl"></i>
                    </div>
                  <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="flex-grow min-w-0">
                  <div class="flex items-start justify-between gap-4">
                    <div class="flex-grow">
                      <h3 class="text-base font-semibold text-slate-900 truncate">
                        <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                      </h3>
                      
                      <?php if (!empty($product['gtin13'])): ?>
                        <div class="flex items-center gap-2 mt-1">
                          <span class="text-xs text-slate-500">GTIN:</span>
                          <span class="text-xs font-mono text-slate-700"><?= htmlspecialchars($product['gtin13'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      <?php endif; ?>
                      
                      <?php if (!empty($product['specialities'])): ?>
                        <div class="flex items-start gap-2 mt-1">
                          <span class="text-xs text-slate-500 mt-0.5">Specialites:</span>
                          <span class="text-xs text-slate-700 line-clamp-2"><?= htmlspecialchars($product['specialities'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      <?php endif; ?>
                    </div>
                    
                    <!-- Price Info -->
                    <?php if (!empty($product['cost']) || !empty($product['price'])): ?>
                      <div class="text-right flex-shrink-0">
                        <?php if (!empty($product['cost'])): ?>
                          <div class="text-xs text-slate-500">Cout</div>
                          <div class="text-sm font-semibold text-orange-600"><?= number_format((float)$product['cost'], 2) ?> DT</div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['price'])): ?>
                          <div class="text-xs text-slate-500 mt-1">Prix</div>
                          <div class="text-sm font-semibold text-green-600"><?= number_format((float)$product['price'], 2) ?> DT</div>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Stock Quantity -->
                <div class="flex-shrink-0">
                  <div class="text-center">
                    <div class="text-xs text-slate-500 mb-1">Quantite</div>
                    <input type="number" 
                           min="0" 
                           name="qty[<?= (int)$product['id'] ?>]" 
                           value="<?= (int)$product['quantity'] ?>" 
                           class="w-24 h-12 rounded-xl border border-slate-200 text-center text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <!-- Submit Button -->
        <div class="p-5 bg-slate-50 border-t border-slate-100">
          <button type="submit" class="w-full h-12 rounded-xl bg-slate-900 text-white font-semibold flex items-center justify-center gap-2 hover:bg-slate-800 active:scale-[0.98] transition-all">
            <i class="fas fa-save"></i>
            <span>Enregistrer le Stock</span>
          </button>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Admin Only: Assign Stock to Delegate -->
  <?php if ($role === 'admin'): ?>
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100">
        <h2 class="text-lg font-semibold text-slate-900">Affecter du Stock a un Delegue</h2>
        <p class="text-sm text-slate-500 mt-1">Transferer du stock global vers un delegue</p>
      </div>
      
      <form method="post" class="p-5">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="assign_stock">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Delegue</label>
            <select name="rep_id" class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
              <option value="">Choisir un delegue</option>
              <?php foreach ($reps as $rep): ?>
                <option value="<?= (int)$rep['id'] ?>"><?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Produit</label>
            <select name="assign_product_id" class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
              <option value="">Choisir un produit</option>
              <?php foreach ($stock as $product): ?>
                <option value="<?= (int)$product['id'] ?>">
                  <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> (Stock: <?= (int)$product['quantity'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Quantite a Affecter</label>
            <input type="number" 
                   name="assign_qty" 
                   min="1" 
                   value="1" 
                   class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   required>
          </div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="h-12 px-6 rounded-xl bg-blue-600 text-white font-semibold flex items-center gap-2 hover:bg-blue-700 active:scale-[0.98] transition-all">
            <i class="fas fa-exchange-alt"></i>
            <span>Affecter le Stock</span>
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>