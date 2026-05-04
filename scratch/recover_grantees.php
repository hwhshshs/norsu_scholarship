<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting recovery of 'Scholarship A' grantees...\n";

// The data we found in temp_imports
$data = [
    [
        'id' => 202203637,
        'name' => 'Kim Barry Distrito',
        'ay' => '2025-2026',
        'sem' => '1st Semester',
        'program' => 'Scholarship A',
        'region' => 'VI',
        'billing_date' => '2026-03-22',
        'amount' => 2500.00,
        'remark' => 'Initial billing claim',
        'disbursed_date' => '2026-04-23'
    ]
    // Add more if found, but start with what we confirmed
];

foreach ($data as $row) {
    // 1. Ensure Batch exists
    $batchId = DB::table('billing_batch')->updateOrInsert(
        [
            'program' => $row['program'],
            'academic_year' => $row['ay'],
            'semester' => $row['sem'],
            'batch_label' => 'Restored Batch',
        ],
        [
            'region' => $row['region'],
            'billing_date' => $row['billing_date'],
            'billing_total_amount' => $row['amount'],
            'scholar_count' => 1,
            'status' => 'open',
            'delete_status' => '0',
            'created_at' => \Illuminate\Support\Carbon::now(),
        ]
    );
    
    $batch = DB::table('billing_batch')
        ->where('program', $row['program'])
        ->where('academic_year', $row['ay'])
        ->where('semester', $row['sem'])
        ->first();
    
    $batchId = $batch->id;

    // 2. Find internal student ID
    $student = DB::table('student')
        ->where('student_id_no', $row['id'])
        ->first();
    
    if (!$student) {
        echo "Warning: Student ID {$row['id']} not found in main table. Skipping transaction restoration.\n";
        continue;
    }

    // 3. Restore Fees Transaction
    DB::table('fees_transaction')->updateOrInsert(
        [
            'billing_batch_id' => $batchId,
            'stdid' => $student->id,
        ],
        [
            'submitdate' => $row['billing_date'],
            'paid' => $row['amount'],
            'program' => $row['program'],
            'semester' => $row['sem'],
            'academic_year' => $row['ay'],
            'record_type' => 'billing',
            'transcation_remark' => 'Data Recovery',
        ]
    );

    // 4. Restore Disbursement Log
    DB::table('disbursed_transaction')->updateOrInsert(
        [
            'billing_batch_id' => $batchId,
            'stdid' => $student->id,
        ],
        [
            'program' => $row['program'],
            'semester' => $row['sem'],
            'academic_year' => $row['ay'],
            'disbursed_date' => $row['disbursed_date'],
            'disbursed_amount' => $row['amount'],
            'remarks' => 'Data Recovery',
            'disbursed_status' => 'finalized',
        ]
    );

    echo "Restored grantee: {$row['name']} (Batch ID: $batchId)\n";
}

echo "Recovery complete!\n";
