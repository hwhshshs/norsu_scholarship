<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$file = 'app/Http/Controllers/ScholarshipDisbursedController.php';
$content = file_get_contents($file);

// 1. Update header normalization to handle dots/spaces (e.g., "ADA no." -> "ada_no")
$content = str_replace(
    'fn($h) => strtolower(trim((string)$h))',
    'fn($h) => strtolower(trim(preg_replace(\'/[^a-z0-9]+/\', \'_\', (string)$h), \'_\'))',
    $content
);

file_put_contents($file, $content);
echo "ScholarshipDisbursedController headers normalized successfully.\n";
