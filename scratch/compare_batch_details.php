<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = [1, 39];
foreach ($ids as $id) {
    $batch = DB::table('billing_batches')->where('id', $id)->first();
    echo "Batch ID: $id\n";
    print_r($batch);
    echo "\n";
}
