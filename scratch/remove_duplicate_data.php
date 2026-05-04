<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$batchIdToRemove = 8;

DB::beginTransaction();
try {
    // 1. Remove from disbursed_batch_details
    $deletedDetails = DB::table('disbursed_batch_details')
        ->where('billing_batch_id', $batchIdToRemove)
        ->delete();
        
    // 2. Remove from disbursed_transaction
    $deletedTransactions = DB::table('disbursed_transaction')
        ->where('billing_batch_id', $batchIdToRemove)
        ->delete();
        
    // 3. Optional: Reset the billing status for these students? 
    // Actually, deleting from disbursed_transaction automatically makes them "Billed" again in the reports.
    
    DB::commit();
    echo "Success! Removed disbursement data for Batch ID: $batchIdToRemove.\n";
    echo "Deleted Details: $deletedDetails\n";
    echo "Deleted Transactions: $deletedTransactions\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
