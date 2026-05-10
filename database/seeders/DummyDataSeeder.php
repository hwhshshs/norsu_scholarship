<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Create some dummy students
        $students = [
            [
                'tdp_tes_award_no' => 'TDP-2025-001',
                'student_id_no' => '2023-0001',
                'last_name' => 'Dela Cruz',
                'given_name' => 'Juan',
                'middle_initial' => 'P',
                'degree_program' => 'BS in Information Technology',
                'year_level' => '3rd Year',
                'pwd_no' => 'N/A',
                'ip_no' => 'N/A',
                'email' => 'juan.delacruz@student.norsu.edu.ph',
                'contact_no' => '09123456789',
                'fb_link' => 'https://facebook.com/juandelacruz',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tdp_tes_award_no' => 'TDP-2025-002',
                'student_id_no' => '2023-0002',
                'last_name' => 'Rizal',
                'given_name' => 'Jose',
                'middle_initial' => 'P',
                'degree_program' => 'BS in Computer Science',
                'year_level' => '2nd Year',
                'pwd_no' => 'N/A',
                'ip_no' => 'N/A',
                'email' => 'jose.rizal@student.norsu.edu.ph',
                'contact_no' => '09987654321',
                'fb_link' => 'https://facebook.com/joserizal',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tdp_tes_award_no' => 'TES-2025-001',
                'student_id_no' => '2022-0015',
                'last_name' => 'Bonifacio',
                'given_name' => 'Andres',
                'middle_initial' => 'A',
                'degree_program' => 'BS in Civil Engineering',
                'year_level' => '4th Year',
                'pwd_no' => 'PWD-12345',
                'ip_no' => 'N/A',
                'email' => 'andres.bonifacio@student.norsu.edu.ph',
                'contact_no' => '09112223344',
                'fb_link' => 'https://facebook.com/andresbonifacio',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('students')->insert($students);

        // Fetch inserted students to link to billing
        $juan = DB::table('students')->where('student_id_no', '2023-0001')->first();
        $jose = DB::table('students')->where('student_id_no', '2023-0002')->first();
        $andres = DB::table('students')->where('student_id_no', '2022-0015')->first();

        // 2. Create a Billing Batch for TDP
        $batch1Id = DB::table('billing_batches')->insertGetId([
            'program' => 'TDP',
            'semester' => '1st Semester',
            'batch' => 'Batch 1',
            'ay' => '2025-2026',
            'region' => 'Region VII',
            'scholar_count' => 2,
            'billing_date' => Carbon::now()->subDays(10),
            'amount' => 50000.00,
            
            // It has disbursement info filled
            'ada_date' => Carbon::now()->subDays(2),
            'ada_no' => 'ADA-2025-9988',
            'admin_cost' => 500.00,
            'or_number' => 'OR-112233',
            'or_date' => Carbon::now()->subDays(1),
            'disbursed_count' => 2, // Fully disbursed
            
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Add Scholars to Batch 1
        DB::table('billing_scholars')->insert([
            [
                'billing_batch_id' => $batch1Id,
                'student_id' => $juan->id,
                'student_name' => $juan->last_name . ', ' . $juan->given_name,
                'student_id_no' => $juan->student_id_no,
                'created_at' => Carbon::now(),
            ],
            [
                'billing_batch_id' => $batch1Id,
                'student_id' => $jose->id,
                'student_name' => $jose->last_name . ', ' . $jose->given_name,
                'student_id_no' => $jose->student_id_no,
                'created_at' => Carbon::now(),
            ]
        ]);

        // 3. Create a Billing Batch for TES (Pending Disbursement)
        $batch2Id = DB::table('billing_batches')->insertGetId([
            'program' => 'TES',
            'semester' => '2nd Semester',
            'batch' => 'Batch 3',
            'ay' => '2024-2025',
            'region' => 'Region VII',
            'scholar_count' => 1,
            'billing_date' => Carbon::now()->subDays(1),
            'amount' => 40000.00,
            
            // No disbursement info yet (Pending)
            'ada_date' => null,
            'ada_no' => null,
            'admin_cost' => 0.00,
            'or_number' => null,
            'or_date' => null,
            'disbursed_count' => 0, 
            
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Add Scholar to Batch 2
        DB::table('billing_scholars')->insert([
            [
                'billing_batch_id' => $batch2Id,
                'student_id' => $andres->id,
                'student_name' => $andres->last_name . ', ' . $andres->given_name,
                'student_id_no' => $andres->student_id_no,
                'created_at' => Carbon::now(),
            ]
        ]);
        
        // Log activity
        DB::table('activity_logs')->insert([
            'user_id' => 1,
            'module' => 'System',
            'action' => 'Dummy Data Seeded',
            'description' => 'Generated dummy students and billing batches.',
            'created_at' => Carbon::now(),
        ]);
    }
}
