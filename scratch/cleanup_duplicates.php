<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Find duplicates across batches for the same period
$duplicates = DB::table('billing_scholars as bs1')
    ->join('billing_batches as bb1', 'bs1.billing_batch_id', '=', 'bb1.id')
    ->join('billing_scholars as bs2', function($join) {
        $join->on(DB::raw('REPLACE(REPLACE(bs1.student_id_no, "-", ""), " ", "")'), '=', DB::raw('REPLACE(REPLACE(bs2.student_id_no, "-", ""), " ", "")'))
             ->on('bs1.id', '<', 'bs2.id');
    })
    ->join('billing_batches as bb2', 'bs2.billing_batch_id', '=', 'bb2.id')
    ->whereColumn('bb1.ay', 'bb2.ay')
    ->whereColumn('bb1.semester', 'bb2.semester')
    ->select('bs2.id', 'bs2.student_name', 'bs2.billing_batch_id', 'bb2.ay', 'bb2.semester')
    ->get();

echo "Found " . $duplicates->count() . " duplicate/double-funded records.\n";

foreach($duplicates as $d) {
    echo "Deleting duplicate: " . $d->student_name . " from Batch " . $d->billing_batch_id . " (" . $d->ay . " " . $d->semester . ")\n";
    DB::table('billing_scholars')->where('id', $d->id)->delete();
    DB::table('billing_batches')->where('id', $d->billing_batch_id)->decrement('scholar_count');
}

echo "Duplicate cleanup complete.\n";
