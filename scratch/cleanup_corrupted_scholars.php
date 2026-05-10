<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting cleanup of corrupted scholars...\n";

DB::beginTransaction();
try {
    // 1. Delete billing_scholars with FB links or wrong IDs
    $corruptedBillingCount = DB::table('billing_scholars')
        ->where('student_name', 'like', '%http%')
        ->orWhere('student_name', 'like', '%.com%')
        ->orWhere('student_name', 'like', '%facebook%')
        ->orWhere(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), 'NOT REGEXP', '^(20|19)[0-9]{7}$')
        ->delete();

    echo "Deleted $corruptedBillingCount corrupted entries from Billing Scholars list.\n";

    // 2. Delete ghost students from Master List
    $ghostStudentCount = DB::table('students')
        ->where('last_name', 'like', '%http%')
        ->orWhere('given_name', 'like', '%http%')
        ->orWhere('student_id_no', 'NOT REGEXP', '^(20|19)[0-9]{7}$')
        ->delete();

    echo "Deleted $ghostStudentCount ghost students from the Master List.\n";

    DB::commit();
    echo "Cleanup successful! Your system is now clean.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
