<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- TRANSACTION AUDIT ---\n";

// 1. Find all fees_transactions that don't have a valid student record
$orphans = DB::table('fees_transaction as ft')
    ->leftJoin('student as s', 's.id', '=', 'ft.stdid')
    ->whereNull('s.id')
    ->select('ft.*')
    ->get();

foreach ($orphans as $ft) {
    echo "Deleting Orphan Fees Transaction: ID {$ft->id}, Batch {$ft->billing_batch_id}, Amount {$ft->paid}\n";
    DB::table('fees_transaction')->where('id', $ft->id)->delete();
}

// 2. Find all disbursed_transactions that don't have a valid student record
$orphansD = DB::table('disbursed_transaction as dt')
    ->leftJoin('student as s', 's.id', '=', 'dt.stdid')
    ->whereNull('s.id')
    ->select('dt.*')
    ->get();

foreach ($orphansD as $dt) {
    echo "Deleting Orphan Disbursed Transaction: ID {$dt->id}, Batch {$dt->billing_batch_id}, Amount {$dt->disbursed_amount}\n";
    DB::table('disbursed_transaction')->where('id', $dt->id)->delete();
}

// 3. Delete any batches that are now empty
$batches = DB::table('billing_batch')->get();
foreach ($batches as $b) {
    $count = DB::table('fees_transaction')->where('billing_batch_id', $b->id)->count();
    if ($count == 0) {
        echo "Deleting Empty/Ghost Batch: ID {$b->id}, Program {$b->program}\n";
        DB::table('billing_batch')->where('id', $b->id)->delete();
    }
}

echo "--- AUDIT COMPLETE ---\n";
