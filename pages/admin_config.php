<?php

declare(strict_types=1);

require_role('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save_config') {
            $mailgunKey = trim((string) ($_POST['mailgun_api_key'] ?? ''));
            $mailgunDomain = trim((string) ($_POST['mailgun_domain'] ?? ''));
            $sesKey = trim((string) ($_POST['ses_access_key'] ?? ''));
            $sesSecret = trim((string) ($_POST['ses_secret_key'] ?? ''));
            $sesRegion = trim((string) ($_POST['ses_region'] ?? ''));
            $sendgridKey = trim((string) ($_POST['sendgrid_api_key'] ?? ''));
            $googleMapsKey = trim((string) ($_POST['google_maps_api_key'] ?? ''));
            $twilioSid = trim((string) ($_POST['twilio_account_sid'] ?? ''));
            $twilioToken = trim((string) ($_POST['twilio_auth_token'] ?? ''));
            $twilioPhone = trim((string) ($_POST['twilio_phone_number'] ?? ''));
            $fcmServerKey = trim((string) ($_POST['fcm_server_key'] ?? ''));
            $fcmSenderId = trim((string) ($_POST['fcm_sender_id'] ?? ''));
            $openaiKey = trim((string) ($_POST['openai_api_key'] ?? ''));
            $stripeKey = trim((string) ($_POST['stripe_secret_key'] ?? ''));
            $stripePubKey = trim((string) ($_POST['stripe_publishable_key'] ?? ''));
            
            $envPath = dirname(__DIR__) . '/.env';
            $envContent = '';
            
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
            }
            
            $vars = [
                'MAILGUN_API_KEY' => $mailgunKey,
                'MAILGUN_DOMAIN' => $mailgunDomain,
                'SES_ACCESS_KEY' => $sesKey,
                'SES_SECRET_KEY' => $sesSecret,
                'SES_REGION' => $sesRegion,
                'SENDGRID_API_KEY' => $sendgridKey,
                'GOOGLE_MAPS_API_KEY' => $googleMapsKey,
                'TWILIO_ACCOUNT_SID' => $twilioSid,
                'TWILIO_AUTH_TOKEN' => $twilioToken,
                'TWILIO_PHONE_NUMBER' => $twilioPhone,
                'FCM_SERVER_KEY' => $fcmServerKey,
                'FCM_SENDER_ID' => $fcmSenderId,
                'OPENAI_API_KEY' => $openaiKey,
                'STRIPE_SECRET_KEY' => $stripeKey,
                'STRIPE_PUBLISHABLE_KEY' => $stripePubKey,
            ];
            
            foreach ($vars as $key => $value) {
                $pattern = "/^" . preg_quote($key, '/') . "=.*/m";
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "$key=" . ($value !== '' ? '"' . $value . '"' : ''), $envContent);
                } elseif ($value !== '') {
                    $envContent .= "\n$key=\"$value\"";
                }
            }
            
            if (file_put_contents($envPath, $envContent)) {
                $success = 'Configuration enregistree avec succes.';
            } else {
                $error = 'Impossible d\'enregistrer la configuration.';
            }
        }
    } catch (Throwable $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

$envValues = [];
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            list($key, $value) = $parts;
            if ($key && $value !== '') {
                $envValues[trim($key)] = trim($value, '"\'');
            }
        }
    }
}

$mailgunKey = $envValues['MAILGUN_API_KEY'] ?? '';
$mailgunDomain = $envValues['MAILGUN_DOMAIN'] ?? '';
$sesKey = $envValues['SES_ACCESS_KEY'] ?? '';
$sesSecret = $envValues['SES_SECRET_KEY'] ?? '';
$sesRegion = $envValues['SES_REGION'] ?? 'us-east-1';
$sendgridKey = $envValues['SENDGRID_API_KEY'] ?? '';
$googleMapsKey = $envValues['GOOGLE_MAPS_API_KEY'] ?? '';
$twilioSid = $envValues['TWILIO_ACCOUNT_SID'] ?? '';
$twilioToken = $envValues['TWILIO_AUTH_TOKEN'] ?? '';
$twilioPhone = $envValues['TWILIO_PHONE_NUMBER'] ?? '';
$fcmServerKey = $envValues['FCM_SERVER_KEY'] ?? '';
$fcmSenderId = $envValues['FCM_SENDER_ID'] ?? '';
$openaiKey = $envValues['OPENAI_API_KEY'] ?? '';
$stripeKey = $envValues['STRIPE_SECRET_KEY'] ?? '';
$stripePubKey = $envValues['STRIPE_PUBLISHABLE_KEY'] ?? '';

$page_title = 'Configuration';
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

  <form method="post" class="space-y-6">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_config">

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <i class="fas fa-envelope text-purple-600"></i>
        Services d'Email
      </h2>
      
      <div class="space-y-4">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-sm font-medium text-slate-900 mb-3">Mailgun</h3>
          <div class="space-y-3">
            <div>
              <label class="block text-xs text-slate-500 mb-1">API Key</label>
              <input type="password" name="mailgun_api_key" 
                     value="<?= htmlspecialchars($mailgunKey, ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                     placeholder="key-xxxxxxxx">
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Domain</label>
              <input type="text" name="mailgun_domain" 
                     value="<?= htmlspecialchars($mailgunDomain, ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                     placeholder="mg.yourdomain.com">
            </div>
          </div>
        </div>

        <div class="pb-4">
          <h3 class="text-sm font-medium text-slate-900 mb-3">Amazon SES</h3>
          <div class="space-y-3">
            <div>
              <label class="block text-xs text-slate-500 mb-1">Access Key ID</label>
              <input type="text" name="ses_access_key" 
                     value="<?= htmlspecialchars($sesKey, ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                     placeholder="AKIAIOSFODNN7EXAMPLE">
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Secret Access Key</label>
              <input type="password" name="ses_secret_key" 
                     value="<?= htmlspecialchars($sesSecret, ENT_QUOTES, 'UTF-8') ?>"
                     class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                     placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY">
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Region</label>
              <select name="ses_region" class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm">
                <option value="us-east-1" <?= $sesRegion === 'us-east-1' ? 'selected' : '' ?>>US East (N. Virginia)</option>
                <option value="us-east-2" <?= $sesRegion === 'us-east-2' ? 'selected' : '' ?>>US East (Ohio)</option>
                <option value="us-west-1" <?= $sesRegion === 'us-west-1' ? 'selected' : '' ?>>US West (N. California)</option>
                <option value="us-west-2" <?= $sesRegion === 'us-west-2' ? 'selected' : '' ?>>US West (Oregon)</option>
                <option value="eu-west-1" <?= $sesRegion === 'eu-west-1' ? 'selected' : '' ?>>EU (Ireland)</option>
                <option value="eu-central-1" <?= $sesRegion === 'eu-central-1' ? 'selected' : '' ?>>EU (Frankfurt)</option>
              </select>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-medium text-slate-900 mb-3">SendGrid</h3>
          <div>
            <label class="block text-xs text-slate-500 mb-1">API Key</label>
            <input type="password" name="sendgrid_api_key" 
                   value="<?= htmlspecialchars($sendgridKey, ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                   placeholder="SG.xxxxx">
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <i class="fas fa-map-marked-alt text-blue-600"></i>
        Cartes et Localisation (Google Maps)
      </h2>
      <div class="space-y-3">
        <div>
          <label class="block text-xs text-slate-500 mb-1">API Key</label>
          <input type="password" name="google_maps_api_key" 
                 value="<?= htmlspecialchars($googleMapsKey, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="AIzaSyxxxxxxxxxxxxxxx">
          <p class="text-xs text-slate-500 mt-1">Necessaire pour les codes Plus Codes (ex: QP4V+9X Sfax)</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <i class="fas fa-sms text-amber-600"></i>
        Service SMS (Twilio)
      </h2>
      <div class="space-y-3">
        <div>
          <label class="block text-xs text-slate-500 mb-1">Account SID</label>
          <input type="text" name="twilio_account_sid" 
                 value="<?= htmlspecialchars($twilioSid, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="ACxxxxxxxxxxxxxxxxxxx">
        </div>
        <div>
          <label class="block text-xs text-slate-500 mb-1">Auth Token</label>
          <input type="password" name="twilio_auth_token" 
                 value="<?= htmlspecialchars($twilioToken, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="your_auth_token">
        </div>
        <div>
          <label class="block text-xs text-slate-500 mb-1">Phone Number</label>
          <input type="text" name="twilio_phone_number" 
                 value="<?= htmlspecialchars($twilioPhone, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="+1234567890">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <i class="fas fa-bell text-red-600"></i>
        Notifications Push (Firebase Cloud Messaging)
      </h2>
      <div class="space-y-3">
        <div>
          <label class="block text-xs text-slate-500 mb-1">Server Key</label>
          <input type="password" name="fcm_server_key" 
                 value="<?= htmlspecialchars($fcmServerKey, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="AAAA...">
        </div>
        <div>
          <label class="block text-xs text-slate-500 mb-1">Sender ID</label>
          <input type="text" name="fcm_sender_id" 
                 value="<?= htmlspecialchars($fcmSenderId, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="123456789012">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <i class="fas fa-robot text-cyan-600"></i>
        Intelligence Artificielle (OpenAI)
      </h2>
      <div>
        <label class="block text-xs text-slate-500 mb-1">API Key</label>
        <input type="password" name="openai_api_key" 
               value="<?= htmlspecialchars($openaiKey, ENT_QUOTES, 'UTF-8') ?>"
               class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
               placeholder="sk-xxxxxxxxxxxxxxxxxxxx">
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5">
      <h2 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <i class="fas fa-credit-card text-green-600"></i>
        Paiement (Stripe)
      </h2>
      <div class="space-y-3">
        <div>
          <label class="block text-xs text-slate-500 mb-1">Secret Key</label>
          <input type="password" name="stripe_secret_key" 
                 value="<?= htmlspecialchars($stripeKey, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="sk_test_xxxxxxxxxxxxxxxxxxxx">
        </div>
        <div>
          <label class="block text-xs text-slate-500 mb-1">Publishable Key</label>
          <input type="text" name="stripe_publishable_key" 
                 value="<?= htmlspecialchars($stripePubKey, ENT_QUOTES, 'UTF-8') ?>"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm"
                 placeholder="pk_test_xxxxxxxxxxxxxxxxxxxx">
        </div>
      </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm">
      <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5"></i>
        <div>
          <h3 class="font-semibold text-amber-900">Securite</h3>
          <p class="text-amber-800 mt-1">Les cles API sont stockees dans le fichier .env. Assurez-vous que ce fichier n'est pas accessible publiquement.</p>
        </div>
      </div>
    </div>

    <button type="submit" class="w-full h-12 rounded-xl bg-blue-600 text-white font-semibold flex items-center justify-center gap-2 hover:bg-blue-700">
      <i class="fas fa-save"></i>
      <span>Enregistrer la configuration</span>
    </button>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>