<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batchId = 2;
$deletedCount = DB::table('billing_scholars')->where('billing_batch_id', $batchId)->delete();
DB::table('billing_batches')->where('id', $batchId)->update(['scholar_count' => 0]);

echo "SUCCESS: Deleted $deletedCount scholars from Batch $batchId. Batch count reset to 0.\n";
