<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$id = 40;
$student = DB::table('student')->where('id', $id)->first();

echo "ID: " . $student->id . "\n";
echo "given_name: [" . ($student->given_name ?? 'NULL') . "]\n";
echo "middle_initial: [" . ($student->middle_initial ?? 'NULL') . "]\n";
echo "last_name: [" . ($student->last_name ?? 'NULL') . "]\n";
echo "sname: [" . ($student->sname ?? 'NULL') . "]\n";
echo "student_id_no: [" . ($student->student_id_no ?? 'NULL') . "]\n";
