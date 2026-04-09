<?php

declare(strict_types=1);

require_role('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $action = (string) ($_POST['action'] ?? '');
    
    if ($action === 'create_product') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $cost = (float) ($_POST['cost'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $gtin13 = trim((string) ($_POST['gtin13'] ?? ''));
        $specialities = trim((string) ($_POST['specialities'] ?? ''));
        $initialQty = (int) ($_POST['initial_qty'] ?? 0);
        
        // Validation
        if ($name === '') {
            $error = 'Le nom du produit est requis.';
        } elseif ($cost < 0) {
            $error = 'Le cout ne peut pas etre negatif.';
        } elseif ($price < 0) {
            $error = 'Le prix ne peut pas etre negatif.';
        } elseif ($initialQty < 0) {
            $error = 'La quantite initiale ne peut pas etre negative.';
        } elseif ($gtin13 !== '' && !preg_match('/^[0-9]{13}$/', $gtin13)) {
            $error = 'Le GTIN-13 doit contenir exactement 13 chiffres.';
        } else {
            try {
                db()->beginTransaction();
                
                // Handle photo upload
                $photoPath = null;
                if (!empty($_FILES['photo']['name'])) {
                    $uploadDir = dirname(__DIR__) . '/public/uploads/products/';
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            throw new RuntimeException('Impossible de creer le repertoire de telechargement.');
                        }
                    }
                    
                    // Check if directory is writable
                    if (!is_writable($uploadDir)) {
                        throw new RuntimeException('Le repertoire de telechargement n\'est pas accessible en ecriture.');
                    }
                    
                    $fileInfo = pathinfo($_FILES['photo']['name']);
                    $extension = strtolower($fileInfo['extension'] ?? '');
                    
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        throw new RuntimeException('Format d\'image non valide. Utilisez JPG, PNG, GIF ou WebP.');
                    }
                    
                    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
                        throw new RuntimeException('L\'image ne doit pas depasser 5Mo.');
                    }
                    
                    // Validate image dimensions (must be at least 1000x1000)
                    $imageInfo = getimagesize($_FILES['photo']['tmp_name']);
                    if ($imageInfo === false) {
                        throw new RuntimeException('Impossible de lire les dimensions de l\'image.');
                    }
                    
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    
                    if ($width < 1000 || $height < 1000) {
                        throw new RuntimeException('L\'image doit faire au moins 1000x1000 pixels. Dimensions actuelles: ' . $width . 'x' . $height);
                    }
                    
                    // Validate square aspect ratio (allow 1% tolerance)
                    $ratio = $width / $height;
                    if ($ratio < 0.99 || $ratio > 1.01) {
                        throw new RuntimeException('L\'image doit etre carree (ratio 1:1). Dimensions actuelles: ' . $width . 'x' . $height);
                    }
                    
                    $fileName = uniqid('product_', true) . '.' . $extension;
                    $filePath = $uploadDir . $fileName;
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $filePath)) {
                        $uploadError = error_get_last();
                        $errorMsg = $uploadError['message'] ?? 'Erreur inconnue';
                        throw new RuntimeException('Erreur lors du telechargement de l\'image: ' . $errorMsg);
                    }
                    
                    $photoPath = '/uploads/products/' . $fileName;
                }
                
                // Insert product
                $stmt = db()->prepare('INSERT INTO products (name, sku, photo, cost, price, gtin13, specialities, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([
                    $name,
                    $sku ?: null,
                    $photoPath,
                    $cost ?: null,
                    $price ?: null,
                    $gtin13 ?: null,
                    $specialities ?: null
                ]);
                
                $productId = (int) db()->lastInsertId();
                
                // Add initial stock
                if ($initialQty > 0) {
                    $stmt = db()->prepare('INSERT INTO stock_global (product_id, quantity) VALUES (?, ?)');
                    $stmt->execute([$productId, $initialQty]);
                }
                
                db()->commit();
                $success = 'Produit cree avec succes!';
                
                // Clear form data
                $_POST = [];
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                $error = 'Erreur lors de la creation: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Nouveau Produit';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="max-w-4xl mx-auto space-y-6">
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Nouveau Produit</h1>
      <p class="text-sm text-slate-500 mt-1">Ajouter un nouveau produit au catalogue</p>
    </div>
    <a href="/stock" class="h-10 px-4 rounded-xl border border-slate-200 text-sm text-slate-700 flex items-center gap-2 hover:bg-slate-50">
      <i class="fas fa-arrow-left"></i>
      <span>Retour</span>
    </a>
  </div>

  <?php if ($success !== ''): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 text-sm flex items-center gap-3">
      <i class="fas fa-check-circle text-xl"></i>
      <div>
        <strong>Succes!</strong> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <a href="/stock" class="ml-3 text-blue-600 hover:underline">Voir le stock</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 text-sm flex items-center gap-3">
      <i class="fas fa-exclamation-circle text-xl"></i>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <!-- Form -->
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <form method="post" enctype="multipart/form-data" class="divide-y divide-slate-100">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_product">
      
      <!-- Basic Information -->
      <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Informations de Base</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Nom du Produit <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   name="name" 
                   value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   required
                   placeholder="Ex: Huile d'olive vierge">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">SKU (Reference)</label>
            <input type="text" 
                   name="sku" 
                   value="<?= htmlspecialchars($_POST['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="Ex: HUILE-001">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">GTIN-13 (Code-barres)</label>
            <input type="text" 
                   name="gtin13" 
                   value="<?= htmlspecialchars($_POST['gtin13'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   pattern="[0-9]{13}"
                   maxlength="13"
                   class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="13 chiffres">
            <p class="text-xs text-slate-500 mt-1">Code-barres international (13 chiffres)</p>
          </div>
        </div>
      </div>

      <!-- Pricing -->
      <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Tarification</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Cout d'Achat (DT)</label>
            <div class="relative">
              <input type="number" 
                     name="cost" 
                     step="0.01"
                     min="0"
                     value="<?= htmlspecialchars($_POST['cost'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-12 rounded-xl border border-slate-200 pl-3 pr-12 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                     placeholder="0.00">
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">DT</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Prix d'achat fournisseur</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Prix de Vente (DT)</label>
            <div class="relative">
              <input type="number" 
                     name="price" 
                     step="0.01"
                     min="0"
                     value="<?= htmlspecialchars($_POST['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-12 rounded-xl border border-slate-200 pl-3 pr-12 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                     placeholder="0.00">
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">DT</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Prix de vente client</p>
          </div>
        </div>
      </div>

      <!-- Details -->
      <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-2">Specialites</label>
            <textarea 
              name="specialities" 
              rows="4"
              class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Ex: Premiere pression, Saveur legere, Bio..."><?= htmlspecialchars($_POST['specialities'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <p class="text-xs text-slate-500 mt-1">Caracteristiques, saveurs, certifications...</p>
          </div>
        </div>
      </div>

      <!-- Photo -->
      <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Photo du Produit</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Image</label>
            <div class="relative">
              <input type="file" 
                     name="photo" 
                     accept="image/jpeg,image/png,image/gif,image/webp"
                     class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium hover:file:bg-blue-100">
              <p class="text-xs text-slate-500 mt-1">Formats: JPG, PNG, GIF, WebP (min 1000x1000px, carree, max 5Mo)</p>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Apercu</label>
            <div id="photo-preview" class="w-full h-32 rounded-xl border-2 border-dashed border-slate-200 flex items-center justify-center bg-slate-50">
              <div class="text-center text-slate-400">
                <i class="fas fa-image text-3xl mb-2"></i>
                <p class="text-sm">Aucune image selectionnee</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Initial Stock -->
      <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Stock Initial</h2>
        
        <div class="max-w-xs">
          <label class="block text-sm font-medium text-slate-700 mb-2">Quantite Initiale</label>
          <input type="number" 
                 name="initial_qty" 
                 min="0"
                 value="<?= htmlspecialchars($_POST['initial_qty'] ?? '0', ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-12 rounded-xl border border-slate-200 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <p class="text-xs text-slate-500 mt-1">Quantite disponible dans le stock global</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-4">
        <a href="/stock" class="h-12 px-6 rounded-xl border border-slate-200 text-sm text-slate-700 font-medium flex items-center gap-2 hover:bg-slate-50">
          <i class="fas fa-times"></i>
          <span>Annuler</span>
        </a>
        <div class="flex gap-3">
          <button type="submit" class="h-12 px-8 rounded-xl bg-blue-600 text-white font-semibold flex items-center gap-2 hover:bg-blue-700 active:scale-[0.98] transition-all">
            <i class="fas fa-save"></i>
            <span>Creer le Produit</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Photo preview
document.querySelector('input[name="photo"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photo-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="w-full h-full object-cover rounded-xl">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<div class="text-center text-slate-400"><i class="fas fa-image text-3xl mb-2"></i><p class="text-sm">Aucune image selectionnee</p></div>';
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>