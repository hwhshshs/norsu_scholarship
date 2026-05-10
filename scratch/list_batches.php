<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batches = DB::table('billing_batches')->select('id', 'program', 'batch', 'ay', 'semester')->get();
foreach ($batches as $b) {
    echo "ID: " . $b->id . " | Program: " . $b->program . " | Batch: " . $b->batch . " | Period: " . $b->ay . " " . $b->semester . "\n";
}
