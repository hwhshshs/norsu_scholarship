<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$unknowns = DB::table('students')
    ->where('last_name', 'like', '%Unknown%')
    ->orWhere('given_name', 'like', '%Unknown%')
    ->get();

$count = 0;
foreach($unknowns as $u) {
    // Delete from scholars first (FK)
    DB::table('billing_scholars')->where('student_id', $u->id)->delete();
    // Delete from students
    DB::table('students')->where('id', $u->id)->delete();
    echo "Deleted Unknown Student: {$u->last_name}, {$u->given_name} (ID: {$u->student_id_no})\n";
    $count++;
}

echo "Cleanup complete. Removed $count 'Unknown' records.\n";
