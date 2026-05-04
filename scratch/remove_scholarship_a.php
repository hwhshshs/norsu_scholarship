<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Removing 'Scholaship A' from the system...\n";

// 1. Delete transactions linked to these program names
$names = ['Scholaship A', 'Scholarship A'];
$deletedTxs = DB::table('fees_transaction')->whereIn('program', $names)->delete();
$deletedDisbursed = DB::table('disbursed_transaction')->whereIn('program', $names)->delete();

echo "Deleted $deletedTxs fees transactions and $deletedDisbursed disbursement records.\n";

// 2. Delete billing batches
$deletedBatches = DB::table('billing_batch')->whereIn('program', $names)->delete();
echo "Deleted $deletedBatches billing batches.\n";

// 3. Delete from programs list if exists
if (Illuminate\Support\Facades\Schema::hasTable('academic_program')) {
    $deletedPrograms = DB::table('academic_program')->whereIn('name', $names)->delete();
    echo "Deleted $deletedPrograms program definitions.\n";
}

echo "Removal of 'Scholaship A' complete!\n";
