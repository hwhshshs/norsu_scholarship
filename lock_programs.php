<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$scholars = DB::table('billing_scholars')
    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
    ->select('billing_scholars.student_id', 'billing_batches.program')
    ->get();

$count = 0;
foreach($scholars as $s) {
    $affected = DB::table('students')
        ->where('id', $s->student_id)
        ->where('scholarship_program', 'N/A')
        ->update(['scholarship_program' => $s->program]);
    
    if ($affected) $count++;
}

echo "Global Program Lock Complete. Locked $count students to their official programs.\n";
