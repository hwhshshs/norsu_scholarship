<?php
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bbId = 75;
$row = DB::table('billing_batch as bb')
    ->where('bb.id', $bbId)
    ->selectRaw("(
        SELECT COUNT(DISTINCT dt.stdid)
        FROM disbursed_transaction dt
        WHERE dt.stdid IN (SELECT ft_in.stdid FROM fees_transaction ft_in WHERE ft_in.billing_batch_id = bb.id AND COALESCE(ft_in.record_type, 'billing') = 'billing')
          AND dt.program = bb.program
          AND dt.semester = bb.semester
          AND dt.academic_year = bb.academic_year
          AND COALESCE(dt.disbursed_status, 'draft') = 'finalized'
    ) AS count_test")
    ->selectRaw("(
        SELECT COALESCE(SUM(CASE WHEN COALESCE(dt.disbursed_status, 'draft') = 'finalized' THEN dt.disbursed_amount ELSE 0 END), 0)
        FROM disbursed_transaction dt
        WHERE dt.stdid IN (SELECT ft_in.stdid FROM fees_transaction ft_in WHERE ft_in.billing_batch_id = bb.id AND COALESCE(ft_in.record_type, 'billing') = 'billing')
          AND dt.program = bb.program
          AND dt.semester = bb.semester
          AND dt.academic_year = bb.academic_year
    ) AS amount_test")
    ->first();

echo "Batch ID: " . $bbId . "\n";
echo "Count Test: " . $row->count_test . "\n";
echo "Amount Test: " . $row->amount_test . "\n";
