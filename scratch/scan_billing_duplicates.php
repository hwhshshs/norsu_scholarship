<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- BILLING DUPLICATE SCAN ---\n";

// 1. Check for duplicate Billing Batches
echo "\nChecking for duplicate Billing Batches...\n";
$duplicateBatches = DB::table('billing_batches')
    ->select('program', 'semester', 'ay', 'batch', DB::raw('COUNT(*) as count'))
    ->groupBy('program', 'semester', 'ay', 'batch')
    ->having('count', '>', 1)
    ->get();

if ($duplicateBatches->isEmpty()) {
    echo "[OK] No exact duplicate batches found (Program/Sem/AY/Batch).\n";
} else {
    echo "[!] Duplicate batches detected:\n";
    foreach ($duplicateBatches as $dupe) {
        echo "    - {$dupe->program} | {$dupe->semester} | {$dupe->ay} | Batch: {$dupe->batch} ({$dupe->count} occurrences)\n";
        
        // List the IDs for analysis
        $ids = DB::table('billing_batches')
            ->where('program', $dupe->program)
            ->where('semester', $dupe->semester)
            ->where('ay', $dupe->ay)
            ->where('batch', $dupe->batch)
            ->pluck('id');
        echo "      IDs: " . $ids->implode(', ') . "\n";
    }
}

// 2. Check for duplicate scholars within a single batch
echo "\nChecking for duplicate scholars within batches...\n";
$duplicateScholars = DB::table('billing_scholars')
    ->select('billing_batch_id', 'student_id_no', DB::raw('COUNT(*) as count'))
    ->groupBy('billing_batch_id', 'student_id_no')
    ->having('count', '>', 1)
    ->get();

if ($duplicateScholars->isEmpty()) {
    echo "[OK] No duplicate students found within individual batches.\n";
} else {
    echo "[!] Duplicate scholars detected within batches:\n";
    foreach ($duplicateScholars as $dupe) {
        $batch = DB::table('billing_batches')->where('id', $dupe->billing_batch_id)->first();
        echo "    - Batch ID {$dupe->billing_batch_id} (#{$batch->batch_no} - {$batch->program}):\n";
        echo "      Student ID {$dupe->student_id_no} appears {$dupe->count} times.\n";
    }
}

echo "\n--- SCAN COMPLETE ---\n";
