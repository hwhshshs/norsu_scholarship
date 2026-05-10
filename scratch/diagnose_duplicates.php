<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- RECENT BATCHES ---\n";
$batches = DB::table('billing_batches')->orderBy('id', 'desc')->limit(10)->get();
foreach($batches as $b) {
    echo "ID: {$b->id} | Program: {$b->program} | Period: {$b->ay} {$b->semester} | Scholars: {$b->scholar_count}\n";
}

echo "\n--- SCHOLAR DUPLICATION SCAN (ANY PERIOD) ---\n";
$dupes = DB::table('billing_scholars')
    ->select('student_id_no', DB::raw('count(*) as count'))
    ->groupBy('student_id_no')
    ->having('count', '>', 1)
    ->get();

foreach($dupes as $d) {
    $records = DB::table('billing_scholars as bs')
        ->join('billing_batches as bb', 'bs.billing_batch_id', '=', 'bb.id')
        ->where('bs.student_id_no', $d->student_id_no)
        ->select('bs.id', 'bs.student_name', 'bb.program', 'bb.ay', 'bb.semester', 'bb.id as batch_id')
        ->get();
    
    echo "Student ID: {$d->student_id_no} appears {$d->count} times:\n";
    foreach($records as $r) {
        echo "  - Batch {$r->batch_id}: {$r->program} | {$r->ay} {$r->semester} | ID: {$r->id}\n";
    }
}
