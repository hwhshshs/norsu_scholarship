<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('student')->whereDate('joindate', date('Y-m-d'))->count();
echo "New students today: $count\n";

$last = DB::table('student')->orderBy('id', 'desc')->first();
if ($last) {
    echo "Last student created:\n";
    print_r($last);
} else {
    echo "No students found.\n";
}
