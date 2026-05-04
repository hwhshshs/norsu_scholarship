<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$indexes = DB::select("SHOW INDEX FROM student");
foreach ($indexes as $idx) {
    if ($idx->Non_unique == 0) {
        echo "Unique Index: {$idx->Key_name} on {$idx->Column_name}\n";
    }
}
