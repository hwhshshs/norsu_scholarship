<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batchId = 43;

echo "Deleting corrupted Batch ID: $batchId...\n";

DB::transaction(function() use ($batchId) {
    // Delete links first
    $deletedLinks = DB::table('billing_scholars')->where('billing_batch_id', $batchId)->delete();
    echo "Deleted $deletedLinks links from billing_scholars.\n";

    // Delete the batch
    $deletedBatch = DB::table('billing_batches')->where('id', $batchId)->delete();
    echo "Deleted batch from billing_batches.\n";
});

echo "\nCleanup complete. Reports should now be accurate.\n";
