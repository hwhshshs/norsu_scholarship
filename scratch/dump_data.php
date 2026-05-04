<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "--- BILLING BATCHES (CHED 1st Semester) ---\n";
$batches = DB::table('billing_batch')
    ->where('program', 'CHED')
    ->where('semester', '1st Semester')
    ->get();
foreach($batches as $b) {
    echo "ID: {$b->id} | Program: {$b->program} | Sem: {$b->semester} | Date: {$b->billing_date} | Delete Status: {$b->delete_status}\n";
}

echo "\n--- DISBURSED BATCH DETAILS ---\n";
$details = DB::table('disbursed_batch_details')->get();
foreach($details as $d) {
    echo "ID: {$d->id} | Batch ID: {$d->billing_batch_id} | ADA: {$d->ada_no} | OR: {$d->or_number}\n";
}
