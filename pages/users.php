<?php

declare(strict_types=1);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'rep');
    $active = isset($_POST['active']) ? 1 : 0;

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } elseif (!in_array($role, ['admin', 'rep'], true)) {
        $error = 'Role invalide.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare('INSERT INTO users (name, email, password, role, active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, $role, $active]);
            $success = 'Utilisateur créé.';
        } catch (Throwable $e) {
            $error = 'Impossible de créer l\'utilisateur (email déjà utilisé?).';
        }
    }
}

try {
    $users = db()->query('SELECT id, name, email, role, active, created_at FROM users ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    $users = [];
}

$page_title = 'Utilisateurs';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Créer un utilisateur</h2>

    <?php if ($success !== ''): ?>
      <div class="mt-3 p-3 rounded-xl bg-green-50 text-green-700 text-sm">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="mt-3 p-3 rounded-xl bg-red-50 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-4 grid grid-cols-1 gap-3">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-medium text-slate-700">Nom</label>
        <input type="text" name="name" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Mot de passe</label>
        <input type="password" name="password" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Role</label>
        <select name="role" class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3">
          <option value="rep">Rep</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="active" checked>
        Actif
      </label>
      <button type="submit" class="h-12 rounded-xl bg-blue-600 text-white font-semibold active:scale-[0.98]">
        Enregistrer
      </button>
    </form>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h2 class="text-base font-semibold text-slate-900">Liste des utilisateurs</h2>
    <div class="mt-3 space-y-3">
      <?php if (empty($users)): ?>
        <p class="text-sm text-slate-600">Aucun utilisateur.</p>
      <?php else: ?>
        <?php foreach ($users as $user): ?>
          <div class="flex items-center justify-between border border-slate-100 rounded-xl px-3 py-2">
            <div>
              <div class="text-sm font-medium text-slate-900">
                <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div class="text-xs text-slate-500">
                <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>
              </div>
            </div>
            <div class="text-xs text-slate-500 text-right">
              <div><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></div>
              <div><?= (int) $user['active'] === 1 ? 'Actif' : 'Inactif' ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
