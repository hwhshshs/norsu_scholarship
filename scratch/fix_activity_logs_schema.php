<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Updating activity_logs table...\n";

Schema::table('activity_logs', function(Blueprint $table) {
    if (!Schema::hasColumn('activity_logs', 'staff_name')) {
        $table->string('staff_name')->nullable()->after('description');
        echo "Added staff_name column.\n";
    }
    if (!Schema::hasColumn('activity_logs', 'staff_email')) {
        $table->string('staff_email')->nullable()->after('staff_name');
        echo "Added staff_email column.\n";
    }
});

echo "Table update complete.\n";
