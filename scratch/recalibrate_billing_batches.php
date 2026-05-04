<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Recalibrating Billing Batch totals and counts...\n";

$batches = DB::table('billing_batch')->where('delete_status', '0')->get();

foreach ($batches as $batch) {
    $stats = DB::table('fees_transaction')
        ->where('billing_batch_id', $batch->id)
        ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
        ->selectRaw("COUNT(DISTINCT stdid) as scholar_count, SUM(paid) as total_amount")
        ->first();

    $actualCount = (int) $stats->scholar_count;
    $actualAmount = (float) $stats->total_amount;

    if ($actualCount !== (int)$batch->scholar_count || abs($actualAmount - (float)$batch->billing_total_amount) > 0.01) {
        echo "Updating Batch ID {$batch->id} ({$batch->program}): Count {$batch->scholar_count} -> {$actualCount}, Amount {$batch->billing_total_amount} -> {$actualAmount}\n";
        
        DB::table('billing_batch')->where('id', $batch->id)->update([
            'scholar_count' => $actualCount,
            'billing_total_amount' => $actualAmount
        ]);
    }
}

echo "Recalibration complete!\n";
