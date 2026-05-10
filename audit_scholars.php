<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = ['202200356', '202203695', '202205684', '202202365', '202203125', '202204578', '202206598', '202203256'];
foreach($ids as $id) {
    $history = DB::table('billing_scholars')
        ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
        ->where('billing_scholars.student_id_no', $id)
        ->select('billing_batches.program', 'billing_batches.ay', 'billing_batches.semester', 'billing_batches.id as batch_id')
        ->get();
    
    echo "ID: $id | Count: " . $history->count() . "\n";
    foreach($history as $h) {
        echo "  - Program: {$h->program} | AY: {$h->ay} | Batch ID: {$h->batch_id}\n";
    }
}
