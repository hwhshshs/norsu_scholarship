<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- CROSS-BATCH STUDENT DUPLICATE SCAN ---\n";

$dupes = DB::table('billing_scholars')
    ->select('student_id_no', DB::raw('COUNT(*) as count'))
    ->groupBy('student_id_no')
    ->having('count', '>', 1)
    ->get();

if ($dupes->isEmpty()) {
    echo "[OK] No students found in multiple billing records.\n";
} else {
    echo "[!] Found students present in multiple billing records:\n";
    foreach ($dupes as $dupe) {
        echo "    - Student ID: {$dupe->student_id_no} ({$dupe->count} entries)\n";
        
        $entries = DB::table('billing_scholars')
            ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
            ->where('billing_scholars.student_id_no', $dupe->student_id_no)
            ->select('billing_batches.id', 'billing_batches.ay', 'billing_batches.program')
            ->get();
        
        foreach ($entries as $e) {
            echo "      -> Batch ID: {$e->id} | AY: {$e->ay} | Program: {$e->program}\n";
        }
    }
}

echo "\n--- SCAN COMPLETE ---\n";
