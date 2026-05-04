<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$name = 'Maranga';
$students = DB::table('student')->where('sname', 'like', "%$name%")->get();

if ($students->isEmpty()) {
    echo "No students found with 'Maranga' in name.\n";
    exit;
}

foreach ($students as $student) {
    echo "ID: {$student->id} | Name: {$student->sname} | ID No: {$student->student_id_no}\n";
}
