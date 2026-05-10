<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- INTRA-BATCH DUPLICATION SCAN ---\n";
$dupes = DB::table('billing_scholars')
    ->select('billing_batch_id', 'student_id_no', DB::raw('count(*) as count'))
    ->groupBy('billing_batch_id', 'student_id_no')
    ->having('count', '>', 1)
    ->get();

if ($dupes->isEmpty()) {
    echo "No intra-batch duplicates found.\n";
} else {
    foreach($dupes as $d) {
        echo "Batch {$d->billing_batch_id}: Student ID {$d->student_id_no} appears {$d->count} times.\n";
    }
}

echo "\n--- SAME-PERIOD DUPLICATION SCAN (Different Batches) ---\n";
$scholars = DB::table('billing_scholars as bs')
    ->join('billing_batches as bb', 'bs.billing_batch_id', '=', 'bb.id')
    ->select('bs.student_id_no', 'bb.ay', 'bb.semester', DB::raw('count(*) as count'))
    ->groupBy('bs.student_id_no', 'bb.ay', 'bb.semester')
    ->having('count', '>', 1)
    ->get();

if ($scholars->isEmpty()) {
    echo "No same-period duplicates found.\n";
} else {
    foreach($scholars as $s) {
        echo "Student ID {$s->student_id_no} appears {$s->count} times in period {$s->ay} {$s->semester}.\n";
        
        $details = DB::table('billing_scholars as bs')
            ->join('billing_batches as bb', 'bs.billing_batch_id', '=', 'bb.id')
            ->where('bs.student_id_no', $s->student_id_no)
            ->where('bb.ay', $s->ay)
            ->where('bb.semester', $s->semester)
            ->select('bb.id', 'bb.program')
            ->get();
        foreach($details as $det) {
            echo "  - Batch {$det->id} ({$det->program})\n";
        }
    }
}
