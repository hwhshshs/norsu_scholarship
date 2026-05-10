<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- SYSTEM SCAN FOR MALFORMED SCHOLAR NAMES ---\n";

$badScholars = DB::table('billing_scholars')
    ->where('student_name', 'REGEXP', '[0-9]{4}-[0-9]{4}')
    ->select('id', 'billing_batch_id', 'student_id_no', 'student_name')
    ->get();

if ($badScholars->isEmpty()) {
    echo "No malformed names found in billing_scholars.\n";
} else {
    echo "Found " . $badScholars->count() . " scholars with malformed names:\n";
    foreach ($badScholars as $s) {
        echo "  - Scholar ID {$s->id} in Batch {$s->billing_batch_id}: ID {$s->student_id_no} has name '{$s->student_name}'\n";
    }
}

echo "\n--- SCANNING FOR MALFORMED STUDENT PROFILES ---\n";
$badStudents = DB::table('students')
    ->where('last_name', 'REGEXP', '[0-9]{4}-[0-9]{4}')
    ->orWhere('given_name', 'REGEXP', '[0-9]{4}-[0-9]{4}')
    ->select('id', 'student_id_no', 'last_name', 'given_name')
    ->get();

if ($badStudents->isEmpty()) {
    echo "No malformed names found in student profiles.\n";
} else {
    echo "Found " . $badStudents->count() . " malformed student profiles:\n";
    foreach ($badStudents as $s) {
        echo "  - Student ID {$s->id}: {$s->student_id_no} has name '{$s->last_name}, {$s->given_name}'\n";
    }
}
