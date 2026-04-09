<?php
$user = function_exists('current_user') ? current_user() : null;
$role = $user['role'] ?? '';
$isAdmin = $role === 'admin';
?>

<nav class="fixed bottom-0 inset-x-0 z-40 bg-white border-t border-slate-200">
  <div class="max-w-md mx-auto grid <?= $isAdmin ? 'grid-cols-6' : 'grid-cols-5' ?> px-2 py-2 text-xs text-slate-600">
    <a href="/dashboard" class="flex flex-col items-center gap-1">
      <span class="text-lg">&#x1F4CA;</span>
      <span>Dashboard</span>
    </a>
    <a href="/visits" class="flex flex-col items-center gap-1">
      <span class="text-lg">&#x1F4C5;</span>
      <span>Visites</span>
    </a>
    <a href="/visits/new" class="flex flex-col items-center gap-1">
      <span class="text-lg">&#x2795;</span>
      <span>Nouvelle</span>
    </a>
    <a href="/contacts" class="flex flex-col items-center gap-1">
      <span class="text-lg">&#x1F465;</span>
      <span>Contacts</span>
    </a>
    <?php if ($isAdmin): ?>
    <a href="/delegues" class="flex flex-col items-center gap-1">
      <span class="text-lg">&#x1F9D1;&#x200D;&#x1F4BC;</span>
      <span>Delegues</span>
    </a>
    <?php endif; ?>
    <a href="/stock" class="flex flex-col items-center gap-1">
      <span class="text-lg">&#x1F4E6;</span>
      <span>Stock</span>
    </a>
  </div>
</nav>
