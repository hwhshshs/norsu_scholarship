<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- SCHOLARSHIP INTEGRITY SCAN: DUPLICATE DETECTION ---\n\n";

// 1. Check for duplicate Student IDs in master list
echo "[1/3] Checking for duplicate Student ID profiles...\n";
$duplicateProfiles = DB::table('students')
    ->select('student_id_no', DB::raw('count(*) as total'))
    ->groupBy('student_id_no')
    ->having('total', '>', 1)
    ->get();

if ($duplicateProfiles->isEmpty()) {
    echo "✓ No duplicate Student ID profiles found.\n";
} else {
    echo "⚠ Found " . $duplicateProfiles->count() . " IDs with multiple profiles:\n";
    foreach ($duplicateProfiles as $dp) {
        echo "  - ID: {$dp->student_id_no} ({$dp->total} records)\n";
        $records = DB::table('students')->where('student_id_no', $dp->student_id_no)->get();
        foreach($records as $r) {
            echo "    * [ID: {$r->id}] Name: {$r->last_name}, {$r->given_name} | Program: {$r->scholarship_program}\n";
        }
    }
}

echo "\n[2/3] Checking for double-funding (Students in multiple batches for same period)...\n";
$doubleFunded = DB::table('billing_scholars')
    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
    ->select(
        'billing_scholars.student_id_no', 
        'billing_batches.ay', 
        'billing_batches.semester', 
        DB::raw('count(*) as total')
    )
    ->groupBy('billing_scholars.student_id_no', 'billing_batches.ay', 'billing_batches.semester')
    ->having('total', '>', 1)
    ->get();

if ($doubleFunded->isEmpty()) {
    echo "✓ No double-funding duplicates found in the billing system.\n";
} else {
    echo "⚠ Found " . $doubleFunded->count() . " instances of double-funding:\n";
    foreach ($doubleFunded as $df) {
        echo "  - ID: {$df->student_id_no} | AY: {$df->ay} | Sem: {$df->semester} | Count: {$df->total}\n";
        $batches = DB::table('billing_scholars')
            ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
            ->where('billing_scholars.student_id_no', $df->student_id_no)
            ->where('billing_batches.ay', $df->ay)
            ->where('billing_batches.semester', $df->semester)
            ->select('billing_batches.id', 'billing_batches.program', 'billing_batches.ada_no', 'billing_batches.batch')
            ->get();
        foreach($batches as $b) {
            $paid = $b->ada_no ? " (PAID: {$b->ada_no})" : " (UNPAID)";
            echo "    * Batch #{$b->id} | Program: {$b->program} | Batch: {$b->batch}{$paid}\n";
        }
    }
}

echo "\n[3/3] Checking for exact row duplicates in same batch...\n";
$exactDupes = DB::table('billing_scholars')
    ->select('billing_batch_id', 'student_id_no', DB::raw('count(*) as total'))
    ->groupBy('billing_batch_id', 'student_id_no')
    ->having('total', '>', 1)
    ->get();

if ($exactDupes->isEmpty()) {
    echo "✓ No exact duplicate rows found within individual batches.\n";
} else {
    echo "⚠ Found " . $exactDupes->count() . " scholars duplicated in the same batch:\n";
    foreach ($exactDupes as $ed) {
        echo "  - Batch #{$ed->billing_batch_id} | Student ID: {$ed->student_id_no} | Count: {$ed->total}\n";
    }
}

echo "\n--- SCAN COMPLETE ---\n";
