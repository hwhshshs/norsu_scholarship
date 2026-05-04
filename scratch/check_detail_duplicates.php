<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$duplicates = DB::table('disbursed_batch_details')
    ->select('billing_batch_id', DB::raw('COUNT(*) as count'))
    ->groupBy('billing_batch_id')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->count() > 0) {
    echo "Found duplicate records in disbursed_batch_details:\n";
    foreach ($duplicates as $d) {
        echo "Batch ID: {$d->billing_batch_id} | Count: {$d->count}\n";
        
        $details = DB::table('disbursed_batch_details')
            ->where('billing_batch_id', $d->billing_batch_id)
            ->get();
            
        foreach($details as $det) {
            echo "   -> Detail ID: {$det->id} | ADA: {$det->ada_no} | OR: {$det->or_number} | Created: {$det->created_at}\n";
        }
    }
} else {
    echo "No duplicates found in disbursed_batch_details.\n";
}
