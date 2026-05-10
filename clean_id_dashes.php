<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$students = DB::table('students')->where('student_id_no', 'like', '%-%')->get();
$count = 0;

foreach($students as $s) {
    $cleanId = str_replace('-', '', $s->student_id_no);
    
    // Update linked records first
    DB::table('billing_scholars')->where('student_id_no', $s->student_id_no)->update(['student_id_no' => $cleanId]);
    
    // Update master record
    DB::table('students')->where('id', $s->id)->update(['student_id_no' => $cleanId]);
    
    echo "Cleaned ID: {$s->student_id_no} -> $cleanId\n";
    $count++;
}

echo "Database cleaned. $count IDs formatted to numeric only.\n";
