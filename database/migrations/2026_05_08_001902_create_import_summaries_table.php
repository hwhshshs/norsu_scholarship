<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_summaries', function (Blueprint $row) {
            $row->id();
            $row->string('filename')->nullable();
            $row->string('program')->nullable();
            $row->string('semester')->nullable();
            $row->string('ay')->nullable();
            $row->integer('success_count')->default(0);
            $row->integer('duplicate_count')->default(0);
            $row->integer('conflict_count')->default(0);
            $row->integer('invalid_count')->default(0);
            $row->longText('report_data'); // JSON storage for names/IDs
            $row->unsignedBigInteger('created_by');
            $row->timestamps();
            
            $row->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_summaries');
    }
};
