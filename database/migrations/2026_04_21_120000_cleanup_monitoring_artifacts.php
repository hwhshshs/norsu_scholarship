<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scholar_requirement_tracker')) {
            Schema::drop('scholar_requirement_tracker');
        }

        if (Schema::hasTable('scholar_upload_history')) {
            Schema::drop('scholar_upload_history');
        }

        if (Schema::hasTable('scholar_unmatched_records')) {
            Schema::drop('scholar_unmatched_records');
        }

        if (Schema::hasTable('scholar_alert_traps')) {
            Schema::drop('scholar_alert_traps');
        }

        if (Schema::hasTable('scholar_liquidation_tracker')) {
            Schema::drop('scholar_liquidation_tracker');
        }

        if (Schema::hasTable('disbursed_batch_details') && Schema::hasColumn('disbursed_batch_details', 'deadline_of_liquidation')) {
            Schema::table('disbursed_batch_details', function (Blueprint $table) {
                $table->dropColumn('deadline_of_liquidation');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('scholar_requirement_tracker')) {
            Schema::create('scholar_requirement_tracker', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('stdid')->unsigned();
                $table->string('cor_status', 20)->default('missing');
                $table->string('registration_form_status', 20)->default('missing');
                $table->string('grades_status', 20)->default('missing');
                $table->string('school_id_status', 20)->default('missing');
                $table->string('clearance_status', 20)->default('missing');
                $table->string('other_status', 20)->default('missing');
                $table->string('cor_file', 255)->default('');
                $table->string('registration_form_file', 255)->default('');
                $table->string('grades_file', 255)->default('');
                $table->string('school_id_file', 255)->default('');
                $table->string('clearance_file', 255)->default('');
                $table->string('other_file', 255)->default('');
                $table->string('completion_status', 30)->default('incomplete');
                $table->string('remarks', 255)->default('');
                $table->integer('updated_by')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique('stdid', 'uk_requirement_student');
            });
        }

        if (!Schema::hasTable('scholar_upload_history')) {
            Schema::create('scholar_upload_history', function (Blueprint $table) {
                $table->increments('id');
                $table->string('module_name', 80)->default('');
                $table->string('upload_type', 60)->default('');
                $table->string('file_name', 255)->default('');
                $table->string('file_path', 255)->default('');
                $table->integer('uploaded_by')->nullable();
                $table->integer('records_processed')->default(0);
                $table->integer('successful_rows')->default(0);
                $table->integer('failed_rows')->default(0);
                $table->integer('duplicates_skipped')->default(0);
                $table->string('status', 30)->default('completed');
                $table->string('summary', 500)->default('');
                $table->timestamp('created_at')->nullable();

                $table->index(['module_name', 'created_at'], 'idx_upload_module_date');
                $table->index('status', 'idx_upload_status');
            });
        }

        if (!Schema::hasTable('scholar_unmatched_records')) {
            Schema::create('scholar_unmatched_records', function (Blueprint $table) {
                $table->increments('id');
                $table->string('import_source', 40)->default('');
                $table->string('module_name', 80)->default('');
                $table->string('student_id_value', 100)->default('');
                $table->string('full_name', 255)->default('');
                $table->date('birthdate')->nullable();
                $table->string('school', 255)->default('');
                $table->integer('billing_batch_id')->nullable();
                $table->string('program', 150)->default('');
                $table->string('academic_year', 30)->default('');
                $table->string('semester', 60)->default('');
                $table->string('batch_label', 60)->default('');
                $table->string('region', 100)->default('');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('remarks', 255)->default('');
                $table->string('reason', 255)->default('');
                $table->text('original_row')->nullable();
                $table->string('resolution_status', 30)->default('pending');
                $table->integer('linked_student_id')->nullable();
                $table->string('resolution_note', 255)->default('');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('resolution_status', 'idx_unmatched_status');
                $table->index('import_source', 'idx_unmatched_source');
            });
        }

        if (!Schema::hasTable('scholar_alert_traps')) {
            Schema::create('scholar_alert_traps', function (Blueprint $table) {
                $table->increments('id');
                $table->string('alert_key', 120)->default('');
                $table->string('alert_type', 80)->default('');
                $table->string('severity', 20)->default('warning');
                $table->integer('stdid')->nullable();
                $table->integer('billing_batch_id')->nullable();
                $table->string('message', 500)->default('');
                $table->string('source_module', 80)->default('');
                $table->enum('is_resolved', ['0', '1'])->default('0');
                $table->dateTime('resolved_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique('alert_key', 'uk_alert_key');
                $table->index('alert_type', 'idx_alert_type');
                $table->index('is_resolved', 'idx_alert_resolved');
            });
        }

        if (!Schema::hasTable('scholar_liquidation_tracker')) {
            Schema::create('scholar_liquidation_tracker', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('stdid');
                $table->integer('billing_batch_id');
                $table->integer('disbursed_transaction_id')->nullable();
                $table->string('liquidation_status', 30)->default('no_liquidation_yet');
                $table->date('liquidation_due_date')->nullable();
                $table->date('liquidation_submitted_date')->nullable();
                $table->decimal('liquidation_amount', 12, 2)->default(0);
                $table->string('remarks', 255)->default('');
                $table->string('receipt_file', 255)->default('');
                $table->string('proof_payment_file', 255)->default('');
                $table->string('liquidation_report_file', 255)->default('');
                $table->string('supporting_document_file', 255)->default('');
                $table->integer('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['stdid', 'billing_batch_id'], 'uk_liquidation_student_batch');
                $table->index('liquidation_status', 'idx_liquidation_status');
                $table->index('liquidation_due_date', 'idx_liquidation_due_date');
            });
        }

        if (Schema::hasTable('disbursed_batch_details') && !Schema::hasColumn('disbursed_batch_details', 'deadline_of_liquidation')) {
            Schema::table('disbursed_batch_details', function (Blueprint $table) {
                $table->date('deadline_of_liquidation')->nullable();
            });
        }
    }
};
