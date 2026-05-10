<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Aggressive Cleanup: Vaporizing all empty batches (0 scholars)...\n";

$batchIds = DB::table('billing_batches')
    ->where('scholar_count', 0)
    ->pluck('id');

if ($batchIds->isEmpty()) {
    echo "No empty batches found.\n";
} else {
    echo "Found " . $batchIds->count() . " empty batches. Vaporizing...\n";
    DB::table('billing_scholars')->whereIn('billing_batch_id', $batchIds)->delete();
    DB::table('billing_batches')->whereIn('id', $batchIds)->delete();
    echo "Vaporization complete. System is now clean.\n";
}
