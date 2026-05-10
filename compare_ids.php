<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$items = DB::table('billing_scholars')->whereIn('id', [6, 27])->get();

foreach($items as $item) {
    echo "ID: {$item->id}\n";
    echo "SID: '{$item->student_id_no}'\n";
    echo "Length: " . strlen($item->student_id_no) . "\n";
    echo "Hex: " . bin2hex($item->student_id_no) . "\n\n";
}
