<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Cleaning up CHED batches for AY 2021-2022...\n";

$batchIds = DB::table('billing_batches')
    ->where('program', 'CHED')
    ->where('ay', '2021-2022')
    ->pluck('id');

if ($batchIds->isEmpty()) {
    echo "No matching batches found.\n";
} else {
    echo "Found " . $batchIds->count() . " batches. Vaporizing...\n";
    DB::table('billing_scholars')->whereIn('billing_batch_id', $batchIds)->delete();
    DB::table('billing_batches')->whereIn('id', $batchIds)->delete();
    echo "Vaporization complete.\n";
}
