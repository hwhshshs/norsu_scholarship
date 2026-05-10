<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Students master list
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('tdp_tes_award_no', 100)->default('N/A');
            $table->string('student_id_no', 50)->unique();
            $table->string('last_name', 100);
            $table->string('given_name', 100);
            $table->string('middle_initial', 10)->nullable();
            $table->string('degree_program', 200)->nullable();
            $table->string('year_level', 20)->nullable();
            $table->string('pwd_no', 100)->default('N/A');
            $table->string('ip_no', 100)->default('N/A');
            $table->string('email', 150)->nullable();
            $table->string('contact_no', 50)->nullable();
            $table->string('fb_link', 255)->nullable();
            $table->timestamps();
        });

        // Billing batches (also serves as disbursement records — same table, Option B)
        Schema::create('billing_batches', function (Blueprint $table) {
            $table->id();
            $table->string('program', 100);
            $table->string('semester', 50);
            $table->string('batch', 50)->nullable();
            $table->string('ay', 20);               // Academic Year e.g. "2025-2026"
            $table->string('region', 100)->nullable();
            $table->integer('scholar_count')->default(0);
            $table->date('billing_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);

            // Disbursement details (filled in on Billing page, viewed on Disbursement page)
            $table->date('ada_date')->nullable();    // Date on ADA Details
            $table->string('ada_no', 100)->nullable();
            $table->decimal('admin_cost', 15, 2)->default(0);
            $table->string('or_number', 100)->nullable();
            $table->date('or_date')->nullable();
            $table->integer('disbursed_count')->default(0); // Status: No of Students Disbursed

            $table->string('scholar_file', 255)->nullable(); // Uploaded CSV path
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Individual scholars linked to a billing batch (from CSV upload)
        Schema::create('billing_scholars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billing_batch_id');
            $table->unsignedBigInteger('student_id')->nullable(); // linked to students table if matched
            $table->string('student_name', 200);    // Name from CSV
            $table->string('student_id_no', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('billing_batch_id')->references('id')->on('billing_batches')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
        });

        // Activity logs for admin auditing
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('module', 50);
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('billing_scholars');
        Schema::dropIfExists('billing_batches');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('students');
    }
};
