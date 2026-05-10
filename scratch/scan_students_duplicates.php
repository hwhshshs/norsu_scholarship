<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- STUDENT MASTER LIST DUPLICATE SCAN ---\n";

echo "\nChecking for duplicate Student ID Numbers...\n";
$dupeIds = DB::table('students')
    ->select('student_id_no', DB::raw('COUNT(*) as count'))
    ->groupBy('student_id_no')
    ->having('count', '>', 1)
    ->get();

if ($dupeIds->isEmpty()) {
    echo "[OK] No duplicate Student ID numbers found.\n";
} else {
    echo "[!] Found duplicate Student ID numbers:\n";
    foreach ($dupeIds as $dupe) {
        echo "    - ID: {$dupe->student_id_no} ({$dupe->count} occurrences)\n";
    }
}

echo "\nChecking for duplicate Names (Last Name + Given Name)...\n";
$dupeNames = DB::table('students')
    ->select('last_name', 'given_name', DB::raw('COUNT(*) as count'))
    ->groupBy('last_name', 'given_name')
    ->having('count', '>', 1)
    ->get();

if ($dupeNames->isEmpty()) {
    echo "[OK] No duplicate names found.\n";
} else {
    echo "[!] Found duplicate student names:\n";
    foreach ($dupeNames as $dupe) {
        echo "    - Name: {$dupe->last_name}, {$dupe->given_name} ({$dupe->count} occurrences)\n";
    }
}

echo "\n--- SCAN COMPLETE ---\n";
