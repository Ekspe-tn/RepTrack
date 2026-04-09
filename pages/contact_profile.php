<?php

declare(strict_types=1);

$contactId = (int) ($_GET['id'] ?? 0);
$user = current_user();
$role = $user['role'] ?? 'rep';

$success = '';
$error = '';

if ($contactId <= 0) {
    $page_title = 'Contact';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="bg-white rounded-2xl shadow-sm p-4 text-sm text-slate-600">Contact introuvable.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_gps') {
        $postedId = (int) ($_POST['contact_id'] ?? 0);
        $latRaw = trim((string) ($_POST['latitude'] ?? ''));
        $lngRaw = trim((string) ($_POST['longitude'] ?? ''));

        if ($postedId !== $contactId) {
            $error = 'Contact invalide.';
        } elseif ($latRaw === '' || $lngRaw === '') {
            $error = 'Coordonnees GPS manquantes.';
        } elseif (!is_numeric($latRaw) || !is_numeric($lngRaw)) {
            $error = 'Coordonnees GPS invalides.';
        } else {
            $lat = (float) $latRaw;
            $lng = (float) $lngRaw;
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $error = 'Coordonnees GPS hors limites.';
            } else {
                try {
                    $stmt = db()->prepare('UPDATE contacts SET latitude = ?, longitude = ? WHERE id = ?');
                    $stmt->execute([$lat, $lng, $contactId]);
                    $success = 'Coordonnees GPS enregistrees.';
                } catch (Throwable $e) {
                    $error = 'Impossible de mettre a jour les coordonnees.';
                }
            }
        }
    }
}

try {
    $stmt = db()->prepare('SELECT c.*, g.name_fr AS governorate_name, ci.name_fr AS city_name, u.name AS rep_name
        FROM contacts c
        JOIN governorates g ON g.id = c.governorate_id
        JOIN cities ci ON ci.id = c.city_id
        LEFT JOIN users u ON u.id = c.assigned_rep_id
        WHERE c.id = ?
        LIMIT 1');
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch();
} catch (Throwable $e) {
    $contact = null;
}

if (!$contact) {
    $page_title = 'Contact';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="bg-white rounded-2xl shadow-sm p-4 text-sm text-slate-600">Contact introuvable.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$visitWhere = 'WHERE v.contact_id = ?';
$visitParams = [$contactId];
if ($role !== 'admin') {
    $visitWhere .= ' AND v.user_id = ?';
    $visitParams[] = $user['id'];
}

$visitsTotal = 0;
$visits30 = 0;
$lastVisit = null;
$totalSamples = 0;
$recentVisits = [];
$from30 = date('Y-m-d H:i:s', strtotime('-30 days'));

try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM visits v $visitWhere");
    $stmt->execute($visitParams);
    $visitsTotal = (int) $stmt->fetchColumn();
} catch (Throwable $e) {
    $visitsTotal = 0;
}

try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM visits v $visitWhere AND v.created_at >= ?");
    $stmt->execute(array_merge($visitParams, [$from30]));
    $visits30 = (int) $stmt->fetchColumn();
} catch (Throwable $e) {
    $visits30 = 0;
}

try {
    $stmt = db()->prepare("SELECT MAX(v.created_at) FROM visits v $visitWhere");
    $stmt->execute($visitParams);
    $lastVisit = $stmt->fetchColumn() ?: null;
} catch (Throwable $e) {
    $lastVisit = null;
}

try {
    $stmt = db()->prepare("SELECT COALESCE(SUM(vs.quantity), 0) FROM visit_samples vs
        JOIN visits v ON v.id = vs.visit_id
        $visitWhere");
    $stmt->execute($visitParams);
    $totalSamples = (int) $stmt->fetchColumn();
} catch (Throwable $e) {
    $totalSamples = 0;
}

try {
    $stmt = db()->prepare("SELECT v.id, v.visit_type, v.created_at, u.name AS rep_name
        FROM visits v
        JOIN users u ON u.id = v.user_id
        $visitWhere
        ORDER BY v.created_at DESC
        LIMIT 8");
    $stmt->execute($visitParams);
    $recentVisits = $stmt->fetchAll();
} catch (Throwable $e) {
    $recentVisits = [];
}

$typeLabels = [
    'doctor' => 'Medecin',
    'pharmacy' => 'Pharmacie',
    'parapharmacie' => 'Parapharmacie',
    'depot' => 'Depot',
    'clinic' => 'Clinique',
    'hospital' => 'Hopital',
];
$typeLabel = $typeLabels[$contact['type'] ?? ''] ?? ($contact['type'] ?? '-');

$page_title = 'Profil contact';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="space-y-6">
    <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($contact['name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="text-sm text-slate-500 mt-1">
        <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
        · <?= htmlspecialchars($contact['governorate_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
        · <?= htmlspecialchars($contact['city_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>
    <div class="flex gap-3">
      <a href="/contacts_new?edit=<?= (int) $contactId ?>" class="h-10 px-4 rounded-xl border border-slate-200 text-sm text-slate-700 flex items-center gap-2 hover:bg-slate-50">
        <i class="fas fa-pen"></i>
        <span>Modifier</span>
      </a>
      <a href="/visits/new?contact_id=<?= (int) $contactId ?>" class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-semibold flex items-center gap-2 hover:bg-blue-700">
        <i class="fas fa-calendar-plus"></i>
        <span>Nouvelle visite</span>
      </a>
      <?php if ($role === 'admin' && $visitsTotal === 0): ?>
      <button type="button" onclick="deleteContact()" class="h-10 px-4 rounded-xl bg-red-600 text-white text-sm font-semibold flex items-center gap-2 hover:bg-red-700">
        <i class="fas fa-trash"></i>
        <span>Supprimer</span>
      </button>
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

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Visites totales</div>
          <div class="text-2xl font-bold text-slate-900 mt-1"><?= $visitsTotal ?></div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
          <i class="fas fa-route text-blue-600 text-xl"></i>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Visites 30j</div>
          <div class="text-2xl font-bold text-slate-900 mt-1"><?= $visits30 ?></div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
          <i class="fas fa-chart-line text-emerald-600 text-xl"></i>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Derniere visite</div>
          <div class="text-lg font-bold text-slate-900 mt-1">
            <?= htmlspecialchars($lastVisit ?: '-', ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
          <i class="fas fa-clock text-purple-600 text-xl"></i>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">Echantillons</div>
          <div class="text-2xl font-bold text-slate-900 mt-1"><?= $totalSamples ?></div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
          <i class="fas fa-box-open text-amber-600 text-xl"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl shadow-sm p-5 lg:col-span-2">
      <h2 class="text-base font-semibold text-slate-900 mb-4">Informations</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-xs text-slate-500">Telephone</div>
          <?php if (!empty($contact['phone'])): ?>
            <a href="tel:<?= htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8') ?>" 
               class="text-blue-600 font-medium hover:text-blue-800">
              <i class="fas fa-phone mr-1"></i>
              <?= htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php else: ?>
            <div class="text-slate-900 font-medium">-</div>
          <?php endif; ?>
        </div>
        <div>
          <div class="text-xs text-slate-500">Email</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars($contact['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500">Personne a contacter</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars($contact['contact_person'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500">Delegue assigne</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars($contact['rep_name'] ?: 'Non assigne', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="md:col-span-2">
          <div class="text-xs text-slate-500">Adresse</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars($contact['address'] ?: '-', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php if (!empty($contact['specialty'])): ?>
        <div class="md:col-span-2">
          <div class="text-xs text-slate-500">Specialite</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars($contact['specialty'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($contact['notes'])): ?>
        <div class="md:col-span-2">
          <div class="text-xs text-slate-500">Notes</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars($contact['notes'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4">Coordonnees GPS</h2>
      <div class="hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 mb-3" data-gps-helper>
        Autorisation GPS requise. 
        <a href="/help/gps" class="font-semibold underline">Guide rapide</a>
      </div>
      <div class="space-y-3 text-sm">
        <div>
          <div class="text-xs text-slate-500">Latitude</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars((string) ($contact['latitude'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500">Longitude</div>
          <div class="text-slate-900 font-medium"><?= htmlspecialchars((string) ($contact['longitude'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php if (!empty($contact['latitude']) && !empty($contact['longitude'])): ?>
          <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode((string) $contact['latitude']) ?>,<?= urlencode((string) $contact['longitude']) ?>"
             class="inline-flex items-center gap-2 text-xs text-blue-600 hover:text-blue-800">
            <i class="fas fa-location-dot"></i>
            <span>Ouvrir sur la carte</span>
          </a>
        <?php endif; ?>
      </div>
      <form method="post" class="mt-4 space-y-3" data-gps-form>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_gps">
        <input type="hidden" name="contact_id" value="<?= (int) $contactId ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-slate-500">Latitude</label>
            <input type="number" step="0.0000001" name="latitude"
                   value="<?= htmlspecialchars((string) ($contact['latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm" data-gps-lat>
          </div>
          <div>
            <label class="block text-xs text-slate-500">Longitude</label>
            <input type="number" step="0.0000001" name="longitude"
                   value="<?= htmlspecialchars((string) ($contact['longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="mt-1 w-full h-10 rounded-lg border border-slate-200 px-3 text-sm" data-gps-lng>
          </div>
        </div>
        <div class="flex gap-2">
          <button type="button" class="flex-1 h-11 rounded-xl bg-slate-900 text-white text-sm font-semibold flex items-center justify-center gap-2 hover:bg-slate-800" data-gps-button>
          <i class="fas fa-crosshairs"></i>
          <span>Obtenir GPS</span>
        </button>
          <button type="submit" class="h-11 px-4 rounded-xl border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
            Enregistrer
          </button>
        </div>
        <div class="text-xs text-slate-500" data-gps-status></div>
      </form>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-5">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-slate-900">Dernieres visites</h2>
      <a href="/visits?contact_id=<?= (int) $contactId ?>" class="text-xs text-blue-600">Voir tout</a>
    </div>
    <?php if (empty($recentVisits)): ?>
      <p class="text-sm text-slate-600 mt-3">Aucune visite pour ce contact.</p>
    <?php else: ?>
      <div class="mt-3 space-y-2">
        <?php foreach ($recentVisits as $visit): ?>
          <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2">
            <div>
              <a href="/visits/view?id=<?= (int) $visit['id'] ?>" class="text-sm font-medium text-slate-900 hover:text-blue-600">
                Visite #<?= (int) $visit['id'] ?>
              </a>
              <div class="text-xs text-slate-500"><?= htmlspecialchars($visit['visit_type'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($visit['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="text-xs text-slate-500"><?= htmlspecialchars($visit['rep_name'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($role === 'admin' && $visitsTotal === 0): ?>
<script>
function deleteContact() {
    if (!confirm('Etes-vous sur de vouloir supprimer ce contact ? Cette action est irreversible.')) {
        return;
    }
    
    const contactId = <?= (int) $contactId ?>;
    
    fetch('/api/delete_contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `contact_id=${contactId}&csrf_token=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Contact supprime avec succes.');
            window.location.href = '/contacts';
        } else {
            alert(data.error || 'Erreur lors de la suppression du contact.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la suppression du contact.');
    });
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
