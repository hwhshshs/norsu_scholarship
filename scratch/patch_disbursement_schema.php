<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Patching Disbursement Tables...\n";

if (!Schema::hasColumn('disbursed_transaction', 'created_by')) {
    Schema::table('disbursed_transaction', function (Blueprint $table) {
        $table->unsignedBigInteger('created_by')->nullable()->after('remarks');
    });
    echo "Added created_by to disbursed_transaction\n";
}

if (!Schema::hasColumn('disbursed_batch_details', 'created_by')) {
    Schema::table('disbursed_batch_details', function (Blueprint $table) {
        $table->unsignedBigInteger('created_by')->nullable()->after('status_students_disbursed');
    });
    echo "Added created_by to disbursed_batch_details\n";
}

echo "Database patch complete.\n";
