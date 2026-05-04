<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Starting cleanup of 'No names' (invalid stdid) records...\n";

// 1. Identify batches with invalid stdid=0 in fees_transaction
$invalidBatches = DB::table('fees_transaction')
    ->where('stdid', 0)
    ->pluck('billing_batch_id')
    ->unique()
    ->toArray();

if (empty($invalidBatches)) {
    echo "No batches found with stdid=0 in fees_transaction.\n";
} else {
    echo "Found " . count($invalidBatches) . " batches with invalid student links. Deleting...\n";
    
    // Delete linked transactions first
    DB::table('fees_transaction')->whereIn('billing_batch_id', $invalidBatches)->delete();
    DB::table('disbursed_transaction')->whereIn('billing_batch_id', $invalidBatches)->delete();
    DB::table('disbursed_batch_details')->whereIn('billing_batch_id', $invalidBatches)->delete();
    
    // Delete the batches themselves
    $deletedCount = DB::table('billing_batch')->whereIn('id', $invalidBatches)->delete();
    echo "Successfully deleted $deletedCount broken batches.\n";
}

// 2. Also check for batches that say they have scholars but have 0 records in fees_transaction
$emptyBatches = DB::table('billing_batch as bb')
    ->leftJoin('fees_transaction as ft', 'ft.billing_batch_id', '=', 'bb.id')
    ->select('bb.id')
    ->where('bb.delete_status', '0')
    ->groupBy('bb.id')
    ->havingRaw('COUNT(ft.id) = 0')
    ->pluck('bb.id')
    ->toArray();

if (!empty($emptyBatches)) {
    echo "Found " . count($emptyBatches) . " batches with 0 transaction records. Deleting...\n";
    DB::table('disbursed_batch_details')->whereIn('billing_batch_id', $emptyBatches)->delete();
    DB::table('billing_batch')->whereIn('id', $emptyBatches)->delete();
}

echo "Cleanup complete.\n";
