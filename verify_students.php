<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$s40 = DB::table('student')->where('id', 40)->first();
$s42 = DB::table('student')->where('id', 42)->first();

echo "Student 40: " . ($s40 ? "EXISTS (" . $s40->sname . ")" : "MISSING") . "\n";
echo "Student 42: " . ($s42 ? "EXISTS (" . $s42->sname . ")" : "MISSING") . "\n";
