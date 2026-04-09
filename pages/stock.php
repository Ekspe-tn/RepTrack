<?php

declare(strict_types=1);

$success = '';
$error = '';

$user = current_user();
$userId = $user['id'] ?? null;
$role = $user['role'] ?? 'rep';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_product') {
        $name = trim((string) ($_POST['product_name'] ?? ''));
        $quantity = (int) ($_POST['product_qty'] ?? 0);
        if ($name === '') {
            $error = 'Nom du produit requis.';
        } elseif ($quantity < 0) {
            $error = 'Quantite invalide.';
        } else {
            try {
                db()->beginTransaction();
                $stmt = db()->prepare('INSERT INTO products (name, active) VALUES (?, 1)');
                $stmt->execute([$name]);
                $productId = (int) db()->lastInsertId();

                if ($role === 'admin') {
                    $stmt = db()->prepare('INSERT INTO stock_global (product_id, quantity) VALUES (?, ?)');
                    $stmt->execute([$productId, $quantity]);
                } else {
                    $stmt = db()->prepare('INSERT INTO stock (user_id, product_id, quantity) VALUES (?, ?, ?)');
                    $stmt->execute([$userId, $productId, $quantity]);
                }

                db()->commit();
                $success = 'Produit ajoute et stock mis a jour.';
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                $error = 'Erreur lors de l\'ajout du produit.';
            }
        }
    }

    if ($action === 'update_stock') {
        $qtyMap = $_POST['qty'] ?? [];
        try {
            if ($role === 'admin') {
                $stmt = db()->prepare('INSERT INTO stock_global (product_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()');
                foreach ($qtyMap as $productId => $qty) {
                    $productId = (int) $productId;
                    $qty = (int) $qty;
                    if ($productId <= 0) {
                        continue;
                    }
                    if ($qty < 0) {
                        $qty = 0;
                    }
                    $stmt->execute([$productId, $qty]);
                }
            } else {
                $stmt = db()->prepare('INSERT INTO stock (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()');
                foreach ($qtyMap as $productId => $qty) {
                    $productId = (int) $productId;
                    $qty = (int) $qty;
                    if ($productId <= 0) {
                        continue;
                    }
                    if ($qty < 0) {
                        $qty = 0;
                    }
                    $stmt->execute([$userId, $productId, $qty]);
                }
            }
            $success = 'Stock mis a jour.';
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
                $success = 'Stock affecte au delegue.';
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                $error = 'Erreur lors de l\'affectation.';
            }
        }
    }
}

try {
    $products = db()->query('SELECT id, name FROM products WHERE active = 1 ORDER BY name')->fetchAll();
} catch (Throwable $e) {
    $products = [];
}

try {
    $stmt = db()->prepare('SELECT p.id, p.name, COALESCE(s.quantity, 0) AS quantity FROM products p LEFT JOIN stock s ON s.product_id = p.id AND s.user_id = ? WHERE p.active = 1 ORDER BY p.name');
    $stmt->execute([$userId]);
    $repStock = $stmt->fetchAll();
} catch (Throwable $e) {
    $repStock = [];
}

try {
    $globalStock = db()->query('SELECT p.id, p.name, COALESCE(g.quantity, 0) AS quantity FROM products p LEFT JOIN stock_global g ON g.product_id = p.id WHERE p.active = 1 ORDER BY p.name')->fetchAll();
} catch (Throwable $e) {
    $globalStock = [];
}

try {
    $reps = db()->query("SELECT id, name FROM users WHERE role = 'rep' AND active = 1 ORDER BY name")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

$page_title = 'Stock';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <?php if ($success !== ''): ?>
    <div class="bg-green-50 text-green-700 rounded-2xl p-4 text-sm"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="bg-red-50 text-red-700 rounded-2xl p-4 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="flex flex-wrap gap-3 text-sm">
    <a href="/stock/history" class="text-blue-600">Historique mouvements</a>
    <?php if ($role === 'admin'): ?>
      <a href="/delegues" class="text-blue-600">Gestion delegues</a>
    <?php endif; ?>
  </div>

  <?php if ($role === 'admin'): ?>
    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h2 class="text-base font-semibold text-slate-900">Ajouter un produit (stock global)</h2>
      <form method="post" class="mt-3 grid grid-cols-1 gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_product">
        <div>
          <label class="block text-sm font-medium text-slate-700">Nom du produit</label>
          <input type="text" name="product_name" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Quantite initiale</label>
          <input type="number" name="product_qty" min="0" value="0" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
        </div>
        <button type="submit" class="h-12 rounded-xl bg-blue-600 text-white font-semibold active:scale-[0.98]">Ajouter</button>
      </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h2 class="text-base font-semibold text-slate-900">Stock global</h2>
      <form method="post" class="mt-3 space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_stock">
        <?php if (empty($globalStock)): ?>
          <p class="text-sm text-slate-600">Aucun produit disponible.</p>
        <?php else: ?>
          <?php foreach ($globalStock as $product): ?>
            <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2">
              <div class="text-sm text-slate-800">
                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
              </div>
              <input type="number" min="0" name="qty[<?= (int) $product['id'] ?>]" value="<?= (int) $product['quantity'] ?>" class="h-10 w-24 rounded-xl border border-slate-200 text-center">
            </div>
          <?php endforeach; ?>
          <button type="submit" class="w-full h-12 rounded-xl bg-slate-900 text-white font-semibold active:scale-[0.98]">Enregistrer stock global</button>
        <?php endif; ?>
      </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h2 class="text-base font-semibold text-slate-900">Affecter du stock a un delegue</h2>
      <form method="post" class="mt-3 grid grid-cols-1 gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="assign_stock">
        <div>
          <label class="block text-sm font-medium text-slate-700">Delegue</label>
          <select name="rep_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
            <option value="">Choisir</option>
            <?php foreach ($reps as $rep): ?>
              <option value="<?= (int) $rep['id'] ?>"><?= htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Produit</label>
          <select name="assign_product_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
            <option value="">Choisir</option>
            <?php foreach ($globalStock as $product): ?>
              <option value="<?= (int) $product['id'] ?>">
                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) $product['quantity'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Quantite a affecter</label>
          <input type="number" name="assign_qty" min="1" value="1" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
        </div>
        <button type="submit" class="h-12 rounded-xl bg-blue-600 text-white font-semibold">Affecter</button>
      </form>
    </div>

  <?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm p-4">
      <h2 class="text-base font-semibold text-slate-900">Mon stock</h2>
      <form method="post" class="mt-3 space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_stock">
        <?php if (empty($repStock)): ?>
          <p class="text-sm text-slate-600">Aucun produit disponible.</p>
        <?php else: ?>
          <?php foreach ($repStock as $product): ?>
            <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2">
              <div class="text-sm text-slate-800">
                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
              </div>
              <input type="number" min="0" name="qty[<?= (int) $product['id'] ?>]" value="<?= (int) $product['quantity'] ?>" class="h-10 w-24 rounded-xl border border-slate-200 text-center">
            </div>
          <?php endforeach; ?>
          <button type="submit" class="w-full h-12 rounded-xl bg-slate-900 text-white font-semibold active:scale-[0.98]">Enregistrer</button>
        <?php endif; ?>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
