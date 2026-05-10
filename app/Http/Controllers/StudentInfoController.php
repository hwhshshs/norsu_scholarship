<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentInfoController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('students');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('student_id_no', 'like', $search)
                  ->orWhere('last_name', 'like', $search)
                  ->orWhere('given_name', 'like', $search)
                  ->orWhere('tdp_tes_award_no', 'like', $search)
                  ->orWhere('scholarship_program', 'like', $search);
            });
        }

        $students = $query->orderBy('last_name')->orderBy('given_name')->paginate(50);

        return view('student-info.index', compact('students'));
    }

    public function create()
    {
        return view('student-info.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tdp_tes_award_no' => 'nullable|string|max:100',
            'student_id_no' => 'required|digits:9|regex:/^(20|19)\d{7}$/|unique:students,student_id_no',
            'last_name' => 'required|string|max:100',
            'given_name' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'degree_program' => 'nullable|string|max:200',
            'year_level' => 'nullable|string|max:20',
            'pwd_no' => 'nullable|string|max:100',
            'ip_no' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'contact_no' => 'nullable|string|max:50',
            'fb_link' => 'nullable|string|max:255',
            'scholarship_program' => 'nullable|string|max:50',
        ]);

        // Standardize ID: Remove dashes
        $validated['student_id_no'] = str_replace('-', '', $validated['student_id_no']);

        $validated['tdp_tes_award_no'] = $validated['tdp_tes_award_no'] ?: 'N/A';
        $validated['pwd_no'] = $validated['pwd_no'] ?: 'N/A';
        $validated['ip_no'] = $validated['ip_no'] ?: 'N/A';
        $validated['scholarship_program'] = ($validated['scholarship_program'] ?? 'N/A') ?: 'N/A';
        
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        DB::table('students')->insert($validated);

        $this->logActivity('Student Info', 'Created student', "Added student ID: {$validated['student_id_no']}");

        return redirect()->route('student-info.index')->with('success', 'Student added successfully.');
    }

    public function show($id)
    {
        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) abort(404);

        return view('student-info.show', compact('student'));
    }

    public function edit($id)
    {
        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) abort(404);

        return view('student-info.edit', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) abort(404);

        $validated = $request->validate([
            'tdp_tes_award_no' => 'nullable|string|max:100',
            'student_id_no' => 'required|digits:9|regex:/^(20|19)\d{7}$/|unique:students,student_id_no,' . $id,
            'last_name' => 'required|string|max:100',
            'given_name' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'degree_program' => 'nullable|string|max:200',
            'year_level' => 'nullable|string|max:20',
            'pwd_no' => 'nullable|string|max:100',
            'ip_no' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'contact_no' => 'nullable|string|max:50',
            'fb_link' => 'nullable|string|max:255',
            'scholarship_program' => 'nullable|string|max:50',
        ]);

        // Standardize ID: Remove dashes
        $validated['student_id_no'] = str_replace('-', '', $validated['student_id_no']);

        $validated['tdp_tes_award_no'] = $validated['tdp_tes_award_no'] ?: 'N/A';
        $validated['pwd_no'] = $validated['pwd_no'] ?: 'N/A';
        $validated['ip_no'] = $validated['ip_no'] ?: 'N/A';
        $validated['scholarship_program'] = ($validated['scholarship_program'] ?? 'N/A') ?: 'N/A';
        $validated['updated_at'] = now();

        DB::table('students')->where('id', $id)->update($validated);

        $this->logActivity('Student Info', 'Updated student', "Updated student ID: {$validated['student_id_no']}");

        return redirect()->route('student-info.index')->with('success', 'Student updated successfully.');
    }

    public function quickUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'scholarship_program' => 'required|string|max:50',
            'tdp_tes_award_no' => 'nullable|string|max:100',
        ]);

        $student = DB::table('students')->where('id', $id)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $awardNo = $validated['tdp_tes_award_no'] ?: 'N/A';

        DB::table('students')->where('id', $id)->update([
            'scholarship_program' => $validated['scholarship_program'],
            'tdp_tes_award_no' => $awardNo,
            'updated_at' => now()
        ]);

        $this->logActivity('Student Info', 'Quick Program Switch', "Switched student ID: {$student->student_id_no} to {$validated['scholarship_program']} (Award: {$awardNo})");

        return response()->json([
            'success' => true, 
            'program' => $validated['scholarship_program'],
            'award_no' => $awardNo
        ]);
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ];

        $columns = [
            'TDP-TES Award No', 'Student ID No', 'Last Name', 'Given Name', 'Middle Initial',
            'Degree Program', 'Year Level', 'PWD No', 'IP No', 'Email', 'Contact No', 'FB Link'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), "r");
        
        $header = fgetcsv($handle, 1000, ",");
        if (!$header) {
            return back()->with('error', 'Invalid CSV file format.');
        }

        $expectedColumns = [
            'TDP-TES Award No', 'Student ID No', 'Last Name', 'Given Name', 'Middle Initial',
            'Degree Program', 'Year Level', 'PWD No', 'IP No', 'Email', 'Contact No', 'FB Link'
        ];

        // ROBOT INTELLIGENCE: Build audit report
        $report = [
            'success' => 0,
            'duplicate' => 0,
            'conflict' => 0,
            'invalid' => 0,
            'total_rows' => 0,
            'success_list' => [],
            'duplicate_list' => [],
            'conflict_list' => [],
            'invalid_list' => [],
        ];

        $processedIdsInSession = [];

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $report['total_rows']++;

                if (count($data) < 12) {
                    // ROBOT: Track malformed rows
                    $report['invalid']++;
                    if (count($report['invalid_list']) < 50) {
                        $report['invalid_list'][] = ['name' => trim($data[2] ?? 'Unknown') . ', ' . trim($data[3] ?? ''), 'id' => 'Malformed Row'];
                    }
                    continue;
                }
                
                $studentIdNo = str_replace('-', '', trim($data[1]));
                $studentName = trim($data[2]) . ', ' . trim($data[3]);

                if (empty($studentIdNo)) {
                    // ROBOT: Track rows with missing ID
                    $report['invalid']++;
                    if (count($report['invalid_list']) < 50) {
                        $report['invalid_list'][] = ['name' => $studentName ?: 'Unknown', 'id' => 'Missing ID'];
                    }
                    continue;
                }

                // Validate Student ID format (must be 9 digits starting with 20 or 19)
                if (!preg_match('/^(20|19)\d{7}$/', $studentIdNo)) {
                    $report['invalid']++;
                    if (count($report['invalid_list']) < 50) {
                        $report['invalid_list'][] = ['name' => $studentName, 'id' => $studentIdNo, 'reason' => 'Invalid ID Format'];
                    }
                    continue;
                }

                // ROBOT: Session deduplication - prevent same ID twice in one upload
                if (in_array($studentIdNo, $processedIdsInSession)) {
                    $report['duplicate']++;
                    if (count($report['duplicate_list']) < 50) {
                        $report['duplicate_list'][] = ['name' => $studentName, 'id' => $studentIdNo];
                    }
                    continue;
                }
                $processedIdsInSession[] = $studentIdNo;

                $rawProgram = trim($data[5]);
                $rawYear = trim($data[6]);

                // SMART MAPPING: Degrees
                $programMap = [
                    'BSInT' => 'Bachelor of Science in Information Technology',
                    'CSIT' => 'Bachelor of Science in Information Technology',
                    'BSIT' => 'Bachelor of Science in Industrial Technology',
                    'BSCS' => 'Bachelor of Science in Computer Science',
                    'BSBA' => 'Bachelor of Science in Business Administration',
                    'BSED' => 'Bachelor of Secondary Education',
                    'BEED' => 'Bachelor of Elementary Education',
                    'BSCrim' => 'Bachelor of Science in Criminology',
                    'BSCE' => 'Bachelor of Science in Civil Engineering',
                    'BSEE' => 'Bachelor of Science in Electrical Engineering',
                    'BSME' => 'Bachelor of Science in Mechanical Engineering',
                ];

                // SMART MAPPING: Year Levels
                $yearMap = [
                    '1st' => '1ST YEAR', '1st Year' => '1ST YEAR', '1st year' => '1ST YEAR',
                    '2nd' => '2ND YEAR', '2nd Year' => '2ND YEAR', '2nd year' => '2ND YEAR',
                    '3rd' => '3RD YEAR', '3rd Year' => '3RD YEAR', '3rd year' => '3RD YEAR',
                    '4th' => '4TH YEAR', '4th Year' => '4TH YEAR', '4th year' => '4TH YEAR',
                    '5th' => '5TH YEAR', '5th Year' => '5TH YEAR', '5th year' => '5TH YEAR',
                ];

                $finalProgram = $programMap[$rawProgram] ?? $rawProgram;
                $finalYear = $yearMap[$rawYear] ?? strtoupper($rawYear);

                $studentData = [
                    'tdp_tes_award_no' => trim($data[0]) ?: 'N/A',
                    'student_id_no' => $studentIdNo,
                    'last_name' => trim($data[2]),
                    'given_name' => trim($data[3]),
                    'middle_initial' => trim($data[4]),
                    'degree_program' => $finalProgram,
                    'year_level' => $finalYear,
                    'pwd_no' => trim($data[7]) ?: 'N/A',
                    'ip_no' => trim($data[8]) ?: 'N/A',
                    'email' => trim($data[9]),
                    'contact_no' => trim($data[10]),
                    'fb_link' => trim($data[11]),
                    'updated_at' => now(),
                ];

                $existing = DB::table('students')->where('student_id_no', $studentIdNo)->first();
                if ($existing) {
                    DB::table('students')->where('id', $existing->id)->update($studentData);
                    // ROBOT: Track as duplicate (existing record updated)
                    $report['duplicate']++;
                    if (count($report['duplicate_list']) < 50) {
                        $report['duplicate_list'][] = ['name' => $studentName, 'id' => $studentIdNo];
                    }
                } else {
                    $studentData['created_at'] = now();
                    DB::table('students')->insert($studentData);
                    // ROBOT: Track as success (new record)
                    $report['success']++;
                    if (count($report['success_list']) < 50) {
                        $report['success_list'][] = ['name' => $studentName, 'id' => $studentIdNo];
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Student import error: ' . $e->getMessage());
            fclose($handle);
            return back()->with('error', 'Failed to import students. Please check your CSV format.');
        }
        
        fclose($handle);

        // ROBOT INTELLIGENCE: Save audit report to import_summaries for the Intelligence Timeline
        try {
            $summaryId = DB::table('import_summaries')->insertGetId([
                'filename' => $file->getClientOriginalName(),
                'program' => 'Student Master List',
                'ay' => 'N/A',
                'semester' => 'N/A',
                'success_count' => $report['success'],
                'duplicate_count' => $report['duplicate'],
                'conflict_count' => $report['conflict'],
                'invalid_count' => $report['invalid'],
                'report_data' => json_encode($report),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $report['id'] = $summaryId;
        } catch (\Exception $e) {
            Log::error('Import summary save error: ' . $e->getMessage());
        }

        $this->logActivity('Student Info', 'Batch Import', "Imported {$report['success']} new, Updated {$report['duplicate']} existing, {$report['invalid']} invalid rows from {$file->getClientOriginalName()}");

        return back()
            ->with('import_report', $report)
            ->with('success', "Import complete. Added: {$report['success']}, Updated: {$report['duplicate']}, Invalid: {$report['invalid']}. Check the Robot Intelligence for details.");
    }

    public function getHistory($id)
    {
        $history = DB::table('billing_scholars')
            ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
            ->where('billing_scholars.student_id', $id)
            ->select(
                'billing_batches.ay',
                'billing_batches.semester',
                'billing_batches.program',
                DB::raw("CASE WHEN billing_batches.ada_no IS NOT NULL THEN 'Paid' ELSE 'Pending' END as status"),
                'billing_scholars.created_at'
            )
            ->orderBy('billing_batches.ay', 'desc')
            ->orderBy('billing_batches.semester', 'desc')
            ->get();

        return response()->json($history);
    }

    private function logActivity($module, $action, $description)
    {
        DB::table('activity_logs')->insert([
            'user_id' => auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
