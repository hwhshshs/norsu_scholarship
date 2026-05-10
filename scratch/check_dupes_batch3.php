<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dupes = DB::table('billing_scholars')
    ->where('billing_batch_id', 3)
    ->select('student_id_no', DB::raw('COUNT(*) as count'))
    ->groupBy('student_id_no')
    ->having('count', '>', 1)
    ->get();

if ($dupes->isEmpty()) {
    echo "No actual duplicates found in Batch 3.\n";
    
    // Let's check the total count
    $count = DB::table('billing_scholars')->where('billing_batch_id', 3)->count();
    echo "Total scholars in Batch 3: $count\n";
    
    // Let's list some IDs to be sure
    $ids = DB::table('billing_scholars')->where('billing_batch_id', 3)->limit(5)->pluck('student_id_no');
    echo "Sample IDs in Batch 3: " . implode(', ', $ids->toArray()) . "\n";

} else {
    echo "FOUND DUPLICATES IN BATCH 3:\n";
    foreach ($dupes as $d) {
        echo "ID: " . $d->student_id_no . " appeared " . $d->count . " times.\n";
    }
}
