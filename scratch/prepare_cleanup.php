<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- CLEANUP PREVIEW ---\n";

// 1. Identify Empty Batches
$emptyBatches = DB::table('billing_batches')->where('scholar_count', 0)->get();
echo "\nEmpty Batches (0 scholars):\n";
foreach ($emptyBatches as $b) {
    echo "  - ID: {$b->id} | Program: {$b->program} | AY: {$b->ay} | Created: {$b->created_at}\n";
}

// 2. Identify Batches with identical content to another batch
echo "\nRedundant Batches (Identical student lists to a later batch):\n";
$batches = DB::table('billing_batches')->orderBy('id', 'desc')->get(); // Check newer ones first
$seenSets = [];
$toDelete = [];

foreach ($batches as $batch) {
    $scholars = DB::table('billing_scholars')
        ->where('billing_batch_id', $batch->id)
        ->orderBy('student_id_no')
        ->pluck('student_id_no')
        ->toArray();
    
    if (empty($scholars)) continue;

    $scholarKey = implode(',', $scholars);
    
    if (isset($seenSets[$scholarKey])) {
        // We've seen this student set already in a newer/more complete batch
        $existingBatch = $seenSets[$scholarKey];
        echo "  - ID: {$batch->id} is a redundant copy of ID: {$existingBatch->id}\n";
        echo "    (ID {$batch->id} AY: {$batch->ay} vs ID {$existingBatch->id} AY: {$existingBatch->ay})\n";
        $toDelete[] = $batch->id;
    } else {
        $seenSets[$scholarKey] = $batch;
    }
}

echo "\n--- PROPOSED ACTION ---\n";
if (empty($emptyBatches) && empty($toDelete)) {
    echo "No obvious duplicates found for removal.\n";
} else {
    echo "Recommended deletion IDs: " . implode(', ', array_merge($emptyBatches->pluck('id')->toArray(), $toDelete)) . "\n";
}
