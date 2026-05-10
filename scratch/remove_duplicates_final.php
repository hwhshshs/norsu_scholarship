<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batchId = 41;

echo "--- CLEANING UP BATCH $batchId ---\n";

DB::beginTransaction();
try {
    $scholarsDeleted = DB::table('billing_scholars')->where('billing_batch_id', $batchId)->delete();
    $batchDeleted = DB::table('billing_batches')->where('id', $batchId)->delete();
    
    DB::commit();
    echo "Deleted $scholarsDeleted scholars and Batch $batchId.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- SCANNING FOR OTHER PROGRAM CONFLICTS ---\n";
$conflicts = DB::table('billing_scholars as bs')
    ->join('students as s', 'bs.student_id', '=', 's.id')
    ->join('billing_batches as bb', 'bs.billing_batch_id', '=', 'bb.id')
    ->where('s.scholarship_program', '!=', 'N/A')
    ->whereColumn('s.scholarship_program', '!=', 'bb.program')
    ->select('bs.id', 'bs.student_id_no', 's.scholarship_program as master_program', 'bb.program as batch_program', 'bb.id as batch_id')
    ->get();

if ($conflicts->isEmpty()) {
    echo "No more program conflicts found.\n";
} else {
    echo "Found " . $conflicts->count() . " remaining conflicts. Removing them...\n";
    foreach($conflicts as $c) {
        DB::table('billing_scholars')->where('id', $c->id)->delete();
        // Update batch count
        DB::table('billing_batches')->where('id', $c->batch_id)->decrement('scholar_count');
        echo "  - Removed student {$c->student_id_no} from Batch {$c->batch_id} (Master says {$c->master_program}, Batch was {$c->batch_program})\n";
    }
}

echo "\nCleanup complete.\n";
