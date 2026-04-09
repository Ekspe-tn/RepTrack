<?php

declare(strict_types=1);

function kpi_card(string $label, string $value, string $tone = 'blue'): string
{
    $tones = [
        'blue' => 'bg-blue-600 text-white',
        'green' => 'bg-green-600 text-white',
        'amber' => 'bg-amber-500 text-white',
        'red' => 'bg-red-600 text-white',
        'slate' => 'bg-slate-800 text-white',
    ];

    $class = $tones[$tone] ?? $tones['blue'];

    return '<div class="rounded-2xl p-4 shadow-sm ' . $class . '">' .
        '<div class="text-xs uppercase tracking-wide opacity-80">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>' .
        '<div class="text-2xl font-semibold mt-2">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</div>' .
        '</div>';
}
