<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Full cleanup of redundant empty billing batches...\n";

$deleted = DB::table('billing_batch')
    ->where('delete_status', '0')
    ->where('scholar_count', 0)
    ->delete();

echo "Successfully deleted $deleted redundant empty batches.\n";
