<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tableName = 'student';
$columns = DB::select("SHOW COLUMNS FROM $tableName");

echo sprintf("%-20s | %-15s | %-10s | %-10s | %-10s\n", "Field", "Type", "Null", "Key", "Default");
echo str_repeat("-", 75) . "\n";
foreach ($columns as $column) {
    echo sprintf("%-20s | %-15s | %-10s | %-10s | %-10s\n", $column->Field, $column->Type, $column->Null, $column->Key, $column->Default);
}
