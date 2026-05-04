<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Fix Ian's name typo
DB::table('student')->where('id', 40)->update([
    'given_name' => 'Ian Christian',
    'sname' => 'Maranga, Ian Christian'
]);
echo "Fixed Ian's name typo.\n";

// 2. Identify "No names" billing batches
$batches = DB::table('billing_batch')->where('delete_status', '0')->get();
foreach ($batches as $batch) {
    $transactions = DB::table('fees_transaction')->where('billing_batch_id', $batch->id)->get();
    if ($transactions->isEmpty()) {
        echo "Batch {$batch->id} ({$batch->program}) has NO transactions. (Removing orphan batch)\n";
        DB::table('billing_batch')->where('id', $batch->id)->update(['delete_status' => '1']);
    } else {
        foreach ($transactions as $ft) {
            $student = DB::table('student')->where('id', $ft->stdid)->first();
            if (!$student) {
                echo "Transaction ID {$ft->id} in Batch {$batch->id} points to missing Student ID {$ft->stdid}.\n";
                // Try to find student by name if possible? No, stdid is usually 0 if it failed.
            }
        }
    }
}

// 3. Clean up Disbursed Transactions with draft status for incomplete profiles
// (We should probably just let them be, but we need to know why they are No names)
$disbursed = DB::table('disbursed_transaction as dt')
    ->leftJoin('student as s', 's.id', '=', 'dt.stdid')
    ->whereNull('s.id')
    ->select('dt.*')
    ->get();

foreach ($disbursed as $d) {
    echo "Orphan Disbursed Transaction: ID {$d->id}, Batch {$d->billing_batch_id}, Student ID {$d->stdid}\n";
}
