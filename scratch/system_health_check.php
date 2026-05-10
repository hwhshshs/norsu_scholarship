<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- SYSTEM HEALTH CHECK ---\n";

// 1. Check for orphaned scholars
$orphanedScholars = DB::table('billing_scholars')
    ->leftJoin('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
    ->whereNull('billing_batches.id')
    ->count();

echo "Orphaned Scholars (No Batch): " . ($orphanedScholars > 0 ? "⚠️ $orphanedScholars found!" : "✅ 0 found") . "\n";

// 2. Check for empty batches
$emptyBatches = DB::table('billing_batches')
    ->where('scholar_count', '<=', 0)
    ->count();

echo "Empty Batches: " . ($emptyBatches > 0 ? "⚠️ $emptyBatches found!" : "✅ 0 found") . "\n";

// 3. Verify Batch Totals vs Scholar Records
$batches = DB::table('billing_batches')->get();
$mismatches = 0;

foreach ($batches as $batch) {
    $actualCount = DB::table('billing_scholars')->where('billing_batch_id', $batch->id)->count();
    if ($actualCount != $batch->scholar_count) {
        echo "⚠️ Mismatch in Batch ID {$batch->id} ({$batch->program}): Record says {$batch->scholar_count}, but found $actualCount scholars.\n";
        $mismatches++;
    }
}

if ($mismatches == 0) {
    echo "Batch Count Consistency: ✅ Perfect\n";
}

// 4. Check for invalid Academic Years
$invalidYears = DB::table('billing_batches')
    ->where('ay', 'NOT LIKE', '20%')
    ->count();

echo "Invalid AY Formats: " . ($invalidYears > 0 ? "⚠️ $invalidYears found!" : "✅ 0 found") . "\n";

// 5. Check for null or zero amounts in active batches
$zeroAmounts = DB::table('billing_batches')
    ->where('amount', '<=', 0)
    ->count();

echo "Batches with Zero Amount: " . ($zeroAmounts > 0 ? "⚠️ $zeroAmounts found!" : "✅ 0 found") . "\n";

echo "\n--- AUDIT COMPLETE ---\n";
