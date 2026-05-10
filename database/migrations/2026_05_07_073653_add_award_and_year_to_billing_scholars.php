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
        Schema::table('billing_scholars', function (Blueprint $table) {
            $table->string('award_no')->nullable()->after('student_id_no');
            $table->string('year_level')->nullable()->after('award_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_scholars', function (Blueprint $table) {
            $table->dropColumn(['award_no', 'year_level']);
        });
    }
};
