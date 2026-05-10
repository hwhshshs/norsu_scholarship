<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- SYSTEM REPAIR: FIXING MALFORMED SCHOLAR NAMES ---\n";

$badScholars = DB::table('billing_scholars')
    ->where('student_name', 'REGEXP', '[0-9]{4}-[0-9]{4}')
    ->get();

if ($badScholars->isEmpty()) {
    echo "No malformed names found to fix.\n";
} else {
    foreach ($badScholars as $s) {
        $student = DB::table('students')
            ->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $s->student_id_no)
            ->first();
            
        if ($student) {
            $realName = "{$student->last_name}, {$student->given_name}";
            DB::table('billing_scholars')
                ->where('id', $s->id)
                ->update(['student_name' => $realName]);
            echo "  - Fixed Scholar ID {$s->id}: Set name to '$realName' (was '{$s->student_name}')\n";
        } else {
            echo "  - Warning: Could not find student record for ID {$s->student_id_no}. Skipping.\n";
        }
    }
}

echo "\nRepair complete.\n";
