<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- DATA INTEGRITY SCAN ---\n";

$studentsCount = DB::table('students')->count();
echo "Total unique students in Master List: $studentsCount\n";

$batchSum = DB::table('billing_batches')->sum('scholar_count');
echo "Sum of 'scholar_count' across all batches: $batchSum\n";

echo "\n--- BATCH BREAKDOWN ---\n";
$batches = DB::table('billing_batches')->get();
foreach ($batches as $batch) {
    $linkedCount = DB::table('billing_scholars')->where('billing_batch_id', $batch->id)->count();
    
    echo "Batch ID: {$batch->id} (Program: {$batch->program}):\n";
    echo "  - Reported scholar_count field: {$batch->scholar_count}\n";
    echo "  - Actual linked students in billing_scholars: $linkedCount\n";
    
    if ($batch->scholar_count != $linkedCount) {
        echo "  [!] DISCREPANCY DETECTED in Batch ID: {$batch->id}\n";
    }
}

echo "\n--- SYSTEM OVERVIEW ---\n";
if ($studentsCount < $batchSum) {
    echo "EXPLANATION: The Dashboard shows the 'Sum of scholars across all billing batches' ($batchSum).\n";
    echo "Since your Master List only has $studentsCount unique students, it means some students are either:\n";
    echo "1. Being counted in multiple batches (e.g. Sem 1 and Sem 2).\n";
    echo "2. There are 'Ghost' entries in the batches where the scholar_count field was set higher than the actual number of student records.\n";
}
