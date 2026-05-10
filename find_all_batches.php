<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = ['202200356', '202203695', '202205684', '202202365', '202203125', '202204578', '202206598', '202203256'];
foreach($ids as $id) {
    $entries = DB::table('billing_scholars')
        ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
        ->where('billing_scholars.student_id_no', $id)
        ->select('billing_scholars.billing_batch_id', 'billing_batches.program', 'billing_batches.ay')
        ->get();
    
    echo "ID: $id is in:\n";
    foreach($entries as $e) {
        echo "  - Batch ID: {$e->billing_batch_id} | Program: {$e->program} | AY: {$e->ay}\n";
    }
}
