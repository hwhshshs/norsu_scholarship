<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- DEEP SYSTEM AUDIT: INTELLIGENCE BRIEFING ---\n\n";

// 1. SCAN: Malformed Student IDs (Non-9 digits or containing dashes)
echo "[1/5] Auditing Student ID Formatting...\n";
$badIds = DB::table('students')
    ->whereRaw('LENGTH(REPLACE(student_id_no, "-", "")) != 9 OR student_id_no LIKE "%-%"')
    ->get();

if ($badIds->isEmpty()) {
    echo "✓ All Student IDs are perfectly formatted (9-digit, no dashes).\n";
} else {
    echo "⚠ Found " . $badIds->count() . " malformed IDs in Master List. (Will need normalization).\n";
}

// 2. SCAN: Count Discrepancies
echo "\n[2/5] Auditing Batch Scholar Counts...\n";
$batches = DB::table('billing_batches')->get();
$mismatches = 0;
foreach ($batches as $batch) {
    $actualCount = DB::table('billing_scholars')->where('billing_batch_id', $batch->id)->count();
    if ($actualCount != $batch->scholar_count) {
        $mismatches++;
        echo "  - Batch #{$batch->id} ({$batch->program}): Reported {$batch->scholar_count}, but actually has {$actualCount} scholars.\n";
    }
}
if ($mismatches === 0) echo "✓ All batch counts are perfectly synchronized.\n";

// 3. SCAN: Program Lock Violations
echo "\n[3/5] Auditing Scholarship Program Integrity...\n";
$lockViolations = DB::table('billing_scholars')
    ->join('students', 'billing_scholars.student_id', '=', 'students.id')
    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
    ->whereRaw('students.scholarship_program != billing_batches.program')
    ->where('students.scholarship_program', '!=', 'N/A')
    ->select('billing_scholars.student_name', 'students.scholarship_program as locked_to', 'billing_batches.program as found_in')
    ->get();

if ($lockViolations->isEmpty()) {
    echo "✓ No program lock violations found. All students are in their correct scholarship categories.\n";
} else {
    echo "⚠ Found " . $lockViolations->count() . " students in the wrong program batches.\n";
}

// 4. SCAN: Future Dated Records
echo "\n[4/5] Auditing Time-Gate Integrity...\n";
$currentYear = date('Y');
$futureBatches = DB::table('billing_batches')
    ->whereRaw("SUBSTRING_INDEX(ay, '-', 1) > " . ($currentYear + 1))
    ->get();

if ($futureBatches->isEmpty()) {
    echo "✓ No future-dated batches found. All records are within valid time ranges.\n";
} else {
    echo "⚠ Found " . $futureBatches->count() . " future-dated batches (e.g., AY 2027+).\n";
}

// 5. SCAN: Ghost Profiles
echo "\n[5/5] Auditing Student Profile Links...\n";
$ghosts = DB::table('billing_scholars')
    ->leftJoin('students', 'billing_scholars.student_id', '=', 'students.id')
    ->whereNull('students.id')
    ->get();

if ($ghosts->isEmpty()) {
    echo "✓ Every scholar in your batches is properly linked to a Master Profile.\n";
} else {
    echo "⚠ Found " . $ghosts->count() . " scholars without a master profile link.\n";
}

echo "\n--- AUDIT COMPLETE ---\n";
