<?php

declare(strict_types=1);

require_role('admin');

$success = '';
$error = '';
$lastBackup = null;
$backupsDir = dirname(__DIR__) . '/storage/backups';

if (!is_dir($backupsDir)) {
    mkdir($backupsDir, 0755, true);
}

// Get last backup
$backupFiles = glob($backupsDir . '/reptrack_backup_*.sql');
if (!empty($backupFiles)) {
    $lastBackup = basename(max($backupFiles));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'download_backup') {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "reptrack_backup_{$timestamp}.sql";
            $filepath = $backupsDir . '/' . $filename;
            
            $tables = ['users', 'contacts', 'visits', 'governorates', 'cities', 'products', 'stock', 'visit_samples', 'stock_movements', 'stock_global', 'notifications', 'expenses'];
            
            $sql = "-- RepTrack Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $stmt = db()->query("SHOW CREATE TABLE `{$table}`");
                $result = $stmt->fetch();
                if ($result) {
                    $sql .= "-- Table structure for `{$table}`\n";
                    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $sql .= $result['Create Table'] . ";\n\n";
                    
                    // Get data
                    $stmt = db()->query("SELECT * FROM `{$table}`");
                    $rows = $stmt->fetchAll();
                    
                    if (!empty($rows)) {
                        $columns = array_keys($rows[0]);
                        $sql .= "-- Data for `{$table}`\n";
                        
                        foreach ($rows as $row) {
                            $values = [];
                            foreach ($columns as $col) {
                                $val = $row[$col];
                                if ($val === null) {
                                    $values[] = 'NULL';
                                } elseif (is_numeric($val)) {
                                    $values[] = $val;
                                } else {
                                    $values[] = "'" . addslashes((string)$val) . "'";
                                }
                            }
                            $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                        }
                        $sql .= "\n";
                    }
                }
            }
            
            if (file_put_contents($filepath, $sql)) {
                // Send file to browser
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            } else {
                $error = 'Impossible de creer le fichier de sauvegarde.';
            }
        } elseif ($action === 'restore_backup') {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Erreur lors du telechargement du fichier.';
            } else {
                $filepath = $_FILES['backup_file']['tmp_name'];
                $sql = file_get_contents($filepath);
                
                if ($sql === false) {
                    $error = 'Impossible de lire le fichier de sauvegarde.';
                } else {
                    // Split SQL into statements
                    $statements = explode(';', $sql);
                    $successCount = 0;
                    
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement) && !str_starts_with($statement, '--')) {
                            try {
                                db()->exec($statement);
                                $successCount++;
                            } catch (Throwable $e) {
                                // Continue on error
                            }
                        }
                    }
                    
                    $success = "Restauration terminee. {$successCount} commandes executees.";
                }
            }
        } elseif ($action === 'backup_google_drive') {
            $error = 'Fonctionnalite Google Drive non configuree. Veuillez configurer les API Google dans la page Configuration.';
        } elseif ($action === 'backup_dropbox') {
            $error = 'Fonctionnalite Dropbox non configuree. Veuillez configurer les API Dropbox dans la page Configuration.';
        }
    } catch (Throwable $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

$page_title = 'Sauvegarde & Restauration';
$show_back = true;
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="space-y-4">
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

  <!-- Backup Options -->
  <div class="bg-white rounded-2xl shadow-sm p-5">
    <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <i class="fas fa-cloud-upload-alt text-blue-600"></i>
      Sauvegarde de la base de donnees
    </h2>
    
    <div class="space-y-3">
      <form method="post" class="flex gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="download_backup">
        <button type="submit" class="flex-1 h-12 rounded-xl bg-blue-600 text-white font-semibold flex items-center justify-center gap-2 hover:bg-blue-700">
          <i class="fas fa-download"></i>
          <span>Telecharger la sauvegarde</span>
        </button>
      </form>

      <form method="post" class="flex gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="backup_google_drive">
        <button type="submit" class="flex-1 h-12 rounded-xl bg-yellow-500 text-white font-semibold flex items-center justify-center gap-2 hover:bg-yellow-600">
          <i class="fab fa-google-drive"></i>
          <span>Sauvegarder sur Google Drive</span>
        </button>
      </form>

      <form method="post" class="flex gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="backup_dropbox">
        <button type="submit" class="flex-1 h-12 rounded-xl bg-blue-500 text-white font-semibold flex items-center justify-center gap-2 hover:bg-blue-600">
          <i class="fab fa-dropbox"></i>
          <span>Sauvegarder sur Dropbox</span>
        </button>
      </form>
    </div>

    <?php if ($lastBackup): ?>
      <div class="mt-4 p-3 bg-slate-50 rounded-xl text-sm">
        <div class="flex items-center gap-2 text-slate-600">
          <i class="fas fa-clock"></i>
          <span>Derniere sauvegarde: <?= htmlspecialchars($lastBackup, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Restore Options -->
  <div class="bg-white rounded-2xl shadow-sm p-5">
    <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <i class="fas fa-cloud-download-alt text-green-600"></i>
      Restauration de la base de donnees
    </h2>
    
    <form method="post" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="restore_backup">
      
      <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center">
        <input type="file" name="backup_file" accept=".sql" id="backup_file" class="hidden">
        <label for="backup_file" class="cursor-pointer">
          <div class="flex flex-col items-center gap-2">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
              <i class="fas fa-upload text-slate-500 text-xl"></i>
            </div>
            <div>
              <span class="text-sm font-medium text-slate-900">Cliquez pour selectionner un fichier</span>
              <p class="text-xs text-slate-500 mt-1">Fichiers . uniquement</p>
            </div>
          </div>
        </label>
        <div id="file_name" class="text-sm text-slate-600 mt-3 hidden"></div>
      </div>

      <button type="submit" class="w-full h-12 rounded-xl bg-green-600 text-white font-semibold flex items-center justify-center gap-2 hover:bg-green-700">
        <i class="fas fa-restore"></i>
        <span>Restaurer la base de donnees</span>
      </button>
    </form>

    <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm">
      <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-red-600 mt-0.5"></i>
        <div>
          <h3 class="font-semibold text-red-900">Attention</h3>
          <p class="text-red-800 mt-1">La restauration remplacera toutes les donnees actuelles. Il est recommande de faire une sauvegarde avant de restaurer.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Cloud Storage Setup Info -->
  <div class="bg-white rounded-2xl shadow-sm p-5">
    <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <i class="fas fa-info-circle text-purple-600"></i>
      Configuration du stockage cloud
    </h2>
    
    <div class="space-y-3 text-sm">
      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fab fa-google-drive text-yellow-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">Google Drive</h3>
          <p class="text-slate-600 mt-1">Pour utiliser Google Drive, creez un projet dans Google Cloud Console, activez l'API Drive, et ajoutez les credentials OAuth 2.0 dans la configuration.</p>
          <a href="/admin/config" class="text-blue-600 text-xs mt-2 inline-block">Configurer maintenant</a>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fab fa-dropbox text-blue-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">Dropbox</h3>
          <p class="text-slate-600 mt-1">Pour utiliser Dropbox, creez une app dans Dropbox App Console, activez les permissions appropriees, et ajoutez l'App Key et l'App Secret dans la configuration.</p>
          <a href="/admin/config" class="text-blue-600 text-xs mt-2 inline-block">Configurer maintenant</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('backup_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || '';
    const fileNameDiv = document.getElementById('file_name');
    
    if (fileName) {
        fileNameDiv.textContent = 'Fichier selectionne: ' + fileName;
        fileNameDiv.classList.remove('hidden');
    } else {
        fileNameDiv.classList.add('hidden');
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>