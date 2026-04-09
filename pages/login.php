<?php

declare(strict_types=1);

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email et mot de passe requis.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, name, email, password, role, active FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !(int) $user['active']) {
                $error = 'Identifiants invalides.';
            } elseif (!password_verify($password, (string) $user['password'])) {
                $error = 'Identifiants invalides.';
            } else {
                login_user($user);
                header('Location: /dashboard');
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Erreur base de donnees. Verifiez la connexion.';
        }
    }
}

$page_title = 'Connexion';
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto mt-6">
  <div class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-lg font-semibold text-slate-900">Bienvenue</h2>
    <p class="text-sm text-slate-500 mt-1">Connectez-vous pour continuer</p>

    <?php if ($error !== ''): ?>
      <div class="mt-4 p-3 rounded-xl bg-red-50 text-red-700 text-sm">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
               class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3"
               placeholder="name@example.com" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Mot de passe</label>
        <input type="password" name="password"
               class="mt-1 w-full h-12 rounded-xl border border-slate-200 px-3"
               placeholder="********" required>
      </div>
      <button type="submit" class="w-full h-12 rounded-xl bg-blue-600 text-white font-semibold active:scale-[0.98]">
        Se connecter
      </button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
