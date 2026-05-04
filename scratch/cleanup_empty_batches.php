<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting cleanup of empty billing batches...\n";

// 1. Find batches that have 0 transactions
$emptyBatches = DB::table('billing_batch as bb')
    ->leftJoin('fees_transaction as ft', 'ft.billing_batch_id', '=', 'bb.id')
    ->whereNull('ft.id')
    ->select('bb.id', 'bb.program', 'bb.semester', 'bb.academic_year')
    ->get();

$count = 0;
foreach ($emptyBatches as $batch) {
    DB::table('billing_batch')->where('id', $batch->id)->delete();
    echo "Deleted empty batch ID {$batch->id}: {$batch->program} ({$batch->semester})\n";
    $count++;
}

echo "Cleanup complete! Deleted $count empty batches.\n";
