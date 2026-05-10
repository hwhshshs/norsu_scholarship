<?php

use App\Support\ScholarshipMonitoring;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting Disbursement Cleanup for Incomplete Profiles...\n";

$disbursed = DB::table('disbursed_transaction')
    ->where('disbursed_status', 'finalized')
    ->get();

$removedCount = 0;
$affectedBatches = [];

foreach ($disbursed as $record) {
    $student = DB::table('student')->where('id', $record->stdid)->first();
    if (!$student) continue;

    $comp = ScholarshipMonitoring::isProfileComplete($student);
    if (!$comp['is_complete']) {
        echo "Removing disbursement for Student ID: {$student->student_id_no} (Incomplete: " . implode(', ', $comp['missing_fields']) . ")\n";
        
        DB::table('disbursed_transaction')
            ->where('id', $record->id)
            ->delete();
            
        $removedCount++;
        $affectedBatches[$record->billing_batch_id] = true;
    }
}

// Refresh batch statuses for affected batches
foreach (array_keys($affectedBatches) as $batchId) {
    $totalBilled = DB::table('fees_transaction')
        ->where('billing_batch_id', $batchId)
        ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
        ->count();

    $totalDisbursed = DB::table('disbursed_transaction')
        ->where('billing_batch_id', $batchId)
        ->where('disbursed_status', 'finalized')
        ->count();

    $status = ($totalDisbursed >= $totalBilled && $totalBilled > 0) ? 'finalized' : 'open';

    DB::table('billing_batch')->where('id', $batchId)->update([
        'status' => $status,
        'updated_at' => now(),
    ]);
    
    // Also update batch details count
    DB::table('disbursed_batch_details')
        ->where('billing_batch_id', $batchId)
        ->update(['status_students_disbursed' => $totalDisbursed]);
}

echo "\nCleanup Complete. Removed {$removedCount} disbursement indications.\n";
