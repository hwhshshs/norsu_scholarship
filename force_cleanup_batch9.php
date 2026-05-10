<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = ['202200356', '202203695', '202205684', '202202365', '202203125', '202204578', '202206598', '202203256'];
$batchId = 9;

foreach($ids as $id) {
    $deleted = DB::table('billing_scholars')
        ->where('billing_batch_id', $batchId)
        ->where('student_id_no', $id)
        ->delete();
    
    if ($deleted) echo "Deleted ID $id from Batch $batchId.\n";
}

// Recalculate count
$newCount = DB::table('billing_scholars')->where('billing_batch_id', $batchId)->count();
DB::table('billing_batches')->where('id', $batchId)->update(['scholar_count' => $newCount]);

echo "Sync complete. Batch $batchId now has $newCount scholars.\n";
