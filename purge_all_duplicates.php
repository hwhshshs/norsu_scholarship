<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Scanning for duplicates in billing_scholars...\n";

// Find all (student_id_no, billing_batch_id) pairs that appear more than once
$duplicates = DB::table('billing_scholars')
    ->select('student_id_no', 'billing_batch_id', DB::raw('COUNT(*) as count'))
    ->groupBy('student_id_no', 'billing_batch_id')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "No duplicates found.\n";
    exit;
}

echo "Found " . $duplicates->count() . " sets of duplicates.\n";

$totalDeleted = 0;
$affectedBatches = [];

foreach ($duplicates as $dup) {
    // Keep the earliest record (min id)
    $keepId = DB::table('billing_scholars')
        ->where('student_id_no', $dup->student_id_no)
        ->where('billing_batch_id', $dup->billing_batch_id)
        ->min('id');

    // Delete the rest
    $deletedCount = DB::table('billing_scholars')
        ->where('student_id_no', $dup->student_id_no)
        ->where('billing_batch_id', $dup->billing_batch_id)
        ->where('id', '!=', $keepId)
        ->delete();

    $totalDeleted += $deletedCount;
    $affectedBatches[$dup->billing_batch_id] = ($affectedBatches[$dup->billing_batch_id] ?? 0) + $deletedCount;

    echo "Cleared duplicates for Student {$dup->student_id_no} in Batch {$dup->billing_batch_id} (Removed $deletedCount entries)\n";
}

// Sync batch counts
echo "\nSyncing batch scholar counts...\n";
foreach ($affectedBatches as $batchId => $reduction) {
    DB::table('billing_batches')
        ->where('id', $batchId)
        ->decrement('scholar_count', $reduction);
    echo "Updated Batch $batchId count: Reduced by $reduction\n";
}

echo "\nCleanup Complete! Total records removed: $totalDeleted\n";
