<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batch = DB::table('billing_batches')->where('id', 2)->first();
echo "BATCH 2 INFO:\n";
echo "  Program: {$batch->program} | AY: {$batch->ay} | Sem: {$batch->semester}\n";

$scholars = DB::table('billing_scholars')
    ->where('billing_batch_id', 2)
    ->select('student_name', 'award_no', 'student_id_no')
    ->limit(10)
    ->get();

echo "\nSCHOLARS IN BATCH 2:\n";
foreach($scholars as $s) {
    echo "  - Name: {$s->student_name} | ID: {$s->student_id_no} | Award: {$s->award_no}\n";
}
