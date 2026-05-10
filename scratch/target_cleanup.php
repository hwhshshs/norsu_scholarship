<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = ['202205684', '202203695', '202200356', '202202365', '202207854', '202203125', '202206598', '202203256'];

echo "Starting targeted cleanup...\n";

foreach ($ids as $id) {
    // Find where this ID exists
    $entries = DB::table('billing_scholars')
        ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
        ->where('billing_scholars.student_id_no', $id)
        ->select('billing_scholars.id', 'billing_scholars.billing_batch_id', 'billing_batches.program')
        ->get();

    if ($entries->count() > 1) {
        echo "Found " . $entries->count() . " entries for ID: $id\n";
        // Keep the oldest one, remove the newer ones
        $sorted = $entries->sortBy('id');
        $toDelete = $sorted->slice(1);
        
        foreach ($toDelete as $entry) {
            echo "Removing duplicate of $id from Batch " . $entry->billing_batch_id . " (" . $entry->program . ")\n";
            DB::table('billing_scholars')->where('id', $entry->id)->delete();
            DB::table('billing_batches')->where('id', $entry->billing_batch_id)->decrement('scholar_count');
        }
    }
}

echo "Targeted cleanup complete.\n";
