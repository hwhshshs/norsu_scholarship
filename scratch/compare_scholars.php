<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = [1, 39];
foreach ($ids as $id) {
    $batch = DB::table('billing_batches')->where('id', $id)->first();
    echo "Batch ID: $id | AY: {$batch->ay} | Sem: {$batch->semester} | Program: {$batch->program}\n";
    $scholars = DB::table('billing_scholars')->where('billing_batch_id', $id)->get();
    foreach ($scholars as $s) {
        echo "  - {$s->student_id_no}: {$s->student_name}\n";
    }
    echo "\n";
}
