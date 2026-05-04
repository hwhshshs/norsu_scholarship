<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$batchId = 9;

DB::beginTransaction();
try {
    // 1. Delete from disbursed_batch_details
    $deletedDetails = DB::table('disbursed_batch_details')->where('billing_batch_id', $batchId)->delete();
    
    // 2. Delete from disbursed_transaction
    $deletedTransactions = DB::table('disbursed_transaction')->where('billing_batch_id', $batchId)->delete();
    
    DB::commit();
    echo "Success! Removed duplicate disbursement data for Batch ID: $batchId.\n";
    echo "Deleted Details: $deletedDetails\n";
    echo "Deleted Transactions: $deletedTransactions\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage();
}
