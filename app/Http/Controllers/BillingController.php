<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('billing_batches');

        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('ay')) {
            $query->where('ay', $request->ay);
        }

        // Calculate totals for filtered dataset
        $totals = [
            'amount' => (clone $query)->sum('amount'),
            'scholars' => (clone $query)->sum('scholar_count'),
            'count' => (clone $query)->count(),
        ];

        $batches = $query->orderBy('program')->orderByDesc('ay')->orderByDesc('created_at')->paginate(50);

        return view('billing.index', compact('batches', 'totals'));
    }

    public function create()
    {
        return view('billing.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program' => 'required|string|max:100',
            'semester' => 'required|string|max:50',
            'batch' => 'nullable|string|max:50',
            'ay' => 'required|string|max:20',
            'region' => 'nullable|string|max:100',
            'billing_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'ada_date' => 'nullable|date',
            'ada_no' => 'nullable|string|max:100',
            'or_number' => 'nullable|string|max:100',
            'or_date' => 'nullable|date',
            'disbursed_count' => 'nullable|integer|min:0',
            'scholar_file' => 'nullable|file|mimes:csv,txt,pdf|max:5120',
            'pdf_attachment' => 'nullable|file|mimes:pdf|max:10240',
            'disbursement_scholar_file' => 'nullable|file|mimes:csv,txt,pdf|max:5120',
            'disbursement_attachment' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('scholar_file')) {
            $filePath = $request->file('scholar_file')->store('scholar_files', 'public');
        }

        $pdfAttachmentPath = null;
        if ($request->hasFile('pdf_attachment')) {
            $pdfAttachmentPath = $request->file('pdf_attachment')->store('attachments', 'public');
        }

        $disbursementAttachmentPath = null;
        if ($request->hasFile('disbursement_attachment')) {
            $disbursementAttachmentPath = $request->file('disbursement_attachment')->store('attachments', 'public');
        }

        $disbursementScholarPath = null;
        if ($request->hasFile('disbursement_scholar_file')) {
            $disbursementScholarPath = $request->file('disbursement_scholar_file')->store('scholar_files', 'public');
        }

        DB::beginTransaction();
        try {
            $batchId = DB::table('billing_batches')->insertGetId([
                'program' => $validated['program'],
                'semester' => $validated['semester'],
                'batch' => $validated['batch'],
                'ay' => $validated['ay'],
                'region' => $validated['region'],
                'billing_date' => $validated['billing_date'],
                'amount' => $validated['amount'] ?: 0,
                'ada_date' => $validated['ada_date'],
                'ada_no' => $validated['ada_no'],
                'or_number' => $validated['or_number'],
                'or_date' => $validated['or_date'],
                'disbursed_count' => $validated['disbursed_count'] ?: 0,
                'scholar_file' => $filePath,
                'pdf_attachment' => $pdfAttachmentPath,
                'disbursement_scholar_file' => $disbursementScholarPath,
                'disbursement_attachment' => $disbursementAttachmentPath,
                'scholar_count' => 0, // Will be updated if file uploaded
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $scholarCount = 0;
            $rows = [];
            $extension = null;
            $dExtension = null;

            if ($filePath) {
                $fullPath = storage_path('app/public/' . $filePath);
                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                if ($extension === 'pdf') {
                    Log::info("Skipping PDF extraction for scholar list in batch $batchId.");
                } else {
                    $handle = fopen($fullPath, "r");
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $rows[] = $data;
                    }
                    fclose($handle);
                }
            }

            if ($disbursementScholarPath) {
                $dFullPath = storage_path('app/public/' . $disbursementScholarPath);
                $dExtension = strtolower(pathinfo($dFullPath, PATHINFO_EXTENSION));

                if ($dExtension === 'pdf') {
                    Log::info("Skipping PDF extraction for disbursement scholar list in batch $batchId.");
                } else {
                    $dHandle = fopen($dFullPath, "r");
                    while (($dData = fgetcsv($dHandle, 1000, ",")) !== FALSE) {
                        $rows[] = $dData;
                    }
                    fclose($dHandle);
                }
            }

            if (!empty($rows)) {

                $blockedKeywords = ['semester', 'academic', 'year', 'program', 'batch', 'region', 'total', 'amount', 'scholar', 'billing', 'date', 'CHED', 'TDP', 'TES', 'ACEF', 'GIAHEP', 'CMSP', '1st', '2nd', 'student_id', 'academic_year', 'disbursed_amount', 'no.'];
                $processedIdsInSession = [];
                $rejections = [];

                foreach ($rows as $row) {
                    $rowString = strtolower(implode(' ', $row));
                    $isHeaderRow = false;
                    foreach (['student_id', 'academic_year', 'disbursed_amount', 'no.', 'region', 'program'] as $hk) {
                        if (strpos($rowString, $hk) !== false) { $isHeaderRow = true; break; }
                    }
                    if ($isHeaderRow) continue;

                    $detectedName = '';
                    $detectedId = '';
                    $detectedFbLink = '';
                    foreach ($row as $k => $v) {
                        if (preg_match('/^(20|19)\d{7}$/', $v)) $detectedId = $v;
                        if (preg_match('/(facebook\.com|http|@)/i', $v)) $detectedFbLink = $v;
                    }
                    $row['id'] = $detectedId;
                    $row['fb_link'] = $detectedFbLink;

                    foreach ($row as $cell) {
                        $cell = trim($cell);
                        if (empty($cell)) continue;

                        if (preg_match('/((20|19)\d{7})/', $cell, $idMatches)) {
                            $detectedId = $idMatches[1];
                            if ($extension === 'pdf' && empty($detectedName)) {
                                $parts = explode($idMatches[0], $cell);
                                if (isset($parts[0]) && strlen(trim($parts[0])) > 3) {
                                    $pName = trim($parts[0]);
                                    $isBad = false;
                                    foreach ($blockedKeywords as $kw) { if (stripos($pName, $kw) !== false) { $isBad = true; break; } }
                                    if (!$isBad) $detectedName = $pName;
                                }
                            }
                            continue;
                        }

                        if (preg_match('/(facebook\.com|http|@)/i', $cell)) {
                            $detectedFbLink = $cell;
                            continue;
                        }

                        if (!is_numeric($cell) && strlen($cell) > 5 && empty($detectedName)) {
                            $isK = false;
                            foreach ($blockedKeywords as $kw) { if (stripos($cell, $kw) !== false) { $isK = true; break; } }
                            if (stripos($cell, 'http') !== false || stripos($cell, '.com') !== false || stripos($cell, 'facebook') !== false) $isK = true;
                            if (!$isK) $detectedName = $cell;
                        }
                    }

                    if (empty($detectedId)) continue;

                    // SESSION DEDUPLICATION
                    if (in_array($detectedId, $processedIdsInSession)) {
                        $rejections[] = ['id' => $detectedId, 'name' => $detectedName ?: 'Duplicate', 'reason' => 'Duplicate in file'];
                        continue;
                    }
                    $processedIdsInSession[] = $detectedId;

                    // GLOBAL HARD GATE: Double-Funding Prevention
                    $conflict = DB::table('billing_scholars')
                        ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
                        ->where(DB::raw('REPLACE(REPLACE(billing_scholars.student_id_no, "-", ""), " ", "")'), $detectedId)
                        ->where('billing_batches.ay', $validated['ay'])
                        ->where('billing_batches.semester', $validated['semester'])
                        ->select('billing_batches.program')
                        ->first();
                    
                    if ($conflict) {
                        $rejections[] = ['id' => $detectedId, 'name' => $detectedName ?: $detectedId, 'reason' => "Already funded in {$conflict->program}"];
                        continue;
                    }

                    $student = DB::table('students')
                        ->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $detectedId)
                        ->first();

                        if ($student) {
                            // PROGRAM INTEGRITY GATE: Lock student to their program
                            if ($student->scholarship_program === 'N/A') {
                                $rejections[] = ['id' => $detectedId, 'name' => "{$student->last_name}, {$student->given_name}", 'reason' => "No Program Assigned (Master List)"];
                                continue;
                            }
                            
                            if ($student->scholarship_program !== $validated['program']) {
                                $rejections[] = ['id' => $detectedId, 'name' => "{$student->last_name}, {$student->given_name}", 'reason' => "Locked to {$student->scholarship_program}"];
                                continue;
                            }

                            // UPDATE MASTER INFO: If FB link was found in row and student doesn't have one yet
                            if ($student->fb_link === 'N/A' && !empty($detectedFbLink)) {
                                DB::table('students')->where('id', $student->id)->update(['fb_link' => $detectedFbLink]);
                            }

                            $detectedName = "{$student->last_name}, {$student->given_name}";
                        
                        // YEAR LEVEL SYNC: Automatically update student's year level if found in row
                        foreach ($row as $cell) {
                            $cell = trim($cell);
                            if (preg_match('/(1st|2nd|3rd|4th|5th)\s*Year/i', $cell, $yrM)) {
                                $finalYear = strtoupper($yrM[0]);
                                if ($student->year_level !== $finalYear) {
                                    DB::table('students')->where('id', $student->id)->update(['year_level' => $finalYear]);
                                    $student->year_level = $finalYear; // Update local object
                                }
                                break;
                            }
                        }
                    } else {
                        $nameParts = explode(',', $detectedName);
                        $lastName = trim($nameParts[0]);
                        $givenName = isset($nameParts[1]) ? trim($nameParts[1]) : ($detectedName ?: "Scholar ID: $detectedId");
                        
                        $newStudentId = DB::table('students')->insertGetId([
                            'student_id_no' => $detectedId,
                            'last_name' => $lastName,
                            'given_name' => $givenName,
                            'middle_initial' => '',
                            'degree_program' => $validated['program'],
                            'scholarship_program' => $validated['program'],
                            'year_level' => 'N/A',
                            'tdp_tes_award_no' => 'N/A',
                            'email' => 'N/A',
                            'contact_no' => 'N/A',
                            'fb_link' => 'N/A',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $student = DB::table('students')->where('id', $newStudentId)->first();
                    }

                    DB::table('billing_scholars')->insert([
                        'billing_batch_id' => $batchId,
                        'student_id' => $student->id,
                        'student_name' => $detectedName,
                        'student_id_no' => $detectedId,
                        'award_no' => $student->tdp_tes_award_no,
                        'year_level' => $student->year_level,
                        'created_at' => now(),
                    ]);
                    $scholarCount++;
                }

                DB::table('billing_batches')->where('id', $batchId)->update(['scholar_count' => $scholarCount]);
            }

                // ROBOT INTELLIGENCE: Log and notify about rejections
                if (!empty($rejections)) {
                    $rejectionSummary = collect($rejections)->map(fn($r) => "{$r['id']} ({$r['reason']})")->implode(', ');
                    DB::table('activity_logs')->insert([
                        'user_id' => auth()->id(),
                        'module' => 'Billing',
                        'action' => 'Robot Intelligence: Bulk Import Rejections',
                        'description' => "Blocked " . count($rejections) . " scholars during import for batch $batchId. Reasons: $rejectionSummary",
                        'staff_name' => auth()->user()->name,
                        'staff_email' => auth()->user()->email,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    session()->flash('rejections', $rejections);
                }

            DB::commit();

            $this->logActivity('Billing', 'Created batch', "Created batch for {$validated['program']} AY {$validated['ay']} with $scholarCount scholars.");

            return redirect()->route('billing.index')->with('success', 'Billing batch created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing create error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'An error occurred while saving the billing batch.');
        }
    }

    public function show($id)
    {
        $batch = DB::table('billing_batches')
            ->leftJoin('users', 'billing_batches.created_by', '=', 'users.id')
            ->select('billing_batches.*', 'users.name as creator_name')
            ->where('billing_batches.id', $id)
            ->first();

        if (!$batch) abort(404);

        $scholars = DB::table('billing_scholars')
            ->leftJoin('students', 'billing_scholars.student_id', '=', 'students.id')
            ->where('billing_scholars.billing_batch_id', $id)
            ->select(
                'billing_scholars.*',
                'students.tdp_tes_award_no',
                'students.degree_program',
                'students.year_level',
                'students.email',
                'students.contact_no',
                'students.fb_link'
            )
            ->get();

        return view('billing.show', compact('batch', 'scholars'));
    }

    public function importScholars(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,pdf|max:5120'
        ]);

        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) return back()->with('error', 'Batch not found.');

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $rows = [];

        if ($extension === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = $pdf->getText();
                $lines = explode("\n", $text);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    // For PDF, we simulate a CSV row by splitting on spaces or tabs
                    // But our scanner handles any cell in the row, so we just pass the whole line as one cell first
                    $rows[] = [$line];
                }
            } catch (\Exception $e) {
                return back()->with('error', 'PDF parsing failed: ' . $e->getMessage());
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            while (($row = fgetcsv($handle)) !== FALSE) {
                $rows[] = $row;
            }
            fclose($handle);
        }
        
        $report = [
            'success' => 0,
            'duplicate' => 0,
            'conflict' => 0,
            'invalid' => 0,
            'total_rows' => 0,
            'success_list' => [],
            'duplicate_list' => [],
            'conflict_list' => [],
            'invalid_list' => []
        ];

        $blockedKeywords = [
            'semester', 'academic', 'year', 'program', 'batch', 'region', 
            'total', 'amount', 'scholar', 'billing', 'date', 'CHED', 
            'TDP', 'TES', 'ACEF', 'GIAHEP', 'CMSP', '1st', '2nd',
            'student_id', 'academic_year', 'disbursed_amount', 'no.'
        ];

        DB::beginTransaction();
        $processedIdsInSession = [];
        try {
            foreach ($rows as $row) {
                $rowString = strtolower(implode(' ', $row));
                // Header Guard: If the row contains common header keywords, skip the whole row
                if (strpos($rowString, 'student_id') !== false || 
                    strpos($rowString, 'academic_year') !== false || 
                    strpos($rowString, 'disbursed_amount') !== false) {
                    continue;
                }

                $report['total_rows']++;
                $detectedName = '';
                $detectedId = '';

                foreach ($row as $cell) {
                    $cell = trim($cell);
                    if (empty($cell)) continue;

                    // ADVANCED PATTERN-SEEKER: Find ID pattern anywhere in the cell (Strict 9-digit match)
                    if (preg_match('/((20|19)\d{7})/', $cell, $idMatches)) {
                        $detectedId = $idMatches[1];
                        
                        // If it's a "glued" PDF line, try to grab the name from before the ID
                        if ($extension === 'pdf' && empty($detectedName)) {
                            $parts = explode($idMatches[0], $cell);
                            if (isset($parts[0]) && strlen(trim($parts[0])) > 3) {
                                $potentialName = trim($parts[0]);
                                // Double check it's not just a header
                                $isHeader = false;
                                foreach ($blockedKeywords as $kw) {
                                    if (stripos($potentialName, $kw) !== false) {
                                        $isHeader = true; break;
                                    }
                                }
                                if (!$isHeader) $detectedName = $potentialName;
                            }
                        }
                    }
                    if (preg_match('/(facebook\.com|http|@)/i', $cell)) {
                        $detectedFbLink = $cell;
                    } elseif (!is_numeric($cell) && strlen($cell) > 5) {
                        $isKeyword = false;
                        foreach ($blockedKeywords as $kw) {
                            if (stripos($cell, $kw) !== false) {
                                $isKeyword = true;
                                break;
                            }
                        }
                        if (stripos($cell, 'http') !== false || stripos($cell, '.com') !== false || stripos($cell, 'facebook') !== false) $isKeyword = true;
                        if (!$isKeyword) $detectedName = $cell;
                    }
                }

            // Header Guard: If the whole row looks like a list of headers, skip it
                if (empty($detectedId) && stripos(implode(' ', $row), 'student_id') !== false) {
                    continue;
                }

                if (empty($detectedId)) {
                    $report['invalid']++;
                    if (count($report['invalid_list']) < 50) {
                        $report['invalid_list'][] = ['name' => $detectedName ?: 'Unknown Row', 'id' => 'N/A'];
                    }
                    continue;
                }

                // SESSION DEDUPLICATION: Prevent adding the same ID twice in one upload
                if (in_array($detectedId, $processedIdsInSession)) {
                    $report['duplicate']++;
                    if (count($report['duplicate_list']) < 50) {
                        $report['duplicate_list'][] = ['name' => $detectedName ?: $detectedId, 'id' => $detectedId];
                    }
                    continue;
                }
                $processedIdsInSession[] = $detectedId;

                // ID-FIRST LOOKUP: If name is missing, look it up in master list (Aggressive Matching)
                $detectedIdSanitized = $detectedId;
                $studentLookup = DB::table('students')
                    ->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $detectedIdSanitized)
                    ->first();

                if ($studentLookup) {
                    $detectedName = "{$studentLookup->last_name}, {$studentLookup->given_name}";
                }

                $detectedId = $detectedIdSanitized;

                // INTERNAL DUPLICATE CHECK: Is this student already in THIS specific batch?
                $existsInCurrentBatch = DB::table('billing_scholars')
                    ->where('billing_batch_id', $batch->id)
                    ->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $detectedId)
                    ->exists();
                
                if ($existsInCurrentBatch) {
                    $report['duplicate']++;
                    if (count($report['duplicate_list']) < 50) {
                        $report['duplicate_list'][] = ['name' => $detectedName ?: $detectedId, 'id' => $detectedId];
                    }
                    continue;
                }

                // GLOBAL HARD GATE: Is this Student ID already in ANY batch for the same AY/Semester?
                $conflict = DB::table('billing_scholars')
                    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
                    ->where(DB::raw('REPLACE(REPLACE(billing_scholars.student_id_no, "-", ""), " ", "")'), $detectedId)
                    ->where('billing_batches.ay', $batch->ay)
                    ->where('billing_batches.semester', $batch->semester)
                    ->where('billing_batches.id', '!=', $batch->id)
                    ->select('billing_batches.program')
                    ->first();

                if ($conflict) {
                    $report['conflict']++;
                    if (count($report['conflict_list']) < 50) {
                        $report['conflict_list'][] = [
                            'name' => $detectedName ?: $detectedId, 
                            'id' => $detectedId, 
                            'reason' => "Funded in {$conflict->program}"
                        ];
                    }
                    continue;
                }

                $student = $studentLookup;
                
                if ($student) {
                    // PROGRAM INTEGRITY GATE: If student is not yet assigned to a program
                    if ($student->scholarship_program === 'N/A') {
                        $report['conflict']++;
                        if (count($report['conflict_list']) < 50) {
                            $report['conflict_list'][] = [
                                'name' => $detectedName ?: $detectedId, 
                                'id' => $detectedId, 
                                'reason' => "No Program Assigned (Master List)"
                            ];
                        }
                        continue;
                    }

                    // PROGRAM INTEGRITY GATE: If student is already locked to a DIFFERENT program
                    if ($student->scholarship_program !== $batch->program) {
                        $report['conflict']++;
                        if (count($report['conflict_list']) < 50) {
                            $report['conflict_list'][] = [
                                'name' => $detectedName ?: $detectedId, 
                                'id' => $detectedId, 
                                'reason' => "Locked to {$student->scholarship_program}"
                            ];
                        }
                        continue;
                    }
                }

                // GLOBAL HARD GATE: Check if already PAID in another batch for the same period
                $paidConflict = DB::table('billing_scholars')
                    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
                    ->where(DB::raw('REPLACE(REPLACE(billing_scholars.student_id_no, "-", ""), " ", "")'), $detectedId)
                    ->where('billing_batches.ay', $batch->ay)
                    ->where('billing_batches.semester', $batch->semester)
                    ->where('billing_batches.id', '!=', $batch->id)
                    ->whereNotNull('billing_batches.ada_no')
                    ->where('billing_batches.ada_no', '!=', '')
                    ->select('billing_batches.program', 'billing_batches.ada_no')
                    ->first();

                if ($paidConflict) {
                    $report['conflict']++;
                    if (count($report['conflict_list']) < 50) {
                        $report['conflict_list'][] = [
                            'name' => $detectedName ?: $detectedId, 
                            'id' => $detectedId, 
                            'reason' => "Already PAID in {$paidConflict->program} (ADA: {$paidConflict->ada_no})"
                        ];
                    }
                    continue;
                }

                if (!$student) {
                    $nameParts = explode(',', $detectedName);
                    $lastName = trim($nameParts[0]);
                    $givenName = isset($nameParts[1]) ? trim($nameParts[1]) : ($detectedName ?: 'Unknown');

                    $newStudentId = DB::table('students')->insertGetId([
                        'student_id_no' => $detectedId,
                        'last_name' => $lastName,
                        'given_name' => $givenName,
                        'middle_initial' => '',
                        'degree_program' => $batch->program,
                        'scholarship_program' => $batch->program,
                        'year_level' => 'N/A',
                        'tdp_tes_award_no' => 'N/A',
                        'email' => 'N/A',
                        'contact_no' => 'N/A',
                        'fb_link' => 'N/A',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $student = DB::table('students')->where('id', $newStudentId)->first();
                } else {
                    if ($student) {
                        $upd = [];
                        if ($student->fb_link === 'N/A' && !empty($detectedFbLink)) $upd['fb_link'] = $detectedFbLink;
                        if ($student->scholarship_program === 'N/A') $upd['scholarship_program'] = $batch->program;
                        
                        if (!empty($upd)) {
                            DB::table('students')->where('id', $student->id)->update($upd);
                        }
                    }

                    // YEAR LEVEL SYNC: Automatically update student's year level if found in row
                    foreach ($row as $cell) {
                        $cell = trim($cell);
                        if (preg_match('/(1st|2nd|3rd|4th|5th)\s*Year/i', $cell, $yrM)) {
                            $finalYear = strtoupper($yrM[0]);
                            if ($student->year_level !== $finalYear) {
                                DB::table('students')->where('id', $student->id)->update(['year_level' => $finalYear]);
                                $student->year_level = $finalYear; // Update local object
                            }
                            break;
                        }
                    }
                }

                DB::table('billing_scholars')->insert([
                    'billing_batch_id' => $batch->id,
                    'student_id' => $student->id,
                    'student_name' => $detectedName ?: "{$student->last_name}, {$student->given_name}",
                    'student_id_no' => $detectedId,
                    'award_no' => $student->tdp_tes_award_no,
                    'year_level' => $student->year_level,
                    'created_at' => now(),
                ]);
                
                $report['success']++;
                if (count($report['success_list']) < 50) {
                    $report['success_list'][] = ['name' => $detectedName ?: $detectedId, 'id' => $detectedId];
                }
            }

            DB::table('billing_batches')->where('id', $batch->id)->increment('scholar_count', $report['success']);
            DB::commit();
            if (isset($handle) && is_resource($handle)) fclose($handle);

            // PERSISTENT HISTORY: Save the audit report to the database
            $summaryId = DB::table('import_summaries')->insertGetId([
                'filename' => $file->getClientOriginalName(),
                'program' => $batch->program,
                'ay' => $batch->ay,
                'semester' => $batch->semester,
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
            return back()->with('import_report', $report)->with('success', "Batch import complete. Check the Intelligence Timeline for details.");

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) fclose($handle);
            Log::error('Batch Scholar Import Error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during the batch upload.');
        }
    }

    public function resolveConflict(Request $request, $batchId)
    {
        $batch = DB::table('billing_batches')->where('id', $batchId)->first();
        if (!$batch) return response()->json(['error' => 'Batch not found'], 404);

        $action = $request->input('action'); // 'switch' or 'ignore'
        $studentIdNo = $request->input('student_id_no');
        $studentName = $request->input('student_name');
        $oldProgram = $request->input('old_program');
        $newProgram = $batch->program;

        if ($action === 'switch') {
            $student = DB::table('students')->where('student_id_no', $studentIdNo)->first();
            if (!$student) return response()->json(['error' => 'Student not found'], 404);

            // Update student master profile
            DB::table('students')->where('id', $student->id)->update([
                'scholarship_program' => $newProgram,
                'updated_at' => now()
            ]);

            // Add to current batch
            DB::table('billing_scholars')->insert([
                'billing_batch_id' => $batch->id,
                'student_id' => $student->id,
                'student_name' => $studentName,
                'student_id_no' => $studentIdNo,
                'award_no' => $student->tdp_tes_award_no,
                'year_level' => $student->year_level,
                'created_at' => now(),
            ]);

            DB::table('billing_batches')->where('id', $batch->id)->increment('scholar_count');

            $this->logActivity('Billing', 'Conflict Resolved (Switch)', "Manually moved $studentName ($studentIdNo) from $oldProgram to $newProgram");
            return response()->json(['success' => true, 'message' => "$studentName successfully moved to $newProgram."]);
        } else {
            $this->logActivity('Billing', 'Conflict Resolved (Ignore)', "Kept $studentName ($studentIdNo) in $oldProgram (Skipped import)");
            return response()->json(['success' => true, 'message' => "$studentName skipped."]);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_billing_template.csv"',
        ];

        $columns = [
            'Program', 'Semester', 'AY', 'Batch', 'Region', 'Billing Date', 'Amount', 'Scholar Name', 'Scholar ID'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadQuickTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="quick_scholar_template.csv"',
        ];

        $columns = [
            'Scholar Name', 'Scholar ID'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Add an example row
            fputcsv($file, ['John Doe', '2024-0001']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,pdf|max:10240',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        
        $batchCache = [];
        $importedCount = 0;
        $batchCount = 0;
        $rows = [];
        $processedIdsInSession = []; // SESSION DEDUPLICATION
        
        $blockedKeywords = [
            'semester', 'academic', 'year', 'program', 'batch', 'region', 
            'total', 'amount', 'scholar', 'billing', 'date', 'CHED', 
            'TDP', 'TES', 'ACEF', 'GIAHEP', 'CMSP', '1st', '2nd',
            'student_id', 'academic_year', 'disbursed_amount', 'no.'
        ];

        if ($extension === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
            $lines = explode("\n", $text);
            
            $currentMetadata = [
                'program' => 'TDP-TES',
                'semester' => '1st Semester',
                'ay' => date('Y') . '-' . (date('Y') + 1),
                'batch' => 'Imported Batch',
                'region' => 'Region VII',
                'billing_date' => now()->toDateString()
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/(1st|2nd|First|Second)\s+Semester/i', $line, $m)) $currentMetadata['semester'] = $m[0];
                if (preg_match('/\d{4}-\d{4}/', $line, $m)) $currentMetadata['ay'] = $m[0];
                if (stripos($line, 'CHED') !== false) $currentMetadata['program'] = 'CHED';
                if (stripos($line, 'TES') !== false || stripos($line, 'TDP') !== false) $currentMetadata['program'] = 'TDP-TES';
                if (preg_match('/Batch\s+\d+(\.\d+)?/i', $line, $m)) $currentMetadata['batch'] = $m[0];

                if (preg_match('/([A-Z][a-z]+(?: [A-Z][a-z]+)*,\s+[A-Z][a-z]+(?: [A-Z][a-z]+)*)\s+(\d{4}-\d{5}|\d{9})/', $line, $matches)) {
                    $rows[] = array_merge($currentMetadata, [
                        'name' => $matches[1],
                        'id' => $matches[2]
                    ]);
                }
            }
        } else {
            $handle = fopen($file->getPathname(), "r");
            $header = fgetcsv($handle, 1000, ",");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $item = [
                    'program' => 'TDP-TES',
                    'semester' => '1st Semester',
                    'ay' => date('Y') . '-' . (date('Y') + 1),
                    'batch' => 'Imported Batch',
                    'region' => 'Region VII',
                    'billing_date' => now()->toDateString(),
                    'amount' => 0,
                    'name' => '',
                    'id' => ''
                ];

                foreach ($data as $val) {
                    $val = trim($val);
                    if (empty($val)) continue;

                    if (preg_match('/(1st|2nd|First|Second)\s+Semester/i', $val)) {
                        $item['semester'] = $val;
                    } elseif (preg_match('/(\d{4})-\d{4}/', $val, $ayMatches)) {
                        $startYear = intval($ayMatches[1]);
                        $currentYear = intval(date('Y'));
                        // REAL-TIME LOCK: Only allow current or past academic years. 
                        // If it's 2026, we only allow batches starting in 2025 or earlier.
                        if ($startYear < $currentYear) {
                            $item['ay'] = $val;
                        }
                    } elseif (preg_match('/^(CHED|TES|TDP|TDP-TES|ACEF-GIAHEP|CMSP)$/i', $val)) {
                        $normalized = strtoupper($val);
                        if (stripos($normalized, 'TES') !== false || stripos($normalized, 'TDP') !== false) {
                            $item['program'] = 'TDP-TES';
                        } else {
                            $item['program'] = $normalized;
                        }
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) || (strtotime($val) && strlen($val) > 8 && !preg_match('/[a-zA-Z]{5,}/', $val))) {
                        try {
                            $item['billing_date'] = \Carbon\Carbon::parse($val)->toDateString();
                        } catch (\Exception $e) {}
                    } elseif (preg_match('/^(20|19)\d{7}$/', $val, $idM)) {
                        $item['id'] = $idM[0]; // SANITIZE ID
                    } elseif (stripos($val, 'Batch') !== false) {
                        $item['batch'] = $val;
                    } elseif (stripos($val, 'Region') !== false) {
                        $item['region'] = $val;
                    } elseif (is_numeric($val) && $val > 100 && $val < 50000000 && !preg_match('/^(20|19)\d{7}$/', $val)) {
                        $item['amount'] = floatval($val);
                    } elseif (strpos($val, ',') !== false && strlen($val) > 5) {
                        // POTENTIAL NAME: Only set if it doesn't match metadata patterns or look like a URL
                        if (!preg_match('/\d{4}-\d{4}/', $val) && !preg_match('/(1st|2nd)\s+Semester/i', $val)) {
                            if (stripos($val, 'http') === false && stripos($val, '.com') === false && stripos($val, 'facebook') === false) {
                                $item['name'] = $val;
                            }
                        }
                    }
                }

                // ID-First Logic: Try to fetch name if ID exists
                if ($item['id']) {
                    $student = DB::table('students')
                        ->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $item['id'])
                        ->first();
                    if ($student) {
                        $item['name'] = "{$student->last_name}, {$student->given_name}";
                    }
                }

                if (!empty($item['name']) || !empty($item['id'])) {
                    $rows[] = $item;
                }
            }
            fclose($handle);
        }

        if (empty($rows)) return back()->with('error', 'No students detected. Please check your file content.');

        $report = [
            'success' => 0,
            'duplicate' => 0,
            'conflict' => 0,
            'invalid' => 0,
            'batches' => $batchCount,
            'total_rows' => count($rows),
            'success_list' => [],
            'duplicate_list' => [],
            'conflict_list' => [],
            'invalid_list' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (empty($row['id']) || empty($row['name'])) {
                    $report['invalid']++;
                    if (count($report['invalid_list']) < 50) {
                        $report['invalid_list'][] = ['name' => $row['name'] ?: 'Unknown', 'id' => $row['id'] ?: 'N/A'];
                    }
                    continue;
                }

                $batchKey = "{$row['program']}-{$row['semester']}-{$row['ay']}-{$row['batch']}-{$row['region']}";

                if (!isset($batchCache[$batchKey])) {
                    // Check if batch already exists in database
                    $existingBatch = DB::table('billing_batches')
                        ->where('program', $row['program'])
                        ->where('semester', $row['semester'])
                        ->where('ay', $row['ay'])
                        ->where('batch', $row['batch'])
                        ->where('region', $row['region'])
                        ->first();

                    if ($existingBatch) {
                        $batchId = $existingBatch->id;
                    } else {
                        $batchId = DB::table('billing_batches')->insertGetId([
                            'program' => $row['program'],
                            'semester' => $row['semester'],
                            'ay' => $row['ay'],
                            'batch' => $row['batch'],
                            'region' => $row['region'],
                            'billing_date' => $row['billing_date'],
                            'amount' => $row['amount'],
                            'scholar_count' => 0,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $batchCount++;
                        $report['batches']++;
                    }
                    $batchCache[$batchKey] = ['id' => $batchId, 'count' => 0, 'is_new' => !$existingBatch];
                }

                $batchId = $batchCache[$batchKey]['id'];

                if ($row['id']) {
                    $detectedId = str_replace(['-', ' '], '', $row['id']);
                    
                    // SESSION DEDUPLICATION
                    if (in_array($detectedId, $processedIdsInSession)) {
                        $report['duplicate']++;
                        if (count($report['duplicate_list']) < 50) {
                            $report['duplicate_list'][] = ['name' => $row['name'], 'id' => $detectedId];
                        }
                        continue;
                    }
                    $processedIdsInSession[] = $detectedId;

                    $student = DB::table('students')->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $detectedId)->first();
                    
                    // PRIORITIZE MASTER LIST NAME: If student is in DB, always use their real name
                    if ($student) {
                        $row['name'] = "{$student->last_name}, {$student->given_name}";
                    }
                    
                    // FALLBACK: If name is still empty or looks like metadata, use a placeholder
                    if (empty($row['name']) || preg_match('/\d{4}-\d{4}/', $row['name']) || preg_match('/(1st|2nd)\s+Semester/i', $row['name'])) {
                        $row['name'] = $row['name'] ?: 'Unknown Scholar';
                    }

                    // INTERNAL DUPLICATE CHECK: Is student already in THIS specific batch?
                    $existsInCurrentBatch = DB::table('billing_scholars')
                        ->where('billing_batch_id', $batchId)
                        ->where(DB::raw('REPLACE(REPLACE(student_id_no, "-", ""), " ", "")'), $detectedId)
                        ->exists();

                    if ($existsInCurrentBatch) {
                        $report['duplicate']++;
                        if (count($report['duplicate_list']) < 50) {
                            $report['duplicate_list'][] = ['name' => $row['name'], 'id' => $detectedId];
                        }
                        continue;
                    }

                    // GLOBAL HARD GATE: Check if already PAID in another batch for the same period
                    $paidConflict = DB::table('billing_scholars')
                        ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
                        ->where(DB::raw('REPLACE(REPLACE(billing_scholars.student_id_no, "-", ""), " ", "")'), $detectedId)
                        ->where('billing_batches.ay', $row['ay'])
                        ->where('billing_batches.semester', $row['semester'])
                        ->where('billing_batches.id', '!=', $batchId)
                        ->whereNotNull('billing_batches.ada_no')
                        ->where('billing_batches.ada_no', '!=', '')
                        ->select('billing_batches.program', 'billing_batches.ada_no')
                        ->first();

                    if ($paidConflict) {
                        $report['conflict']++;
                        if (count($report['conflict_list']) < 50) {
                            $report['conflict_list'][] = [
                                'name' => $row['name'], 
                                'id' => $detectedId, 
                                'reason' => "Already PAID in {$paidConflict->program} (ADA: {$paidConflict->ada_no})"
                            ];
                        }
                        continue;
                    }

                    if ($student) {
                        // PROGRAM INTEGRITY GATE: If student is not yet assigned to a program
                        if ($student->scholarship_program === 'N/A') {
                            $report['conflict']++;
                            if (count($report['conflict_list']) < 50) {
                                $report['conflict_list'][] = [
                                    'name' => "{$student->last_name}, {$student->given_name}", 
                                    'id' => $detectedId, 
                                    'reason' => "No Program Assigned (Master List)"
                                ];
                            }
                            continue;
                        }

                        // PROGRAM INTEGRITY GATE: Lock student to their program
                        if ($student->scholarship_program !== $row['program']) {
                            $report['conflict']++;
                            if (count($report['conflict_list']) < 50) {
                                $report['conflict_list'][] = [
                                    'name' => "{$student->last_name}, {$student->given_name}", 
                                    'id' => $detectedId, 
                                    'reason' => "Locked to {$student->scholarship_program}"
                                ];
                            }
                            continue;
                        }
                    }

                    // YEAR LEVEL SYNC: Automatically update student's year level if found in row
                    foreach ($row as $cell) {
                        $cell = trim($cell);
                        if (preg_match('/(1st|2nd|3rd|4th|5th)\s*Year/i', $cell, $yrM)) {
                            $finalYear = strtoupper($yrM[0]);
                            if ($student->year_level !== $finalYear) {
                                DB::table('students')->where('id', $student->id)->update(['year_level' => $finalYear]);
                                $student->year_level = $finalYear; // Update local object
                            }
                            break;
                        }
                    }
                }

                $studentId = $student ? $student->id : null;

                DB::table('billing_scholars')->insert([
                    'billing_batch_id' => $batchId,
                    'student_id' => $studentId,
                    'student_name' => $row['name'] ?: 'Unknown Student',
                    'student_id_no' => $row['id'],
                    'award_no' => $student ? $student->tdp_tes_award_no : 'N/A',
                    'year_level' => $student ? $student->year_level : 'N/A',
                    'created_at' => now(),
                ]);

                // UPDATE MASTER INFO: If FB link was found in row and student doesn't have one yet
                if ($student && $student->fb_link === 'N/A' && !empty($row['fb_link'])) {
                    DB::table('students')->where('id', $student->id)->update(['fb_link' => $row['fb_link']]);
                    $student->fb_link = $row['fb_link'];
                }

                $batchCache[$batchKey]['count']++;
                $report['success']++;
                if (count($report['success_list']) < 50) {
                    $report['success_list'][] = [
                        'name' => $row['name'], 
                        'id' => $row['id']
                    ];
                }
            }

            foreach ($batchCache as $info) {
                if ($info['is_new']) {
                    DB::table('billing_batches')->where('id', $info['id'])->update(['scholar_count' => $info['count']]);
                } else {
                    DB::table('billing_batches')->where('id', $info['id'])->increment('scholar_count', $info['count']);
                }
            }

            DB::commit();

            // PERSISTENT HISTORY: Save the audit report to the database
            $summaryId = DB::table('import_summaries')->insertGetId([
                'filename' => $request->file('file')->getClientOriginalName(),
                'program' => $rows[0]['program'] ?? 'Multiple',
                'ay' => $rows[0]['ay'] ?? 'N/A',
                'semester' => $rows[0]['semester'] ?? 'N/A',
                'success_count' => $report['success'],
                'duplicate_count' => $report['duplicate'],
                'conflict_count' => $report['conflict'],
                'invalid_count' => $report['invalid'],
                'report_data' => json_encode($report),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $report['id'] = $summaryId; // Attach the ID for the UI
            return back()->with('import_report', $report)->with('success', "Import process completed and logged to timeline.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk import error: ' . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) abort(404);

        return view('billing.edit', compact('batch'));
    }

    public function update(Request $request, $id)
    {
        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) abort(404);

        // HARD GATE: Prevent disbursement if scholars have incomplete profiles
        if ($request->filled('disbursed_count') && $request->disbursed_count > 0) {
            $incompleteScholars = DB::table('billing_scholars')
                ->join('students', 'billing_scholars.student_id', '=', 'students.id')
                ->where('billing_scholars.billing_batch_id', $id)
                ->where(function($q) {
                    $q->where('students.email', 'N/A')
                      ->orWhere('students.contact_no', 'N/A')
                      ->orWhere('students.fb_link', 'N/A')
                      ->orWhere('students.year_level', 'N/A')
                      ->orWhereNull('students.email')
                      ->orWhereNull('students.contact_no')
                      ->orWhereNull('students.fb_link')
                      ->orWhereNull('students.year_level');
                })
                ->select('billing_scholars.student_name', 'billing_scholars.student_id_no')
                ->get();

            if ($incompleteScholars->isNotEmpty()) {
                $names = $incompleteScholars->map(function($s) {
                    return "• " . $s->student_name . " (" . $s->student_id_no . ")";
                })->implode('<br>');
                
                return back()->withInput()->with('error', "<strong>Disbursement Blocked!</strong><br>The following students have incomplete profiles. Please update their information in the Master List before disbursing this batch:<br><br>" . $names);
            }
        }

        $validated = $request->validate([
            'program' => 'required|string|max:100',
            'semester' => 'required|string|max:50',
            'batch' => 'nullable|string|max:50',
            'ay' => 'required|string|max:20',
            'region' => 'nullable|string|max:100',
            'billing_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'ada_date' => 'nullable|date',
            'ada_no' => 'nullable|string|max:100',
            'or_number' => 'nullable|string|max:100',
            'or_date' => 'nullable|date',
            'disbursed_count' => 'nullable|integer|min:0',
            'scholar_file' => 'nullable|file|mimes:csv,txt,pdf|max:5120',
            'disbursement_scholar_file' => 'nullable|file|mimes:csv,txt,pdf|max:5120',
            'pdf_attachment' => 'nullable|file|mimes:pdf|max:10240',
            'disbursement_attachment' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Simplicity: Only update fields that were actually in the request
        $updateData = $request->only([
            'program', 'semester', 'batch', 'ay', 'region', 'billing_date', 'amount', 
            'ada_date', 'ada_no', 'or_number', 'or_date', 'disbursed_count'
        ]);
        
        $updateData['updated_at'] = now();
        
        // Handle numeric defaults
        if (isset($updateData['amount']) && empty($updateData['amount'])) $updateData['amount'] = 0;
        if (isset($updateData['disbursed_count']) && empty($updateData['disbursed_count'])) $updateData['disbursed_count'] = 0;

        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) abort(404);

        // PREVENT DOUBLE PAYOUT: Check if any student in this batch is already paid elsewhere
        if (!empty($updateData['ada_no'])) {
            $batchScholars = DB::table('billing_scholars')
                ->where('billing_batch_id', $id)
                ->get();
            
            foreach ($batchScholars as $s) {
                if (empty($s->student_id_no)) continue;
                $sidClean = str_replace(['-', ' '], '', $s->student_id_no);
                
                $alreadyPaid = DB::table('billing_scholars')
                    ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
                    ->where(DB::raw('REPLACE(REPLACE(billing_scholars.student_id_no, "-", ""), " ", "")'), $sidClean)
                    ->where('billing_batches.ay', $batch->ay)
                    ->where('billing_batches.semester', $batch->semester)
                    ->where('billing_batches.id', '!=', $id)
                    ->whereNotNull('billing_batches.ada_no')
                    ->where('billing_batches.ada_no', '!=', '')
                    ->select('billing_batches.program', 'billing_batches.ada_no')
                    ->first();
                    
                if ($alreadyPaid) {
                    return back()->with('error', "<strong>Duplicate Payout Blocked!</strong><br>Student <strong>{$s->student_name}</strong> (ID: {$s->student_id_no}) has already been paid in <strong>{$alreadyPaid->program}</strong> (ADA: {$alreadyPaid->ada_no}) for this semester. Please remove this student or resolve the conflict before assigning an ADA number to this batch.");
                }
            }
        }

        DB::beginTransaction();
        try {
            Log::info("Update Request Params: " . json_encode($request->all()));

            if ($request->hasFile('pdf_attachment')) {
                $pdfPath = $request->file('pdf_attachment')->store('attachments', 'public');
                $updateData['pdf_attachment'] = $pdfPath;
            } elseif ($request->has('remove_pdf_attachment')) {
                $updateData['pdf_attachment'] = null;
            }

            if ($request->hasFile('disbursement_attachment')) {
                $disbursementPath = $request->file('disbursement_attachment')->store('attachments', 'public');
                $updateData['disbursement_attachment'] = $disbursementPath;
            } elseif ($request->has('remove_disbursement_attachment')) {
                $updateData['disbursement_attachment'] = null;
            }

            if ($request->hasFile('disbursement_scholar_file')) {
                $disScholarPath = $request->file('disbursement_scholar_file')->store('scholar_files', 'public');
                $updateData['disbursement_scholar_file'] = $disScholarPath;
                
                $dExt = $request->file('disbursement_scholar_file')->getClientOriginalExtension();
                if ($dExt !== 'pdf') {
                    // Extract if CSV
                    $dFullPath = storage_path('app/public/' . $disScholarPath);
                    $dHandle = fopen($dFullPath, "r");
                    fgetcsv($dHandle, 1000, ","); // skip header
                    while (($dData = fgetcsv($dHandle, 1000, ",")) !== FALSE) {
                        if (count($dData) < 1) continue;
                        $sName = trim($dData[0]);
                        $sId = isset($dData[1]) ? trim($dData[1]) : null;
                        if (empty($sName)) continue;
                        
                        $stdId = null;
                        if ($sId) {
                            $std = DB::table('students')->where('student_id_no', $sId)->first();
                            if ($std) $stdId = $std->id;
                        }
                        DB::table('billing_scholars')->insert([
                            'billing_batch_id' => $id,
                            'student_id' => $stdId,
                            'student_name' => $sName,
                            'student_id_no' => $sId,
                            'created_at' => now(),
                        ]);
                    }
                    fclose($dHandle);
                }
            } elseif ($request->has('remove_disbursement_scholar_file')) {
                $updateData['disbursement_scholar_file'] = null;
            }

            if ($request->hasFile('scholar_file')) {
                $file = $request->file('scholar_file');
                $filePath = $file->store('scholar_files', 'public');
                $updateData['scholar_file'] = $filePath;
                $extension = $file->getClientOriginalExtension();
                $fullPath = storage_path('app/public/' . $filePath);
                $scholarsToInsert = [];
                $scholarCount = 0;

                if ($extension === 'pdf') {
                    // USER REQUEST: Skip extraction for PDF files
                    Log::info("Skipping PDF extraction for scholar list in batch $id as per user request.");
                } else {
                    $handle = fopen($fullPath, "r");
                    $header = fgetcsv($handle, 1000, ",");
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) < 1) continue;
                        $scholarsToInsert[] = ['name' => trim($data[0]), 'id' => isset($data[1]) ? trim($data[1]) : null];
                    }
                    fclose($handle);

                    foreach ($scholarsToInsert as $s) {
                        if (empty($s['name'])) continue;
                        $studentId = null;
                        if ($s['id']) {
                            $student = DB::table('students')->where('student_id_no', $s['id'])->first();
                            if ($student) $studentId = $student->id;
                        }
                        DB::table('billing_scholars')->insert([
                            'billing_batch_id' => $id,
                            'student_id' => $studentId,
                            'student_name' => $s['name'],
                            'student_id_no' => $s['id'],
                            'created_at' => now(),
                        ]);
                        $scholarCount++;
                    }
                    $updateData['scholar_count'] = DB::table('billing_scholars')->where('billing_batch_id', $id)->count();
                }
            } elseif ($request->has('remove_scholar_file')) {
                $updateData['scholar_file'] = null;
            }

            DB::table('billing_batches')->where('id', $id)->update($updateData);
            
            DB::commit();

            $this->logActivity('Billing', 'Updated batch', "Updated batch ID: $id.");

            return redirect()->route('billing.index')->with('success', 'Billing batch updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'An error occurred while updating the billing batch.');
        }
    }

    public function removeAttachment(Request $request, $id)
    {
        $type = $request->input('type'); // pdf_attachment, scholar_file, disbursement_attachment, disbursement_scholar_file
        $validTypes = ['pdf_attachment', 'scholar_file', 'disbursement_attachment', 'disbursement_scholar_file'];

        if (!in_array($type, $validTypes)) {
            return response()->json(['success' => false, 'message' => 'Invalid attachment type.'], 400);
        }

        try {
            Log::info("Attempting to remove attachment '$type' for batch ID: $id");
            
            $affected = DB::table('billing_batches')->where('id', $id)->update([
                $type => null,
                'updated_at' => now()
            ]);

            if ($affected === 0) {
                Log::warning("No rows updated when removing attachment '$type' for batch ID: $id. It might already be null.");
            }

            $this->logActivity('Billing', 'Removed attachment', "Removed $type from batch ID: $id.");

            return response()->json(['success' => true, 'affected' => $affected]);
        } catch (\Exception $e) {
            Log::error("Error removing attachment: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
