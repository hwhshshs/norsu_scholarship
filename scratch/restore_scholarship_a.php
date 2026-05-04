<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Restoring 'Scholarship A'...\n";

// 1. Re-insert the program definition with ID 9
try {
    DB::table('academic_program')->insert([
        'id' => 9,
        'name' => 'Scholarship A',
        'delete_status' => '0',
        'created_at' => \Illuminate\Support\Carbon::now(),
    ]);
    echo "Restored 'Scholarship A' (ID: 9) to the academic_program table.\n";
} catch (\Throwable $e) {
    echo "Error restoring program: " . $e->getMessage() . "\n";
}

echo "Restore complete!\n";
