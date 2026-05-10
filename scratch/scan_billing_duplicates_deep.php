<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- DEEP BILLING DUPLICATE SCAN ---\n";

// 1. Check for batches with the exact same list of students
echo "\nChecking for batches with identical scholar lists...\n";
$batches = DB::table('billing_batches')->get();
$batchStudentSets = [];

foreach ($batches as $batch) {
    $scholars = DB::table('billing_scholars')
        ->where('billing_batch_id', $batch->id)
        ->orderBy('student_id_no')
        ->pluck('student_id_no')
        ->toArray();
    
    if (empty($scholars)) continue;

    $scholarKey = implode(',', $scholars);
    $batchStudentSets[$scholarKey][] = $batch;
}

$foundIdentical = false;
foreach ($batchStudentSets as $key => $matchingBatches) {
    if (count($matchingBatches) > 1) {
        $foundIdentical = true;
        echo "[!] Found " . count($matchingBatches) . " batches with the SAME student list:\n";
        foreach ($matchingBatches as $b) {
            echo "    - ID: {$b->id} | Batch No: {$b->batch} | Program: {$b->program} | AY: {$b->ay} | Sem: {$b->semester}\n";
        }
    }
}

if (!$foundIdentical) {
    echo "[OK] No batches with identical student lists found.\n";
}

// 2. Check for duplicate scholarship entries across ALL batches for the SAME period
echo "\nChecking for students receiving duplicate funding in the same AY/Sem...\n";
$dupeFunding = DB::table('billing_scholars')
    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
    ->select('billing_scholars.student_id_no', 'billing_batches.ay', 'billing_batches.semester', DB::raw('COUNT(*) as count'))
    ->groupBy('billing_scholars.student_id_no', 'billing_batches.ay', 'billing_batches.semester')
    ->having('count', '>', 1)
    ->get();

if ($dupeFunding->isEmpty()) {
    echo "[OK] No students found with duplicate funding in the same Period.\n";
} else {
    echo "[!] Found students with multiple entries in the same Period (AY/Sem):\n";
    foreach ($dupeFunding as $dupe) {
        echo "    - Student ID: {$dupe->student_id_no} | AY: {$dupe->ay} | Sem: {$dupe->semester} | Entries: {$dupe->count}\n";
        
        // Show which batches they are in
        $batchesWithStudent = DB::table('billing_scholars')
            ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
            ->where('billing_scholars.student_id_no', $dupe->student_id_no)
            ->where('billing_batches.ay', $dupe->ay)
            ->where('billing_batches.semester', $dupe->semester)
            ->select('billing_batches.id', 'billing_batches.program')
            ->get();
        
        foreach ($batchesWithStudent as $b) {
            echo "      -> Batch ID: {$b->id} ({$b->program})\n";
        }
    }
}

echo "\n--- SCAN COMPLETE ---\n";
