<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Syncing Batch Scholar Counts...\n";

$batches = DB::table('billing_batches')->get();

foreach ($batches as $batch) {
    $actualCount = DB::table('billing_scholars')->where('billing_batch_id', $batch->id)->count();
    
    if ($batch->scholar_count != $actualCount) {
        DB::table('billing_batches')->where('id', $batch->id)->update(['scholar_count' => $actualCount]);
        echo "Updated Batch #{$batch->id}: Changed count from {$batch->scholar_count} to $actualCount\n";
    }
}

echo "Sync complete!\n";
