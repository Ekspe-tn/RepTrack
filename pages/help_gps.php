<?php

declare(strict_types=1);

$page_title = 'Aide GPS';
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-4">
  <div class="bg-white rounded-2xl shadow-sm p-6">
    <h1 class="text-lg font-semibold text-slate-900">Activer la geolocalisation</h1>
    <p class="text-sm text-slate-600 mt-2">
      Pour obtenir le GPS, le navigateur doit autoriser l'accès a la localisation.
    </p>

    <div class="mt-4 space-y-4 text-sm text-slate-700">
      <div>
        <div class="font-semibold text-slate-900">Chrome (PC)</div>
        <div>1. Cliquez sur l'icone 🔒 a gauche de l'URL.</div>
        <div>2. Autorisations &gt; Localisation &gt; Autoriser.</div>
        <div>3. Rechargez la page.</div>
      </div>
      <div>
        <div class="font-semibold text-slate-900">Chrome (Android)</div>
        <div>1. Menu ⋮ &gt; Parametres &gt; Parametres du site.</div>
        <div>2. Localisation &gt; Autoriser pour ce site.</div>
        <div>3. Rechargez la page.</div>
      </div>
      <div>
        <div class="font-semibold text-slate-900">Safari (iPhone)</div>
        <div>1. Reglages &gt; Safari &gt; Localisation.</div>
        <div>2. Choisir "Pendant l'utilisation".</div>
        <div>3. Rechargez la page.</div>
      </div>
      <div>
        <div class="font-semibold text-slate-900">Windows / macOS</div>
        <div>Assurez-vous que les services de localisation sont actives dans les reglages systeme.</div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-base font-semibold text-slate-900">Toujours bloque ?</h2>
    <p class="text-sm text-slate-600 mt-2">
      Verifiez que l'application est bien ouverte en HTTPS (ou localhost) et que l'heure du systeme est correcte.
    </p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
