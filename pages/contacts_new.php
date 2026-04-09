<?php

declare(strict_types=1);

$success = '';
$error = '';
$editing = isset($_GET['edit']);
$editContact = null;

$user = current_user();
try {
    if ($user['role'] === 'rep') {
        // Get only governorates assigned to this rep
        $stmt = db()->prepare('SELECT DISTINCT g.id, g.name_fr 
            FROM governorates g
            JOIN users u ON (FIND_IN_SET(g.id, u.governorates) > 0 OR FIND_IN_SET(g.name_fr, u.governorates) > 0)
            WHERE u.id = ?
            ORDER BY g.name_fr');
        $stmt->execute([$user['id']]);
        $governorates = $stmt->fetchAll();
    } else {
        // Admins see all governorates
        $governorates = db()->query('SELECT id, name_fr FROM governorates ORDER BY name_fr')->fetchAll();
    }
} catch (Throwable $e) {
    $governorates = [];
}

try {
    $reps = db()->query("SELECT id, name FROM users WHERE role = 'rep' AND active = 1 ORDER BY name")->fetchAll();
} catch (Throwable $e) {
    $reps = [];
}

if ($editing) {
    $editId = (int) ($_GET['edit'] ?? 0);
    if ($editId > 0) {
        try {
            $stmt = db()->prepare('SELECT * FROM contacts WHERE id = ? LIMIT 1');
            $stmt->execute([$editId]);
            $editContact = $stmt->fetch();
        } catch (Throwable $e) {
            $editContact = null;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $payload = [
        'type' => (string) ($_POST['type'] ?? 'doctor'),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'specialty' => trim((string) ($_POST['specialty'] ?? '')),
        'establishment' => trim((string) ($_POST['establishment'] ?? '')),
        'governorate_id' => (int) ($_POST['governorate_id'] ?? 0),
        'city_id' => (int) ($_POST['city_id'] ?? 0),
        'address' => trim((string) ($_POST['address'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'contact_person' => trim((string) ($_POST['contact_person'] ?? '')),
        'status' => (string) ($_POST['status'] ?? 'independent'),
        'potential' => (string) ($_POST['potential'] ?? 'B'),
        'client_type' => (string) ($_POST['client_type'] ?? 'local'),
        'collaboration_history' => (string) ($_POST['collaboration_history'] ?? 'new'),
        'plv_present' => isset($_POST['plv_present']) ? 1 : 0,
        'team_engagement' => (string) ($_POST['team_engagement'] ?? 'medium'),
        'specific_needs' => trim((string) ($_POST['specific_needs'] ?? '')),
        'visit_frequency_days' => (int) ($_POST['visit_frequency_days'] ?? 30),
        'assigned_rep_id' => (int) ($_POST['assigned_rep_id'] ?? 0),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
        'active' => isset($_POST['active']) ? 1 : 0,
    ];

    if ($payload['name'] === '' || $payload['governorate_id'] <= 0 || $payload['city_id'] <= 0 || ($payload['type'] === 'doctor' && $payload['specialty'] === '')) {
        $error = 'Nom, gouvernorat, delegation et specialite (medecin) sont obligatoires.';
    } else {
        try {
            $addedBy = current_user()['id'] ?? null;

            if (!empty($_POST['id'])) {
                $stmt = db()->prepare('UPDATE contacts SET type = ?, name = ?, specialty = ?, establishment = ?, governorate_id = ?, city_id = ?, address = ?, phone = ?, email = ?, contact_person = ?, status = ?, potential = ?, client_type = ?, collaboration_history = ?, plv_present = ?, team_engagement = ?, specific_needs = ?, visit_frequency_days = ?, assigned_rep_id = ?, notes = ?, active = ? WHERE id = ?');
                $stmt->execute([
                    $payload['type'],
                    $payload['name'],
                    $payload['specialty'] ?: null,
                    $payload['establishment'] ?: null,
                    $payload['governorate_id'],
                    $payload['city_id'],
                    $payload['address'] ?: null,
                    $payload['phone'] ?: null,
                    $payload['email'] ?: null,
                    $payload['contact_person'] ?: null,
                    $payload['status'],
                    $payload['potential'],
                    $payload['client_type'],
                    $payload['collaboration_history'],
                    $payload['plv_present'],
                    $payload['team_engagement'],
                    $payload['specific_needs'] ?: null,
                    $payload['visit_frequency_days'],
                    $payload['assigned_rep_id'] ?: null,
                    $payload['notes'] ?: null,
                    $payload['active'],
                    (int) $_POST['id'],
                ]);
                $success = 'Contact mis a jour.';
            } else {
                $stmt = db()->prepare('INSERT INTO contacts (type, name, specialty, establishment, governorate_id, city_id, address, phone, email, contact_person, status, potential, client_type, collaboration_history, plv_present, team_engagement, specific_needs, visit_frequency_days, assigned_rep_id, added_by, notes, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $payload['type'],
                    $payload['name'],
                    $payload['specialty'] ?: null,
                    $payload['establishment'] ?: null,
                    $payload['governorate_id'],
                    $payload['city_id'],
                    $payload['address'] ?: null,
                    $payload['phone'] ?: null,
                    $payload['email'] ?: null,
                    $payload['contact_person'] ?: null,
                    $payload['status'],
                    $payload['potential'],
                    $payload['client_type'],
                    $payload['collaboration_history'],
                    $payload['plv_present'],
                    $payload['team_engagement'],
                    $payload['specific_needs'] ?: null,
                    $payload['visit_frequency_days'],
                    $payload['assigned_rep_id'] ?: null,
                    $addedBy,
                    $payload['notes'] ?: null,
                    $payload['active'],
                ]);
                $success = 'Contact cree.';
            }
        } catch (Throwable $e) {
            $error = 'Impossible d\'enregistrer le contact.';
        }
    }
}

$page_title = $editing ? 'Modifier un contact' : 'Nouveau contact';
$specialtyOptions = [
    'M&eacute;decin g&eacute;n&eacute;raliste',
    'M&eacute;decine interne',
    'Cardiologue',
    'Pneumologue',
    'Gastro-ent&eacute;rologue',
    'Endocrinologue',
    'N&eacute;phrologue',
    'H&eacute;matologue',
    'Oncologue',
    'Infectiologue',
    'Dermatologue',
    'Rhumatologue',
    'Neurologue',
    'Psychiatre',
    'P&eacute;diatre',
    'Gyn&eacute;cologue',
    'ORL',
    'M&eacute;decin du travail',
    'M&eacute;decin du sport',
    'Urgentiste',
    'Orthop&eacute;diste',
];
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
  <div class="mb-4">
    <a href="/contacts" class="text-sm text-blue-600 hover:text-blue-800">
      &larr; Retour aux contacts
    </a>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-6">
    <h1 class="text-lg font-semibold text-slate-900 mb-6"><?= $editing ? 'Modifier un contact' : 'Nouveau contact' ?></h1>

    <?php if ($success !== ''): ?>
      <div class="mb-4 p-3 rounded-xl bg-green-50 text-green-700 text-sm">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-6" data-city-group>
      <?= csrf_field() ?>
      <?php if ($editing && $editContact): ?>
        <input type="hidden" name="id" value="<?= (int) $editContact['id'] ?>">
      <?php endif; ?>
      
      <!-- Basic Information -->
      <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Informations de base</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Type</label>
            <select name="type" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3" required data-type-select>
              <?php
              $types = ['doctor' => 'Medecin', 'pharmacy' => 'Pharmacie', 'parapharmacie' => 'Parapharmacie', 'depot' => 'Depot', 'clinic' => 'Clinique', 'hospital' => 'Hopital'];
              $selectedType = $editContact['type'] ?? 'doctor';
              foreach ($types as $value => $label):
                  $selected = $selectedType === $value ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$label</option>";
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Nom</label>
            <input type="text" name="name" value="<?= htmlspecialchars($editContact['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3" required>
          </div>
      </div>
        <div data-specialty-field class="hidden mt-4">
          <label class="block text-sm font-medium text-slate-700">Specialite</label>
          <select name="specialty" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3" data-specialty-input>
            <option value="">Choisir une specialite</option>
            <?php
            $selectedSpecialty = (string) ($editContact['specialty'] ?? '');
            foreach ($specialtyOptions as $optionHtml):
                $optionValue = html_entity_decode($optionHtml, ENT_QUOTES, 'UTF-8');
                $selected = $selectedSpecialty === $optionValue ? 'selected' : '';
                echo '<option value="' . $optionHtml . '" ' . $selected . '>' . $optionHtml . '</option>';
            endforeach;
            ?>
          </select>
        </div>
      </div>

      <!-- Location -->
      <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Localisation</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Gouvernorat</label>
            <select name="governorate_id" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3" data-governorate-select required>
              <option value="">Choisir</option>
              <?php
              $selectedGov = (int) ($editContact['governorate_id'] ?? 0);
              foreach ($governorates as $gov):
                  $selected = $selectedGov === (int) $gov['id'] ? 'selected' : '';
                  echo '<option value="' . (int) $gov['id'] . '" ' . $selected . '>' . htmlspecialchars($gov['name_fr'], ENT_QUOTES, 'UTF-8') . '</option>';
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Delegation</label>
            <select name="city_id" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3" data-city-select data-city-selected="<?= (int) ($editContact['city_id'] ?? 0) ?>" required>
              <option value="">Choisir</option>
            </select>
          </div>
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium text-slate-700">Adresse</label>
          <input type="text" name="address" value="<?= htmlspecialchars($editContact['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
        </div>
      </div>

      <!-- Contact Info -->
      <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Contact</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Telephone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($editContact['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editContact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
          </div>
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium text-slate-700">Personne a contacter</label>
          <input type="text" name="contact_person" value="<?= htmlspecialchars($editContact['contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium text-slate-700">Assigner a</label>
          <select name="assigned_rep_id" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
            <option value="">Non assigne</option>
            <?php
            $selectedRep = (int) ($editContact['assigned_rep_id'] ?? 0);
            foreach ($reps as $rep):
                $selected = $selectedRep === (int) $rep['id'] ? 'selected' : '';
                echo '<option value="' . (int) $rep['id'] . '" ' . $selected . '>' . htmlspecialchars($rep['name'], ENT_QUOTES, 'UTF-8') . '</option>';
            endforeach;
            ?>
          </select>
        </div>
      </div>

      <!-- Segmentation -->
      <div class="border-t border-slate-100 pt-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Segmentation</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Statut</label>
            <select name="status" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
              <?php
              $statusOptions = ['chain' => 'Chain', 'independent' => 'Independent', 'group' => 'Group', 'hospital_public' => 'Hospital public', 'clinic_private' => 'Clinic privee'];
              $selectedStatus = $editContact['status'] ?? 'independent';
              foreach ($statusOptions as $value => $label):
                  $selected = $selectedStatus === $value ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$label</option>";
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Potentiel</label>
            <select name="potential" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
              <?php
              $potentialOptions = ['A', 'B', 'C'];
              $selectedPotential = $editContact['potential'] ?? 'B';
              foreach ($potentialOptions as $value):
                  $selected = $selectedPotential === $value ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$value</option>";
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Type de client</label>
            <select name="client_type" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
              <?php
              $clientOptions = ['local' => 'Local', 'tourist' => 'Tourist', 'specialized' => 'Specialise', 'mixed' => 'Mixte'];
              $selectedClient = $editContact['client_type'] ?? 'local';
              foreach ($clientOptions as $value => $label):
                  $selected = $selectedClient === $value ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$label</option>";
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Historique</label>
            <select name="collaboration_history" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
              <?php
              $historyOptions = ['new' => 'Nouveau', 'occasional' => 'Occasionnel', 'regular' => 'Regulier', 'key_account' => 'Key account'];
              $selectedHistory = $editContact['collaboration_history'] ?? 'new';
              foreach ($historyOptions as $value => $label):
                  $selected = $selectedHistory === $value ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$label</option>";
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Engagement</label>
            <select name="team_engagement" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
              <?php
              $engagementOptions = ['low' => 'Faible', 'medium' => 'Moyen', 'high' => 'Eleve'];
              $selectedEngagement = $editContact['team_engagement'] ?? 'medium';
              foreach ($engagementOptions as $value => $label):
                  $selected = $selectedEngagement === $value ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$label</option>";
              endforeach;
              ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Frequence de visite (jours)</label>
            <input type="number" name="visit_frequency_days" value="<?= htmlspecialchars((string) ($editContact['visit_frequency_days'] ?? 30), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full h-11 rounded-lg border border-slate-200 px-3">
          </div>
        </div>
        <div class="mt-4 flex items-center gap-6">
          <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="plv_present" <?= !empty($editContact['plv_present']) ? 'checked' : '' ?>>
            PLV presente
          </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Besoins specifiques</label>
            <textarea name="specific_needs" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" rows="2"><?= htmlspecialchars($editContact['specific_needs'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" rows="2"><?= htmlspecialchars($editContact['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between gap-4">
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" name="active" <?= !isset($editContact['active']) || (int) $editContact['active'] === 1 ? 'checked' : '' ?>>
          Actif
        </label>
        <div class="flex gap-3">
          <a href="/contacts" class="h-11 px-8 rounded-lg border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition-colors">
            Annuler
          </a>
          <button type="submit" class="h-11 px-8 rounded-lg bg-blue-600 text-white font-semibold active:scale-[0.98] transition-transform">
            <?= $editing ? 'Mettre a jour' : 'Enregistrer' ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
