<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Find and DELETE any fees_transaction that doesn't have a matching student
$orphanFees = DB::table('fees_transaction as ft')
    ->leftJoin('student as s', 's.id', '=', 'ft.stdid')
    ->whereNull('s.id')
    ->select('ft.id', 'ft.billing_batch_id')
    ->get();

foreach ($orphanFees as $ft) {
    echo "Deleting ghost billing transaction ID: {$ft->id} from Batch: {$ft->billing_batch_id}\n";
    DB::table('fees_transaction')->where('id', $ft->id)->delete();
}

// 2. Find and DELETE any disbursed_transaction that doesn't have a matching student
$orphanDisbursed = DB::table('disbursed_transaction as dt')
    ->leftJoin('student as s', 's.id', '=', 'dt.stdid')
    ->whereNull('s.id')
    ->select('dt.id', 'dt.billing_batch_id')
    ->get();

foreach ($orphanDisbursed as $dt) {
    echo "Deleting ghost disbursement transaction ID: {$dt->id} from Batch: {$dt->billing_batch_id}\n";
    DB::table('disbursed_transaction')->where('id', $dt->id)->delete();
}

// 3. Now delete any Billing Batches that are now empty because of the scrub
$emptyBatches = DB::table('billing_batch')->get();
foreach ($emptyBatches as $b) {
    $hasFees = DB::table('fees_transaction')->where('billing_batch_id', $b->id)->exists();
    if (!$hasFees) {
        echo "Deleting empty/orphan batch ID: {$b->id} ({$b->program})\n";
        DB::table('billing_batch')->where('id', $b->id)->delete();
    }
}

echo "Deep scrub completed. Ghost records removed.\n";
