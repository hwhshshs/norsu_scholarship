<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batches = DB::table('billing_batches')
    ->where('program', 'CHED')
    ->where('ay', '2021-2022')
    ->get();

foreach($batches as $b) {
    echo "Deleting CHED Batch ID: " . $b->id . " (AY 2021-2022)...\n";
    DB::table('billing_scholars')->where('billing_batch_id', $b->id)->delete();
    DB::table('billing_batches')->where('id', $b->id)->delete();
}

echo "Cleanup complete.\n";
