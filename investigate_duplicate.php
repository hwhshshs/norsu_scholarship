<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$id = '202203125';
$items = DB::table('billing_scholars')
    ->where('student_id_no', $id)
    ->get();

echo "Entries found for ID $id:\n";
foreach($items as $item) {
    echo "Entry ID: {$item->id} | Batch ID: {$item->billing_batch_id} | Name: {$item->student_name} | Created At: {$item->created_at}\n";
}
