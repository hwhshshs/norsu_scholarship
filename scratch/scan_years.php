<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Scanning for all Academic Years in the system:\n";

$years = DB::table('billing_batches')
    ->select('ay', DB::raw('count(*) as count'))
    ->groupBy('ay')
    ->get();

foreach ($years as $y) {
    echo "AY: {$y->ay} | Batches: {$y->count}\n";
}

echo "\nScanning for any batches with suspicious amounts or future dates:\n";
$suspicious = DB::table('billing_batches')
    ->where('ay', '>', '2025-2026')
    ->get();

if ($suspicious->isEmpty()) {
    echo "No more future batches found.\n";
} else {
    foreach ($suspicious as $s) {
        echo "Suspicious Batch - ID: {$s->id} | Program: {$s->program} | AY: {$s->ay} | Amount: {$s->amount}\n";
    }
}
