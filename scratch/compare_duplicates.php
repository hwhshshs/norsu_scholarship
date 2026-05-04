<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

function inspectBatch($id) {
    echo "--- Inspecting Batch ID: $id ---\n";
    $batch = DB::table('billing_batch')->where('id', $id)->first();
    if (!$batch) {
        echo "Batch not found.\n";
        return;
    }
    
    $disbursements = DB::table('disbursed_transaction')
        ->where('billing_batch_id', $id)
        ->count();
        
    $details = DB::table('disbursed_batch_details')
        ->where('billing_batch_id', $id)
        ->first();
        
    echo "Program: {$batch->program} | Sem: {$batch->semester} | Date: {$batch->billing_date}\n";
    echo "Disbursement Count: $disbursements\n";
    if ($details) {
        echo "ADA: {$details->ada_no} | OR: {$details->or_number} | Created: {$details->created_at}\n";
    } else {
        echo "No disbursement details found.\n";
    }
    echo "\n";
}

inspectBatch(8);
inspectBatch(9);
