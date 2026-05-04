<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

if (Schema::hasTable('scholar_upload_history')) {
    echo "Table 'scholar_upload_history' exists.\n";
    $columns = Schema::getColumnListing('scholar_upload_history');
    echo "Columns: " . implode(', ', $columns) . "\n";
    
    $count = DB::table('scholar_upload_history')->count();
    echo "Total Records: $count\n";
} else {
    echo "Table 'scholar_upload_history' DOES NOT exist.\n";
}
