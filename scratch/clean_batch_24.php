<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dupes = [85, 86, 87, 88, 89, 90, 91, 92, 93];

echo "Cleaning " . count($dupes) . " duplicates from Batch 24...\n";

DB::table('billing_scholars')->whereIn('id', $dupes)->delete();
DB::table('billing_batches')->where('id', 24)->update(['scholar_count' => 0]);

echo "Cleanup complete. Batch 24 is now empty.\n";
