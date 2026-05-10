<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$scholars = DB::table('billing_scholars')->get();
$count = 0;

foreach($scholars as $s) {
    $student = DB::table('students')->where('student_id_no', $s->student_id_no)->first();
    if($student) {
        DB::table('billing_scholars')->where('id', $s->id)->update([
            'student_id' => $student->id,
            'award_no' => $student->tdp_tes_award_no,
            'year_level' => $student->year_level
        ]);
        $count++;
    }
}

echo "Deep Sync Complete. Corrected $count scholar records.\n";
