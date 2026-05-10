<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$batches = DB::table('billing_batches')->orderBy('id')->get();
foreach ($batches as $b) {
    echo "ID: {$b->id} | Created: {$b->created_at} | AY: {$b->ay} | Program: {$b->program} | Scholars: {$b->scholar_count}\n";
}
