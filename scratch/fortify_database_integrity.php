<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Starting Database Hard-Lock Fortification...\n";

// 1. Clean up any accidental duplicate Student IDs before adding Unique constraint
echo "Step 1: Checking for duplicate Student IDs...\n";
$duplicates = DB::table('students')
    ->select('student_id_no', DB::raw('COUNT(*) as count'))
    ->groupBy('student_id_no')
    ->having('count', '>', 1)
    ->get();

foreach ($duplicates as $duplicate) {
    echo "Cleaning up duplicate: {$duplicate->student_id_no}\n";
    $ids = DB::table('students')
        ->where('student_id_no', $duplicate->student_id_no)
        ->orderBy('id', 'desc')
        ->pluck('id');
    
    $toKeep = $ids->shift(); // Keep the newest one
    DB::table('students')->whereIn('id', $ids)->delete();
}

// 2. Apply Schema Hard-Locks
echo "Step 2: Applying Schema Hard-Locks...\n";

Schema::table('students', function(Blueprint $table) {
    // Unique ID Enforcement
    if (!IndexExists('students', 'students_student_id_no_unique')) {
        $table->unique('student_id_no');
        echo "- Student ID Uniqueness: LOCKED.\n";
    }
    // Search Optimization
    if (!IndexExists('students', 'students_scholarship_program_index')) {
        $table->index('scholarship_program');
        echo "- Scholarship Program Index: ADDED.\n";
    }
});

Schema::table('billing_batches', function(Blueprint $table) {
    // Performance Indexes
    if (!IndexExists('billing_batches', 'billing_batches_ay_index')) {
        $table->index('ay');
        echo "- Academic Year Index: ADDED.\n";
    }
    if (!IndexExists('billing_batches', 'billing_batches_semester_index')) {
        $table->index('semester');
        echo "- Semester Index: ADDED.\n";
    }
});

Schema::table('billing_scholars', function(Blueprint $table) {
    // Foreign Key Constraints (Auto-Cleanup)
    try {
        $table->foreign('billing_batch_id')
              ->references('id')
              ->on('billing_batches')
              ->onDelete('cascade');
        echo "- Batch Auto-Cleanup Link: LOCKED.\n";
    } catch (\Exception $e) { echo "- Batch Link: Already set or exists.\n"; }

    try {
        $table->foreign('student_id')
              ->references('id')
              ->on('students')
              ->onDelete('cascade');
        echo "- Student Profile Link: LOCKED.\n";
    } catch (\Exception $e) { echo "- Student Link: Already set or exists.\n"; }
});

Schema::table('activity_logs', function(Blueprint $table) {
    // Audit Attribution Link
    try {
        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('set null');
        echo "- Audit Attribution Link: LOCKED.\n";
    } catch (\Exception $e) { echo "- Audit Link: Already set or exists.\n"; }
});

echo "\nFortification Complete! Your database is now a High-Integrity Fortress. 🏰🦾\n";

function IndexExists($table, $index) {
    $conn = DB::connection();
    $dbName = $conn->getDatabaseName();
    $results = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
    return !empty($results);
}
