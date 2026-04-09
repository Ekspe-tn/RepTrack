<?php

declare(strict_types=1);

$page_title = $page_title ?? 'RepTrack';
$show_back = $show_back ?? false;
$unread_notifications = $unread_notifications ?? 0;
$is_logged_in = function_exists('is_logged_in') ? is_logged_in() : false;
$app_env = getenv('APP_ENV') ?: 'local';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
  <?php if ($app_env === 'production'): ?>
    <link rel="stylesheet" href="/assets/css/app.css">
  <?php else: ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/app.css">
  <?php endif; ?>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-900">

<header class="sticky top-0 z-40 bg-white border-b border-slate-100">
  <div class="flex items-center justify-between px-4 h-14">
    <div class="w-10">
      <?php if ($show_back): ?>
        <a href="javascript:history.back()" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100">
          <span class="text-slate-600">&larr;</span>
        </a>
      <?php else: ?>
        <span class="text-blue-600 font-black text-lg">RT</span>
      <?php endif; ?>
    </div>

    <h1 class="text-base font-semibold text-slate-900">
      <?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>
    </h1>

    <div class="w-10 flex justify-end">
      <?php if ($is_logged_in): ?>
        <a href="/notifications" class="relative w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100">
          <span class="text-slate-600">&#x1F514;</span>
          <?php if ($unread_notifications > 0): ?>
            <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
              <?= (int) $unread_notifications ?>
            </span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="px-4 pt-4 pb-20">
