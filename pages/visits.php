<?php

declare(strict_types=1);

$errors = [];
$success = '';

$form = [
    'governorate_id' => '',
    'city_id' => '',
    'contact_id' => '',
    'visit_type' => 'rappel',
    'products_discussed' => '',
    'training_content' => '',
    'notes' => '',
];

$selectedProducts = [];
$qtyMap = [];

try {
    $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
} catch (Throwable $e) {
    $governorates = [];
}

$userId = current_user()['id'] ?? null;

try {
    $stmt = db()->prepare('SELECT p.id, p.name, s.quantity FROM stock s JOIN products p ON p.id = s.product_id WHERE s.user_id = ? AND s.quantity > 0 AND p.active = 1 ORDER BY p.name');
    $stmt->execute([$userId]);
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $products = [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $prefContactId = (int) ($_GET['contact_id'] ?? 0);
    if ($prefContactId > 0) {
        try {
            $stmt = db()->prepare('SELECT id, governorate_id, city_id FROM contacts WHERE id = ? AND active = 1 LIMIT 1');
            $stmt->execute([$prefContactId]);
            $prefContact = $stmt->fetch();
            if ($prefContact) {
                $form['contact_id'] = (string) $prefContact['id'];
                $form['governorate_id'] = (string) $prefContact['governorate_id'];
                $form['city_id'] = (string) $prefContact['city_id'];
            }
        } catch (Throwable $e) {
            // ignore prefill errors
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $form['governorate_id'] = (string) ($_POST['governorate_id'] ?? '');
    $form['city_id'] = (string) ($_POST['city_id'] ?? '');
    $form['contact_id'] = (string) ($_POST['contact_id'] ?? '');
    $form['visit_type'] = (string) ($_POST['visit_type'] ?? 'rappel');
    $form['products_discussed'] = trim((string) ($_POST['products_discussed'] ?? ''));
    $form['training_content'] = trim((string) ($_POST['training_content'] ?? ''));
    $form['notes'] = trim((string) ($_POST['notes'] ?? ''));

    $selectedProducts = array_map('intval', $_POST['products'] ?? []);
    $qtyMap = $_POST['qty'] ?? [];

    $governorateId = (int) $form['governorate_id'];
    $cityId = (int) $form['city_id'];
    $contactId = (int) $form['contact_id'];

    if ($governorateId <= 0) {
        $errors[] = 'Veuillez choisir un gouvernorat.';
    }
    if ($cityId <= 0) {
        $errors[] = 'Veuillez choisir une delegation.';
    }
    if ($contactId <= 0) {
        $errors[] = 'Veuillez choisir un contact.';
    }

    $allowedTypes = ['rappel', 'presentation', 'formation'];
    if (!in_array($form['visit_type'], $allowedTypes, true)) {
        $errors[] = 'Type de visite invalide.';
    }

    if (!empty($products) && empty($selectedProducts)) {
        $errors[] = 'Veuillez selectionner au moins un produit en echantillon.';
    }

    if (empty($errors)) {
        try {
            $stmt = db()->prepare('SELECT id, governorate_id, city_id FROM contacts WHERE id = ? AND active = 1 LIMIT 1');
            $stmt->execute([$contactId]);
            $contact = $stmt->fetch();
            if (!$contact) {
                $errors[] = 'Contact introuvable.';
            } elseif ((int) $contact['governorate_id'] !== $governorateId || (int) $contact['city_id'] !== $cityId) {
                $errors[] = 'Le contact ne correspond pas a la zone choisie.';
            }
        } catch (Throwable $e) {
            $errors[] = 'Erreur lors de la verification du contact.';
        }
    }

    $sampleSummary = [];
    $sampleItems = [];

    if (empty($errors)) {
        $placeholders = implode(',', array_fill(0, count($selectedProducts), '?'));
        try {
            $stmt = db()->prepare("SELECT s.product_id, s.quantity, p.name FROM stock s JOIN products p ON p.id = s.product_id WHERE s.user_id = ? AND s.product_id IN ($placeholders)");
            $stmt->execute(array_merge([$userId], $selectedProducts));
            $stockRows = $stmt->fetchAll();
        } catch (Throwable $e) {
            $stockRows = [];
        }

        $stockMap = [];
        foreach ($stockRows as $row) {
            $stockMap[(int) $row['product_id']] = [
                'quantity' => (int) $row['quantity'],
                'name' => (string) $row['name'],
            ];
        }

        foreach ($selectedProducts as $productId) {
            $qty = isset($qtyMap[$productId]) ? (int) $qtyMap[$productId] : 0;
            if ($qty <= 0) {
                $errors[] = 'Quantite invalide pour un produit selectionne.';
                break;
            }
            if (!isset($stockMap[$productId])) {
                $errors[] = 'Produit non disponible en stock.';
                break;
            }
            if ($qty > $stockMap[$productId]['quantity']) {
                $errors[] = 'Stock insuffisant pour ' . $stockMap[$productId]['name'] . '.';
                break;
            }

            $sampleItems[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'name' => $stockMap[$productId]['name'],
            ];
            $sampleSummary[] = $stockMap[$productId]['name'] . ' x' . $qty;
        }
    }

    if (empty($errors)) {
        try {
            db()->beginTransaction();

            $stmt = db()->prepare('INSERT INTO visits (user_id, contact_id, visit_type, products_discussed, samples_given, training_content, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $userId,
                $contactId,
                $form['visit_type'],
                $form['products_discussed'] ?: null,
                !empty($sampleSummary) ? implode(', ', $sampleSummary) : null,
                $form['training_content'] ?: null,
                $form['notes'] ?: null,
            ]);

            $visitId = (int) db()->lastInsertId();

            $sampleStmt = db()->prepare('INSERT INTO visit_samples (visit_id, product_id, quantity) VALUES (?, ?, ?)');
            $stockStmt = db()->prepare('UPDATE stock SET quantity = quantity - ?, updated_at = NOW() WHERE user_id = ? AND product_id = ? AND quantity >= ?');
            $movementStmt = db()->prepare('INSERT INTO stock_movements (user_id, product_id, visit_id, movement_type, quantity) VALUES (?, ?, ?, ?, ?)');

            foreach ($sampleItems as $item) {
                $sampleStmt->execute([$visitId, $item['product_id'], $item['quantity']]);

                $stockStmt->execute([$item['quantity'], $userId, $item['product_id'], $item['quantity']]);
                if ($stockStmt->rowCount() === 0) {
                    throw new RuntimeException('Stock insuffisant pour ' . $item['name']);
                }

                $movementStmt->execute([$userId, $item['product_id'], $visitId, 'deduct', $item['quantity']]);
            }

            db()->commit();

            $success = 'Visite enregistree.';
            $form = [
                'governorate_id' => '',
                'city_id' => '',
                'contact_id' => '',
                'visit_type' => 'rappel',
                'products_discussed' => '',
                'training_content' => '',
                'notes' => '',
            ];
            $selectedProducts = [];
            $qtyMap = [];
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'Erreur lors de l\'enregistrement.';
        }
    }
}

$formJson = json_encode($form, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

$page_title = 'Nouvelle visite';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4" x-data='{"step":1,"form":<?= $formJson ?>}'>
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-slate-900">Flux nouvelle visite</div>
      <div class="text-xs text-slate-500">Etape <span x-text="step"></span>/4</div>
    </div>
    <div class="mt-3 grid grid-cols-4 gap-2">
      <div class="h-1 rounded-full" :class="step >= 1 ? 'bg-blue-600' : 'bg-slate-200'"></div>
      <div class="h-1 rounded-full" :class="step >= 2 ? 'bg-blue-600' : 'bg-slate-200'"></div>
      <div class="h-1 rounded-full" :class="step >= 3 ? 'bg-blue-600' : 'bg-slate-200'"></div>
      <div class="h-1 rounded-full" :class="step >= 4 ? 'bg-blue-600' : 'bg-slate-200'"></div>
    </div>
  </div>

  <?php if ($success !== ''): ?>
    <div class="bg-green-50 text-green-700 rounded-2xl p-4 text-sm"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="bg-red-50 text-red-700 rounded-2xl p-4 text-sm">
      <ul class="list-disc pl-5 space-y-1">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-4">
    <?= csrf_field() ?>

    <div class="bg-white rounded-2xl shadow-sm p-4" x-show="step === 1" data-city-group>
      <h3 class="text-sm font-semibold text-slate-900">1. Zone & contact</h3>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Gouvernorat</label>
        <select name="governorate_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" data-governorate-select x-model="form.governorate_id" required>
          <option value="">Choisir</option>
          <?php foreach ($governorates as $gov): ?>
            <option value="<?= (int) $gov['id'] ?>">
              <?= htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Delegation</label>
        <select name="city_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" data-city-select data-city-selected="<?= htmlspecialchars((string) $form['city_id'], ENT_QUOTES, 'UTF-8') ?>" x-model="form.city_id" required>
          <option value="">Choisir</option>
        </select>
      </div>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Contact</label>
        <select name="contact_id" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" data-contact-select data-contact-selected="<?= htmlspecialchars((string) $form['contact_id'], ENT_QUOTES, 'UTF-8') ?>" x-model="form.contact_id" required>
          <option value="">Choisir un contact</option>
        </select>
        <div class="mt-2">
          <a href="#" class="text-xs text-blue-600 opacity-50 pointer-events-none" data-contact-profile-link>
            Ouvrir profil
          </a>
        </div>
      </div>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Type de visite</label>
        <select name="visit_type" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" x-model="form.visit_type" required>
          <option value="rappel">Rappel</option>
          <option value="presentation">Presentation</option>
          <option value="formation">Formation</option>
        </select>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4" x-show="step === 2">
      <h3 class="text-sm font-semibold text-slate-900">2. Produits & echantillons</h3>
      <?php if (empty($products)): ?>
        <p class="text-sm text-amber-600 mt-3">Aucun produit en stock pour ce delegue.</p>
      <?php else: ?>
        <div class="mt-3 space-y-3">
          <?php foreach ($products as $product): ?>
            <?php
              $productId = (int) $product['id'];
              $qtyValue = isset($qtyMap[$productId]) ? (int) $qtyMap[$productId] : 0;
              $checked = in_array($productId, $selectedProducts, true);
            ?>
            <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2" data-product-row>
              <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="products[]" value="<?= $productId ?>" data-product-checkbox <?= $checked ? 'checked' : '' ?>>
                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                <span class="text-xs text-slate-400">(Stock <?= (int) $product['quantity'] ?>)</span>
              </label>
              <div class="flex items-center gap-2">
                <button type="button" class="h-8 w-8 rounded-lg border border-slate-200" data-qty-minus>-</button>
                <input type="number" name="qty[<?= $productId ?>]" value="<?= $qtyValue ?>" min="0" max="<?= (int) $product['quantity'] ?>" class="h-8 w-16 rounded-lg border border-slate-200 text-center" data-product-qty>
                <button type="button" class="h-8 w-8 rounded-lg border border-slate-200" data-qty-plus>+</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Produits discutes</label>
        <textarea name="products_discussed" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" rows="3" placeholder="Produits, promos, recommandations..." x-model="form.products_discussed"></textarea>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4" x-show="step === 3">
      <h3 class="text-sm font-semibold text-slate-900">3. Formation & notes</h3>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Contenu de formation</label>
        <textarea name="training_content" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" rows="3" x-model="form.training_content"></textarea>
      </div>
      <div class="mt-3">
        <label class="block text-sm font-medium text-slate-700">Notes</label>
        <textarea name="notes" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" rows="3" x-model="form.notes"></textarea>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4" x-show="step === 4">
      <h3 class="text-sm font-semibold text-slate-900">4. Recap</h3>
      <p class="text-sm text-slate-600 mt-2">Verifier les informations avant de sauvegarder.</p>
      <div class="mt-3 space-y-2 text-sm text-slate-600">
        <div>Gouvernorat ID: <span x-text="form.governorate_id || '-'"></span></div>
        <div>Delegation ID: <span x-text="form.city_id || '-'"></span></div>
        <div>Contact ID: <span x-text="form.contact_id || '-'"></span></div>
        <div>Type: <span x-text="form.visit_type"></span></div>
      </div>
    </div>

    <div class="flex items-center justify-between">
      <button type="button" class="h-11 px-4 rounded-xl border border-slate-200 text-slate-600" @click="step = Math.max(1, step - 1)">Retour</button>
      <div class="flex gap-2">
        <button type="button" class="h-11 px-4 rounded-xl bg-slate-100 text-slate-700" x-show="step < 4" @click="step = Math.min(4, step + 1)">Suivant</button>
        <button type="submit" class="h-11 px-4 rounded-xl bg-blue-600 text-white" x-show="step === 4">Enregistrer</button>
      </div>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
