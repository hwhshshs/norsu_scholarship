<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$programs = DB::table('academic_program')->get();
echo "Academic Programs:\n";
foreach ($programs as $p) {
    echo "- ID: {$p->id}, Name: '{$p->name}', Delete Status: {$p->delete_status}\n";
}

$studentsWithUnspecified = DB::table('student')
    ->where('scholarship_program', 'Unspecified Program')
    ->orWhere('degree_program', 'Unspecified Program')
    ->count();
echo "\nStudents with 'Unspecified Program': $studentsWithUnspecified\n";
