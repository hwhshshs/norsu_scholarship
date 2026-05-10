<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- SYSTEM DIAGNOSTIC REPORT ---\n\n";

// 1. Check for suspicious amounts
$highAmountBatches = DB::table('billing_batches')->where('amount', '>', 50000000)->get();
echo "Suspicious High-Amount Batches (> 50M):\n";
if ($highAmountBatches->isEmpty()) {
    echo "  [OK] None found.\n";
} else {
    foreach ($highAmountBatches as $b) {
        echo "  [ALERT] Batch #$b->id | $b->program | $b->ay | Amount: " . number_format($b->amount, 2) . "\n";
    }
}
echo "\n";

// 2. Check for suspicious Academic Years
$currentYear = intval(date('Y'));
$badAYBatches = DB::table('billing_batches')
    ->where('ay', 'NOT REGEXP', '^(' . ($currentYear-2) . '|' . ($currentYear-1) . '|' . $currentYear . '|' . ($currentYear+1) . ')')
    ->get();

echo "Suspicious Academic Years (Outside " . ($currentYear-2) . "-" . ($currentYear+1) . "):\n";
if ($badAYBatches->isEmpty()) {
    echo "  [OK] None found.\n";
} else {
    foreach ($badAYBatches as $b) {
        echo "  [ALERT] Batch #$b->id | $b->program | AY: $b->ay\n";
    }
}
echo "\n";

// 3. Check for FB links in Scholar Names
$corruptedScholars = DB::table('billing_scholars')
    ->where('student_name', 'like', '%http%')
    ->orWhere('student_name', 'like', '%.com%')
    ->get();

echo "Scholars with Links instead of Names:\n";
if ($corruptedScholars->isEmpty()) {
    echo "  [OK] None found.\n";
} else {
    foreach ($corruptedScholars as $s) {
        echo "  [ALERT] Scholar: $s->student_name | ID: $s->student_id_no | Batch: $s->billing_batch_id\n";
    }
}
echo "\n";

// 4. Check for invalid ID formats in Master List
$badIDStudents = DB::table('students')
    ->where('student_id_no', 'NOT REGEXP', '^(20|19)[0-9]{7}$')
    ->get();

echo "Students with Non-Standard IDs (Not 9 digits starting with 19/20):\n";
if ($badIDStudents->isEmpty()) {
    echo "  [OK] None found.\n";
} else {
    foreach ($badIDStudents as $s) {
        echo "  [ALERT] Student: $s->last_name, $s->given_name | ID: $s->student_id_no\n";
    }
}
echo "\n";

echo "--- SCAN COMPLETE ---\n";
