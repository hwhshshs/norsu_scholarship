<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();
try {
    $unlinkedCount = DB::table('billing_scholars')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('students')
                ->whereRaw('students.student_id_no = billing_scholars.student_id_no');
        })
        ->delete();

    $batches = DB::table('billing_batches')->get();
    foreach ($batches as $batch) {
        $count = DB::table('billing_scholars')->where('billing_batch_id', $batch->id)->count();
        DB::table('billing_batches')->where('id', $batch->id)->update(['scholar_count' => $count]);
    }

    // Delete batches with zero scholars
    $emptyBatches = DB::table('billing_batches')->where('scholar_count', 0)->delete();

    DB::commit();
    echo "Deleted $unlinkedCount unlinked scholars, removed $emptyBatches empty batches, and synced counts.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
