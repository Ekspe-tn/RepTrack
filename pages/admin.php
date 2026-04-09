<?php

declare(strict_types=1);

require_role('admin');

$page_title = 'Administration';
require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-4">
    <h1 class="text-xl font-bold text-slate-900">Administration</h1>
    <p class="text-sm text-slate-500 mt-1">Gerez les parametres et les sauvegardes de l'application</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="/admin/config" class="bg-white rounded-2xl shadow-sm p-5 block hover:shadow-md transition-shadow">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
          <i class="fas fa-cog text-blue-600 text-xl"></i>
        </div>
        <div>
          <h2 class="text-base font-semibold text-slate-900">Configuration</h2>
          <p class="text-sm text-slate-600 mt-1">Gerez les API et les parametres</p>
          <div class="flex flex-wrap gap-2 mt-3">
            <span class="px-2 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Mailgun</span>
            <span class="px-2 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Amazon SES</span>
            <span class="px-2 py-1 bg-slate-100 rounded-md text-xs text-slate-600">SMS</span>
          </div>
        </div>
      </div>
    </a>

    <a href="/admin/backup" class="bg-white rounded-2xl shadow-sm p-5 block hover:shadow-md transition-shadow">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
          <i class="fas fa-cloud-upload-alt text-green-600 text-xl"></i>
        </div>
        <div>
          <h2 class="text-base font-semibold text-slate-900">Sauvegarde & Restauration</h2>
          <p class="text-sm text-slate-600 mt-1">Sauvegardez et restaurez les donnees</p>
          <div class="flex flex-wrap gap-2 mt-3">
            <span class="px-2 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Google Drive</span>
            <span class="px-2 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Dropbox</span>
            <span class="px-2 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Telecharger</span>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-5">
    <h2 class="text-base font-semibold text-slate-900 mb-4">Recommandations d'API</h2>
    <div class="space-y-3 text-sm">
      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-envelope text-purple-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">SendGrid</h3>
          <p class="text-slate-600 mt-1">Service d'envoi d'emails transactionnels. Alternative a Mailgun et Amazon SES.</p>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-sms text-amber-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">Twilio</h3>
          <p class="text-slate-600 mt-1">Service SMS pour les notifications et rappels de visites.</p>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-bell text-red-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">Firebase Cloud Messaging</h3>
          <p class="text-slate-600 mt-1">Notifications push pour les delegues sur leurs appareils mobiles.</p>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-robot text-cyan-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">OpenAI API</h3>
          <p class="text-slate-600 mt-1">IA pour generer des rapports, analyser les tendances et predire les ventes.</p>
        </div>
      </div>

      <div class="flex items-start gap-3 p-3 border border-slate-100 rounded-xl">
        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-credit-card text-green-600"></i>
        </div>
        <div>
          <h3 class="font-medium text-slate-900">Stripe / PayPal</h3>
          <p class="text-slate-600 mt-1">Integration de paiement pour les commandes ou abonnements.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>