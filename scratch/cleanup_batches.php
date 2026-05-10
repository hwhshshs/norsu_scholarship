<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();
try {
    $batches = DB::table('billing_batches')
        ->where('batch', 'Imported Batch')
        ->orWhere('batch', 'OLD')
        ->orWhere('batch', 'NEW')
        ->orWhere('batch', 'REGULAR')
        ->orWhere('batch', 'ONGOING')
        ->get();

    foreach($batches as $batch) {
        DB::table('billing_scholars')->where('billing_batch_id', $batch->id)->delete();
        DB::table('billing_batches')->where('id', $batch->id)->delete();
    }

    DB::commit();
    echo "Cleaned up " . count($batches) . " test batches.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
