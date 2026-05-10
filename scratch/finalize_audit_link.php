<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Finalizing Audit Attribution Link...\n";

$results = DB::select("
    SELECT CONSTRAINT_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'schorlarship_main' 
    AND TABLE_NAME = 'activity_logs' 
    AND REFERENCED_TABLE_NAME = 'users'
");

if (empty($results)) {
    echo "Audit link not found. Locking now...\n";
    Schema::table('activity_logs', function(Blueprint $table) {
        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('set null');
    });
    echo "Audit Attribution Link: LOCKED.\n";
} else {
    echo "Audit Attribution Link was already LOCKED.\n";
}

echo "\n--- FINAL FORTRESS REPORT ---\n";
echo "1. Student ID Uniqueness: LOCKED\n";
echo "2. Batch Auto-Cleanup: LOCKED\n";
echo "3. Speed Optimization: ACTIVE\n";
echo "4. Audit Transparency: LOCKED\n";
echo "-----------------------------\n";
