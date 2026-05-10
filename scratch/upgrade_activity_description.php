<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Changing description column to TEXT in activity_logs...\n";

Schema::table('activity_logs', function(Blueprint $table) {
    $table->text('description')->change();
});

echo "Column upgrade complete.\n";
