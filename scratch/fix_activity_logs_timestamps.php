<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Updating activity_logs table for timestamps...\n";

Schema::table('activity_logs', function(Blueprint $table) {
    if (!Schema::hasColumn('activity_logs', 'updated_at')) {
        $table->timestamp('updated_at')->nullable()->after('created_at');
        echo "Added updated_at column.\n";
    }
});

echo "Table update complete.\n";
