<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billing_batches', function (Blueprint $table) {
            $table->string('disbursement_scholar_file', 255)->nullable()->after('scholar_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_batches', function (Blueprint $table) {
            $table->dropColumn('disbursement_scholar_file');
        });
    }
};
