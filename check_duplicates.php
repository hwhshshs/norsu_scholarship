<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$name = 'Ian';
$students = DB::table('student')->where('sname', 'like', "%$name%")->get();

echo "Students found with 'Ian':\n";
foreach ($students as $s) {
    echo "ID: {$s->id} | Name: {$s->sname} | ID No: {$s->student_id_no}\n";
    $tx = DB::table('fees_transaction')->where('stdid', $s->id)->count();
    echo "  - Billing Transactions: $tx\n";
    $dtx = DB::table('disbursed_transaction')->where('stdid', $s->id)->count();
    echo "  - Disbursed Transactions: $dtx\n";
}
