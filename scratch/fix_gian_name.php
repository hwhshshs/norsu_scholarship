<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$affected = DB::table('student')
    ->where('given_name', 'Gian')
    ->where('last_name', 'Tano')
    ->update([
        'given_name' => 'Gian Anthon',
        'middle_initial' => '',
        'sname' => 'Tano, Gian Anthon'
    ]);

echo "Successfully updated $affected student(s) to fix the name 'Gian Anthon Tano'.\n";
