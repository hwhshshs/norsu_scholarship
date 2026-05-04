<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "--- DISBURSED BATCH DETAILS ---\n";
$details = DB::table('disbursed_batch_details')->get();
foreach($details as $d) {
    echo "ID: {$d->id} | Batch ID: {$d->billing_batch_id} | ADA: {$d->ada_no} | OR: {$d->or_number} | Created: {$d->created_at}\n";
}

echo "\n--- DUPLICATE CHECK ---\n";
$duplicates = DB::table('disbursed_batch_details')
    ->select('ada_no', 'or_number', DB::raw('COUNT(*) as count'))
    ->groupBy('ada_no', 'or_number')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "No direct ADA/OR duplicates found in disbursed_batch_details.\n";
} else {
    foreach($duplicates as $dup) {
        echo "DUPLICATE FOUND: ADA {$dup->ada_no} | OR {$dup->or_number} ({$dup->count} times)\n";
    }
}
