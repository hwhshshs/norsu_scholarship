<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$id = '202205684'; 
$batch = DB::table('billing_batches')->where('id', 3)->first();
if (!$batch) {
    echo "Batch 3 not found.\n";
    exit;
}
echo "Target Batch 3 Period: " . $batch->ay . " " . $batch->semester . "\n";

$entries = DB::table('billing_scholars')
    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
    ->where('billing_scholars.student_id_no', $id)
    ->select('billing_scholars.id', 'billing_scholars.billing_batch_id', 'billing_batches.ay', 'billing_batches.semester', 'billing_batches.program')
    ->get();

if ($entries->isEmpty()) {
    echo "No entries found for ID: $id in billing_scholars.\n";
}

foreach($entries as $e) {
    echo "Exists in Batch " . $e->billing_batch_id . " (" . $e->program . ") for " . $e->ay . " " . $e->semester . "\n";
}

$student = DB::table('students')->where('student_id_no', $id)->first();
if ($student) {
    echo "Exists in Master List (students table). Name: " . $student->last_name . ", " . $student->given_name . "\n";
} else {
    echo "NOT found in Master List (students table).\n";
}
