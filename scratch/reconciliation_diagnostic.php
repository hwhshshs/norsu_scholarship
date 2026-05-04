<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- Reconciliation Diagnostic ---\n";

$billingCount = DB::table('fees_transaction')->where('record_type', 'billing')->count();
echo "Total Billing Records: $billingCount\n";

$disbursedCount = DB::table('disbursed_transaction')->count();
echo "Total Disbursement Records: $disbursedCount\n";

$orphanedDisbursed = DB::table('disbursed_transaction as dt')
    ->leftJoin('billing_batch as bb', 'bb.id', '=', 'dt.billing_batch_id')
    ->whereNull('bb.id')
    ->count();
echo "Disbursements without a valid Batch: $orphanedDisbursed\n";

$mismatchedSums = DB::select("
    SELECT COUNT(*) as mismatch_count
    FROM (
        SELECT stdid, SUM(paid) as billed 
        FROM fees_transaction 
        WHERE record_type = 'billing' 
        GROUP BY stdid
    ) b
    JOIN (
        SELECT stdid, SUM(disbursed_amount) as paid 
        FROM disbursed_transaction 
        GROUP BY stdid
    ) d ON d.stdid = b.stdid
    WHERE ABS(b.billed - d.paid) > 0.01
");

echo "Students with Global Mismatches: " . ($mismatchedSums[0]->mismatch_count ?? 0) . "\n";

$ghostStudents = DB::select("
    SELECT DISTINCT stdid 
    FROM (
        SELECT stdid FROM fees_transaction 
        UNION 
        SELECT stdid FROM disbursed_transaction
    ) t 
    WHERE stdid NOT IN (SELECT id FROM student)
");
echo "Ghost Students (In transactions but NOT in Student table): " . count($ghostStudents) . "\n";
if (count($ghostStudents) > 0) {
    foreach ($ghostStudents as $gs) {
        echo " - Missing Student ID: " . $gs->stdid . "\n";
    }
}

echo "--- End Diagnostic ---\n";
