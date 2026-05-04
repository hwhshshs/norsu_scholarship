<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- DEEP DATABASE SCRUB ---\n";

// 1. Find students with blank names
$blankStudents = DB::table('student')
    ->whereRaw("COALESCE(TRIM(given_name), '') = '' AND COALESCE(TRIM(last_name), '') = ''")
    ->get();

foreach ($blankStudents as $s) {
    echo "Found Blank Student Record ID: {$s->id}. Checking for transactions...\n";
    
    // Delete their transactions
    $ftCount = DB::table('fees_transaction')->where('stdid', $s->id)->count();
    $dtCount = DB::table('disbursed_transaction')->where('stdid', $s->id)->count();
    
    echo "  - Fees Transactions: $ftCount\n";
    echo "  - Disbursed Transactions: $dtCount\n";
    
    DB::table('fees_transaction')->where('stdid', $s->id)->delete();
    DB::table('disbursed_transaction')->where('stdid', $s->id)->delete();
    
    // Delete the blank student
    DB::table('student')->where('id', $s->id)->delete();
    echo "  - Blank Student Deleted.\n";
}

// 2. Final sweep for empty batches
$emptyBatches = DB::table('billing_batch')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('fees_transaction')
            ->whereRaw('fees_transaction.billing_batch_id = billing_batch.id');
    })
    ->get();

foreach ($emptyBatches as $b) {
    echo "Deleting truly empty batch: ID {$b->id}, Program {$b->program}\n";
    DB::table('billing_batch')->where('id', $b->id)->delete();
}

echo "--- DEEP SCRUB COMPLETE ---\n";
