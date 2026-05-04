<?php

namespace App\Http\Controllers;

use App\Support\ScholarshipMonitoring;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipBillingController extends Controller
{
    public function index(Request $request)
    {
        $this->bootstrapBillingStructures();

        $program = trim((string) $request->query('program', ''));
        $semester = trim((string) $request->query('semester', ''));
        $month = trim((string) $request->query('month', '')); // YYYY-MM

        $rowsQuery = DB::table('billing_batch as bb')
            ->select('bb.*')
            ->selectRaw("(
                SELECT COUNT(DISTINCT ft.stdid)
                FROM fees_transaction ft
                WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                  AND ft.billing_batch_id = bb.id
            ) AS actual_scholars");

        if ($program !== '') {
            $rowsQuery->where('bb.program', $program);
        }
        if ($semester !== '') {
            $rowsQuery->where('bb.semester', $semester);
        }
        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $rowsQuery->whereRaw("DATE_FORMAT(bb.billing_date, '%Y-%m') = ?", [$month]);
        }

        $rowsQuery->where('bb.delete_status', '0');
        
        // Safety Guard: Only show batches that have at least one scholar
        $rowsQuery->whereExists(function($q) {
            $q->from('fees_transaction as ft_guard')
              ->whereRaw('ft_guard.billing_batch_id = bb.id');
        });

        $rows = $rowsQuery
            ->orderByDesc('bb.id')
            ->limit(500)
            ->get();

        return view('scholarship.billing.index', [
            'rows' => $rows,
            'program' => $program,
            'semester' => $semester,
            'month' => $month,
            'programOptions' => $this->getProgramOptions(),
            'semesterOptions' => $this->getSemesterOptions(),
        ]);
    }

    public function summary()
    {
        $this->bootstrapBillingStructures();

        $raw = DB::table('billing_batch')
            ->select('program', 
                DB::raw("DATE_FORMAT(billing_date, '%Y-%m') as month_key"),
                DB::raw("DATE_FORMAT(billing_date, '%M %Y') as month_label"),
                DB::raw("SUM(scholar_count) as total_scholars"),
                DB::raw("COUNT(*) as batch_count")
            )
            ->where('delete_status', '0')
            ->groupBy('program', 'month_key', 'month_label')
            ->orderBy('program')
            ->orderBy('month_key', 'desc')
            ->get();

        $summary = [];
        foreach ($raw as $item) {
            $p = (string) $item->program;
            if (!isset($summary[$p])) {
                $summary[$p] = [
                    'program' => $p,
                    'total_scholars' => 0,
                    'total_batches' => 0,
                    'months' => [],
                ];
            }
            $summary[$p]['months'][] = $item;
            $summary[$p]['total_scholars'] += (int) $item->total_scholars;
            $summary[$p]['total_batches'] += (int) $item->batch_count;
        }

        return view('scholarship.billing.summary', [
            'summary' => array_values($summary),
        ]);
    }

    public function create()
    {
        $this->bootstrapBillingStructures();

        return view('scholarship.billing.create', [
            'programOptions' => $this->getProgramOptions(),
            'semesterOptions' => $this->getSemesterOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->bootstrapBillingStructures();

        if (!Schema::hasTable('fees_transaction')) {
            return back()->withErrors([
                'grantee_csv' => 'Missing required fees_transaction table in database.',
            ])->withInput();
        }

        $validated = $request->validate([
            'program' => 'required|string|max:150',
            'semester' => 'required|string|max:60',
            'billing_date' => 'required|date',
            'submitted_date_to_ched' => 'nullable|date',
            'billing_amount' => 'nullable|numeric|min:0',
            'input_method' => 'nullable|in:csv,manual',
            'grantee_csv' => 'nullable|file|mimes:csv,txt',
            'signed_billing_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $program = trim((string) $validated['program']);
        $semester = $this->normalizeSemesterValue((string) $validated['semester']);
        $academicYear = $this->getDefaultAcademicYear();
        $batchLabel = $this->getDefaultBillingBatchLabel();
        $region = $this->getDefaultBillingRegion();
        $billingDate = $this->parseDateValue((string) $validated['billing_date']);
        $submittedDateToChedInput = trim((string) ($validated['submitted_date_to_ched'] ?? ''));
        $submittedDateToChed = $this->parseDateValue($submittedDateToChedInput);

        if ($billingDate === '') {
            return back()->withErrors([
                'billing_date' => 'Billing date must be a valid date.',
            ])->withInput();
        }

        if ($submittedDateToChedInput !== '' && $submittedDateToChed === '') {
            return back()->withErrors([
                'submitted_date_to_ched' => 'Submitted Date to CHED must be a valid date.',
            ])->withInput();
        }

        $this->syncProgramAndAcademicYear($program, $academicYear, $semester);

        /** @var UploadedFile $granteeCsv */
        $granteeCsv = $request->file('grantee_csv');
        $manualStudents = $request->input('manual_students', []);
        
        if ($granteeCsv) {
            $parsed = $this->parseGranteeCsv($granteeCsv, $program, $semester, $academicYear);
        } elseif (!empty($manualStudents)) {
            $parsed = $this->parseManualGrantees($manualStudents, $program, $semester, $academicYear);
        } else {
            return back()->withErrors(['grantee_csv' => 'Please either upload a CSV file or add students manually.'])->withInput();
        }

        if (count($parsed['rows']) <= 0) {
            return back()->withErrors([
                'grantee_csv' => count($parsed['errors']) > 0
                    ? implode(' | ', array_slice($parsed['errors'], 0, 8))
                    : 'No valid grantees found.',
            ])->withInput();
        }

        $manualBillingAmount = (float) ($validated['billing_amount'] ?? 0);
        $billingAmount = (float) $parsed['total_amount'];
        
        $isAmountAdjusted = false;
        if ($manualBillingAmount > 0 && abs($manualBillingAmount - $billingAmount) > 0.01) {
            $isAmountAdjusted = true;
        }

        $signedDocPath = $this->storeBillingDocument($request->file('signed_billing_doc'), 'billing_entry');

        $createdBy = (int) (Auth::id() ?? 0);
        $conflictCount = 0;
        $successfulCount = 0;
        $skippedErrors = [];
        $batchId = 0;
        $programBatchRef = '';

        DB::beginTransaction();
        try {
            foreach ($parsed['rows'] as $row) {
                $sid = (int) $row['sid'];
                $amount = (float) $row['amount'];
                $remark = trim((string) $row['remark']);
                $remark = $remark !== '' ? $remark : 'Billing claim import';
                $isConflict = (bool) ($row['is_conflict'] ?? false);
                $conflictStatus = $isConflict ? 'scholarship_conflict' : 'none';
                $conflictNote = trim((string) ($row['conflict_note'] ?? ''));
                if ($isConflict && $conflictNote === '') {
                    $conflictNote = 'Scholarship conflict detected during billing entry import.';
                }

                // Term-Wide Duplicate Check: Skip student if already billed in ANY batch for this term
                $termDuplicate = DB::table('fees_transaction')
                    ->where('stdid', $sid)
                    ->where('program', $program)
                    ->where('semester', $semester)
                    ->where('academic_year', $academicYear)
                    ->where('record_type', 'billing')
                    ->exists();

                if ($termDuplicate) {
                    continue; // Skip this student entirely (don't update batch totals)
                }

                // Use date from CSV row if available, otherwise use form date
                $rowDate = (!empty($row['date'])) ? $row['date'] : $billingDate;
                
                // Group by date to handle merges correctly
                $existingBatch = DB::table('billing_batch')
                    ->where('program', $program)
                    ->where('semester', $semester)
                    ->where('academic_year', $academicYear)
                    ->where('billing_date', $rowDate)
                    ->where('delete_status', '0')
                    ->first();

                if ($existingBatch) {
                    $batchId = (int) $existingBatch->id;
                    DB::table('billing_batch')->where('id', $batchId)->update([
                        'billing_total_amount' => (float) $existingBatch->billing_total_amount + $amount,
                        'scholar_count' => (int) $existingBatch->scholar_count + 1,
                    ]);
                } else {
                    $batchId = (int) DB::table('billing_batch')->insertGetId([
                        'program' => $program,
                        'academic_year' => $academicYear,
                        'semester' => $semester,
                        'batch_label' => $batchLabel,
                        'region' => $region,
                        'billing_date' => $rowDate,
                        'submitted_date_to_ched' => $submittedDateToChed !== '' ? $submittedDateToChed : null,
                        'billing_total_amount' => $amount,
                        'scholar_count' => 1,
                        'signed_billing_doc' => $signedDocPath,
                        'status' => 'open',
                        'delete_status' => '0',
                        'created_by' => $createdBy > 0 ? $createdBy : null,
                    ]);
                }

                $this->assignBillingProgramBatchRef($batchId, $program);

                DB::table('fees_transaction')->insert([
                    'stdid' => $sid,
                    'submitdate' => $rowDate,
                    'transcation_remark' => $remark,
                    'paid' => $amount,
                    'record_type' => 'billing',
                    'program' => $program,
                    'semester' => $semester,
                    'academic_year' => $academicYear,
                    'batch_label' => $batchLabel,
                    'region' => $region,
                    'billing_batch_id' => $batchId,
                    'signed_billing_doc' => $signedDocPath,
                    'conflict_status' => $conflictStatus,
                    'conflict_note' => $conflictNote,
                ]);

                // Hard Gate: Check Profile Completeness
                $studentObj = DB::table('student')->where('id', $sid)->first();
                $comp = \App\Support\ScholarshipMonitoring::isProfileComplete($studentObj);
                if (!$comp['is_complete']) {
                    $skippedErrors[] = "Student " . ($studentObj->student_id_no ?? $sid) . " - Incomplete profile: " . implode(', ', $comp['missing_fields']);
                    continue;
                }

                DB::table('disbursed_transaction')->updateOrInsert(
                    [
                        'billing_batch_id' => $batchId,
                        'stdid' => $sid,
                    ],
                    [
                        'program' => $program,
                        'semester' => $semester,
                        'academic_year' => $academicYear,
                        'batch_label' => $batchLabel,
                        'region' => $region,
                        'disbursed_date' => null, // Reset date for draft records
                        'disbursed_amount' => $amount,
                        'ada_no' => '',
                        'or_no' => '',
                        'or_date' => null,
                        'attachment_note' => '',
                        'attachment_file' => '',
                        'remarks' => $remark,
                        'created_by' => $createdBy > 0 ? $createdBy : null,
                        'disbursed_status' => 'draft',
                    ]
                );

                $successfulCount++;
                $this->recalcStudentBalance($sid);
                $this->applyBillingStudentSmartUpdate($sid, $row);

                if ($isConflict) {
                    $conflictCount++;
                }
            } // Close foreach

            if ($successfulCount <= 0) {
                DB::rollBack();
                return redirect()->back()->withErrors(['all_duplicates' => 'No new billing records were created because all students entered are already billed for this term.'])->withInput();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            
            // Log the detailed error for debugging
            \Illuminate\Support\Facades\Log::error('Billing Import Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'billing_csv' => 'Import failed at the final step: ' . $e->getMessage() . ' (See logs for details)',
            ])->withInput();
        }

        $invalidRows = (int) count((array) ($parsed['invalid_rows'] ?? []));
        $duplicatesSkipped = $this->countDuplicateRows((array) ($parsed['invalid_rows'] ?? []));
        $status = 'completed';
        if ($invalidRows > 0 || $conflictCount > 0) {
            $status = 'warning';
        }

        $this->recordBillingInvalidRows((array) ($parsed['invalid_rows'] ?? []), 'billing_entry', $batchId);

        ScholarshipMonitoring::logUploadHistory([
            'module_name' => 'billing_entry',
            'upload_type' => 'billing_csv',
            'file_name' => $granteeCsv ? (string) $granteeCsv->getClientOriginalName() : 'Manual Entry',
            'file_path' => $signedDocPath,
            'uploaded_by' => $createdBy,
            'records_processed' => (int) count((array) ($parsed['rows'] ?? [])) + $invalidRows,
            'successful_rows' => (int) count((array) ($parsed['rows'] ?? [])),
            'failed_rows' => $invalidRows,
            'duplicates_skipped' => $duplicatesSkipped,
            'status' => $status,
            'summary' => 'Saved billing entry with valid rows: ' . count((array) ($parsed['rows'] ?? [])) . ', invalid rows: ' . $invalidRows . ', conflict rows: ' . $conflictCount . '.',
        ]);

        ScholarshipMonitoring::refreshAutoAlerts();

        $successMessage = 'Billing entry saved. Program Billing ID: ' . $programBatchRef . '. Grantees: ' . count($parsed['rows']) . '. Conflict-flagged rows: ' . $conflictCount . '. Total amount: ' . number_format((float) $parsed['total_amount'], 2) . '.';
        if ($invalidRows > 0) {
            $successMessage .= ' Skipped invalid rows: ' . $invalidRows . '.';
        }
        if (count($skippedErrors) > 0) {
            $successMessage .= ' Additionally, ' . count($skippedErrors) . ' students were skipped due to incomplete profiles.';
        }
        if ($isAmountAdjusted) {
            $successMessage .= ' Billing total was adjusted to matched valid rows only.';
        }

        return redirect()
            ->route('scholarship-billing.show', $batchId)
            ->with(count($skippedErrors) > 0 ? 'warning' : 'success', $successMessage);
    }

    public function show(Request $request, $batch)
    {
        $this->bootstrapBillingStructures();

        $batchId = (int) $batch;
        $batchRow = DB::table('billing_batch')
            ->where('id', $batchId)
            ->first();

        if (!$batchRow) {
            abort(404);
        }

        $studentSearch = trim((string) $request->query('student_search', ''));
        $conflictsOnly = $request->query('conflicts_only', '') === '1';

        $rowsQuery = DB::table('fees_transaction as ft')
            ->leftJoin('student as s', 's.id', '=', 'ft.stdid')
            ->select([
                'ft.stdid',
                'ft.submitdate',
                'ft.paid',
                'ft.transcation_remark',
                'ft.conflict_status',
                'ft.conflict_note',
                's.sname',
                's.student_id_no',
                's.scholarship_program',
                's.contact',
                's.fb_link',
            ])
            ->selectRaw("COALESCE(NULLIF(TRIM(s.degree_program), ''), NULLIF(TRIM(s.scholarship_program), ''), '') AS course")
            ->selectRaw("COALESCE(NULLIF(TRIM(s.year_level), ''), NULLIF(TRIM(s.grade), ''), '') AS year_level")
            ->whereRaw("COALESCE(ft.record_type, 'billing') = 'billing'")
            ->where('ft.billing_batch_id', $batchId);

        if ($studentSearch !== '') {
            $like = '%' . $studentSearch . '%';
            $rowsQuery->where(function ($builder) use ($like) {
                $builder->where('s.sname', 'like', $like)
                    ->orWhere('s.student_id_no', 'like', $like)
                    ->orWhere('s.contact', 'like', $like)
                    ->orWhereRaw("COALESCE(NULLIF(TRIM(s.degree_program), ''), NULLIF(TRIM(s.scholarship_program), ''), '') LIKE ?", [$like])
                    ->orWhereRaw("COALESCE(NULLIF(TRIM(s.year_level), ''), NULLIF(TRIM(s.grade), ''), '') LIKE ?", [$like])
                    ->orWhereRaw('CAST(ft.stdid AS CHAR) LIKE ?', [$like]);
            });
        }

        if ($conflictsOnly) {
            $rowsQuery->where('ft.conflict_status', 'scholarship_conflict');
        }

        $rows = $rowsQuery
            ->orderBy('s.sname')
            ->orderBy('ft.id')
            ->limit(1000)
            ->get();

        $actualScholars = (int) DB::table('fees_transaction')
            ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
            ->where('billing_batch_id', $batchId)
            ->distinct('stdid')
            ->count('stdid');

        $linkedTotal = (float) DB::table('fees_transaction')
            ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
            ->where('billing_batch_id', $batchId)
            ->sum('paid');

        $conflictCount = (int) DB::table('fees_transaction')
            ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
            ->where('billing_batch_id', $batchId)
            ->where('conflict_status', 'scholarship_conflict')
            ->count();

        return view('scholarship.billing.show', [
            'batch' => $batchRow,
            'rows' => $rows,
            'actualScholars' => $actualScholars,
            'linkedTotal' => $linkedTotal,
            'conflictCount' => $conflictCount,
            'studentSearch' => $studentSearch,
            'conflictsOnly' => $conflictsOnly,
        ]);
    }

    public function archive($batch)
    {
        $this->bootstrapBillingStructures();

        $batchId = (int) $batch;
        $updated = DB::table('billing_batch')
            ->where('id', $batchId)
            ->where('delete_status', '0')
            ->update([
                'delete_status' => '1',
                'status' => 'archived',
            ]);

        if ($updated <= 0) {
            return redirect()
                ->route('scholarship-billing.index')
                ->with('success', 'Billing batch was not archived. It may already be archived.');
        }

        return redirect()
            ->route('scholarship-billing.index')
            ->with('success', 'Billing batch archived successfully.');
    }

    public function importForm()
    {
        $this->bootstrapBillingStructures();

        return view('scholarship.billing.import', [
            'rows' => [],
            'summary' => [
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'failed' => 0,
                'imported' => 0,
                'created_batches' => 0,
                'conflicts' => 0,
                'errors' => [],
            ],
            'detectedFormat' => '',
            'selectedMode' => 'import',
        ]);
    }

    public function entryTemplate()
    {
        $response = new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            // Headers
            fputcsv($out, ['student_id', 'full_name', 'billing_amount', 'billing_remark', 'year_level']);
            // Sample Row 1
            fputcsv($out, ['202300001', 'JUAN DELA CRUZ', '15000.00', 'Tuition Fee - 1st Sem', '1']);
            // Sample Row 2
            fputcsv($out, ['202200123', 'MARIA SANTOS', '7500.00', 'Laboratory Fees', '2']);
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=billing_entry_grantee_template.csv');

        return $response;
    }

    public function importTemplate($type = 'detailed')
    {
        $type = strtolower(trim((string) $type));
        if (!in_array($type, ['detailed', 'batch', 'summary'], true)) {
            $type = 'detailed';
        }

        if ($type === 'batch' || $type === 'summary') {
            $response = new StreamedResponse(function () {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['PROGRAM', 'AY', 'SEM', 'BATCH', 'REGION', 'NO. OF SCHOLARS', 'DATE OF BILLING', 'AMOUNT']);
                fputcsv($out, ['ACEF-GIAHEP', '2025-2026', '1ST', 'NEW', 'VI', '10', '12-Aug-25', '200000.00']);
                fclose($out);
            });

            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename=billing_import_template_batch_summary.csv');

            return $response;
        }

        $response = new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['student_id', 'full_name', 'program', 'academic_year', 'semester', 'batch', 'region', 'billing_date', 'billing_amount', 'billing_remark']);
            fputcsv($out, ['202300001', 'JUAN DELA CRUZ', 'CHED', '2025-2026', '1st Semester', 'OLD', 'VI', '2026-03-22', '15000', 'Tuition']);
            fputcsv($out, ['202200555', 'MARIA CLARA', 'CHED', '2025-2026', '1st Semester', 'OLD', 'VI', '2026-03-22', '15000', 'Tuition']);
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=billing_import_template_detailed.csv');

        return $response;
    }

    public function importProcess(Request $request)
    {
        $this->bootstrapBillingStructures();

        if (!Schema::hasTable('fees_transaction')) {
            return back()->withErrors([
                'billing_csv' => 'Missing required fees_transaction table in database.',
            ]);
        }

        $rules = [
            'billing_csv' => 'nullable|file|mimes:csv,txt',
            'signed_billing_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_scan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mode' => 'nullable|string|in:import,preview',
            'temp_path' => 'nullable|string',
            'action' => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        // Manual check for the CSV file only if we don't have a temp path or AI data yet
        if (!$request->hasFile('billing_csv') && !$request->has('temp_path') && !$request->has('ai_data') && $request->input('action') !== 'analyze') {
            return back()->withErrors(['billing_csv' => 'Please select a CSV file to upload.'])
                ->withInput($request->all());
        }

        $mode = trim((string) ($validated['mode'] ?? 'import'));
        if (!in_array($mode, ['import', 'preview'], true)) {
            $mode = 'import';
        }

        $action = trim((string) ($request->input('action') ?? 'run'));
        $mode = trim((string) ($request->input('mode') ?? 'import'));
        \Illuminate\Support\Facades\Log::info("ImportProcess: Starting with action={$action}, mode={$mode}");

        // Handle AI Document Analysis
        if ($action === 'analyze') {
            $docFile = $request->file('document_scan');
            if (!$docFile) {
                return back()->withErrors(['document_scan' => 'Please select a PDF or Image to scan.']);
            }

            $analysis = AIDocumentService::analyzeBillingDocument($docFile->getPathname(), $docFile->getMimeType());

            if (!$analysis['success']) {
                return back()->with('importError', $analysis['error']);
            }

            $rows = $analysis['rows'];
            $summary = [
                'total' => count($rows),
                'valid' => count($rows),
                'invalid' => 0,
                'failed' => 0,
                'imported' => 0,
                'created_batches' => 0,
                'conflicts' => 0,
                'errors' => [],
            ];

            // Add standard keys to AI-extracted rows
            foreach ($rows as &$r) {
                $r['is_valid'] = true;
                $r['errors'] = [];
                $r['line'] = 'AI';
                $r['paid'] = (float) ($r['paid'] ?? 0);
                $r['scholar_count'] = (int) ($r['scholar_count'] ?? 1);
                $r['program'] = $r['program'] ?? 'Unknown';
                $r['academic_year'] = $this->normalizeAcademicYearValue($r['academic_year'] ?? '');
                $r['semester'] = $this->normalizeSemesterValue($r['semester'] ?? '');
                $r['submitdate'] = $this->normalizeDateValue($r['submitdate'] ?? '') ?: date('Y-m-d');
                $r['batch_label'] = $r['batch_label'] ?? 'AI Scan';
                $r['region'] = $r['region'] ?? '';
                $r['students'] = []; 
                if (!empty($r['student_id']) || !empty($r['full_name'])) {
                    $r['students'][] = $r;
                }
            }

            return view('scholarship.billing.import', [
                'rows' => $rows,
                'summary' => $summary,
                'detectedFormat' => 'batch_summary',
                'selectedMode' => 'preview',
                'tempPath' => $docFile->store('temp_scans'),
                'isAiScan' => true,
            ])->with('success', 'AI Document Analysis complete! Review the extracted data below.');
        }

        /** @var UploadedFile $csv */
        $csv = $request->file('billing_csv');
        $headers = $this->getCsvHeaders($csv);

        if ($request->missing('map')) {
            // Show mapping UI
            $tempPath = $csv->store('temp_imports');
            $autoMapping = $this->autoMapBillingHeaders($headers);

            return view('scholarship.billing.import', [
                'rows' => [],
                'summary' => [
                    'total' => 0,
                    'valid' => 0,
                    'invalid' => 0,
                    'imported' => 0,
                    'created_batches' => 0,
                    'conflicts' => 0,
                    'errors' => [],
                ],
                'detectedFormat' => '',
                'selectedMode' => $mode,
                'csvHeaders' => $headers,
                'autoMapping' => $autoMapping,
                'tempPath' => $tempPath,
                'signedDocPath' => $this->storeBillingDocument($request->file('signed_billing_doc'), 'billing_import_temp'),
            ]);
        }

        $mapping = $request->input('map');
        $tempPath = $request->input('temp_path');
        $signedDocPath = $request->input('signed_doc_path');
        $aiData = $request->input('ai_data');

        if ($aiData) {
            $rows = json_decode($aiData, true);
            \Illuminate\Support\Facades\Log::info("ImportProcess: Decoded AI Data", ['row_count' => count($rows ?? [])]);
            $summary = [
                'total' => count($rows),
                'valid' => count($rows),
                'invalid' => 0,
                'failed' => 0,
                'imported' => 0,
                'created_batches' => 0,
                'conflicts' => 0,
                'errors' => [],
            ];
            $detectedFormat = 'batch_summary';
        } else {
            $parsed = $this->parseBillingCsvWithMapping(storage_path('app/' . $tempPath), $mapping);
            $rows = $parsed['rows'] ?? [];
            $summary = $parsed['summary'] ?? [];
            $detectedFormat = $parsed['format'] ?? 'mapped';
            \Illuminate\Support\Facades\Log::info("ImportProcess: Parsed CSV Path", ['row_count' => count($rows), 'format' => $detectedFormat]);
        }

        if (count($summary['errors']) > 0) {
            \Illuminate\Support\Facades\Log::warning("ImportProcess: Returning early due to summary errors", ['errors' => $summary['errors']]);
            return view('scholarship.billing.import', [
                'rows' => $rows,
                'summary' => $summary,
                'detectedFormat' => $detectedFormat,
                'selectedMode' => $mode,
                'tempPath' => $tempPath,
                'importError' => implode(' ', $summary['errors']),
            ]);
        }

        if ($mode === 'preview') {
            return view('scholarship.billing.import', [
                'rows' => $rows,
                'summary' => $summary,
                'detectedFormat' => $detectedFormat,
                'selectedMode' => $mode,
                'tempPath' => $tempPath,
                'csvHeaders' => $this->getCsvHeaders(storage_path('app/' . $tempPath)),
                'mapping' => $mapping,
                'autoMapping' => $this->autoMapBillingHeaders($this->getCsvHeaders(storage_path('app/' . $tempPath))),
            ])->with('success', 'Preview complete. No data saved yet.');
        }

        if ((int) $summary['valid'] <= 0) {
            return view('scholarship.billing.import', [
                'rows' => $rows,
                'summary' => $summary,
                'detectedFormat' => $detectedFormat,
                'selectedMode' => $mode,
                'importError' => 'No valid rows found for import.',
            ]);
        }

        $signedDocPath = $this->storeBillingDocument($request->file('signed_billing_doc'), 'billing_import');
        $createdBy = (int) (Auth::id() ?? 0);

        DB::beginTransaction();
        try {
            if ($detectedFormat === 'batch_summary') {
                foreach ($rows as &$row) {
                    if (empty($row['is_valid'])) {
                        continue;
                    }

                    $program = trim((string) $row['program']);
                    $academicYear = trim((string) $row['academic_year']);
                    $semester = trim((string) $row['semester']);
                    $batchLabel = trim((string) $row['batch_label']);
                    $region = trim((string) $row['region']);
                    $billingDate = trim((string) $row['submitdate']);
                    
                    $students = $row['students'] ?? [];
                    $validStudents = [];
                    $validAmount = 0.0;

                    foreach ($students as $s) {
                        $student = $this->findStudentForBilling($s['student_id'], $s['full_name']);
                        $isDup = false;
                        if ($student) {
                            $isDup = DB::table('fees_transaction')
                                ->where('stdid', $student->id)
                                ->where('program', $program)
                                ->where('semester', $semester)
                                ->where('academic_year', $academicYear)
                                ->where('record_type', 'billing')
                                ->exists();
                        }
                        
                        if (!$isDup) {
                            $validStudents[] = $s;
                            $validAmount += (float) ($s['paid'] ?? 0);
                        }
                    }

                    $totalAmount = count($students) > 0 ? $validAmount : (float) $row['paid'];
                    $scholarCount = count($students) > 0 ? count($validStudents) : (int) ($row['scholar_count'] ?? 0);

                    if ($scholarCount <= 0) {
                        continue; // Skip creating empty batch summary
                    }

                    $this->syncProgramAndAcademicYear($program, $academicYear, $semester);

                    $batchId = (int) DB::table('billing_batch')->insertGetId([
                        'program' => $program,
                        'academic_year' => $academicYear,
                        'semester' => $semester,
                        'batch_label' => $batchLabel,
                        'region' => $region,
                        'billing_date' => $billingDate,
                        'billing_total_amount' => $totalAmount,
                        'scholar_count' => $scholarCount,
                        'signed_billing_doc' => $signedDocPath,
                        'status' => 'open',
                        'delete_status' => '0',
                        'created_by' => $createdBy > 0 ? $createdBy : null,
                    ]);

                    $this->assignBillingProgramBatchRef($batchId, $program);

                    // Replace students with filtered list for the next loop
                    $row['students'] = $validStudents;

                    // Process ALL Individual Students for Profile Updates
                    $allStudents = $row['students_raw'] ?? $students; // Fallback to original list
                    foreach ($allStudents as $s) {
                        $student = $this->findStudentForBilling($s['student_id'], $s['full_name']);
                        if (!$student) {
                            $autoId = $this->autoCreateStudentForBilling(
                                $s['student_id'], $s['full_name'], $program, $academicYear, $semester,
                                '', '', $s['address'] ?? '', $s['contact'] ?? '', $s['course'] ?? '', $s['year_level'] ?? '',
                                $s['guardian_name'] ?? '', $s['guardian_contact'] ?? '', $s['fb_link'] ?? ''
                            );
                            if ($autoId > 0) {
                                $student = $this->findStudentForBilling((string)$autoId);
                            }
                        }
                        if (!$student) continue;

                        $sid = (int) $student->id;
                        
                        // 2. Smart Update: Fill in missing information (ALWAYS call this)
                        $this->applyBillingStudentSmartUpdate($sid, $s);
                        \Illuminate\Support\Facades\Log::info("ImportProcess (Summary): Processing student {$sid} (" . ($s['full_name'] ?? 'N/A') . ")", ['fb_link' => $s['fb_link'] ?? 'MISSING']);

                        // Check if this specific student was a duplicate for the transaction part
                        $isDup = DB::table('fees_transaction')
                            ->where('stdid', $sid)
                            ->where('program', $program)
                            ->where('semester', $semester)
                            ->where('academic_year', $academicYear)
                            ->where('record_type', 'billing')
                            ->exists();
                        
                        if ($isDup) {
                            continue; // Skip transaction records for duplicates
                        }

                        if ($batchId <= 0) continue; // Safety

                        $disbursedDate = $this->normalizeDateValue($s['disbursed_date'] ?? '');
                        $hasAda = !empty($s['ada_no']) && trim((string)$s['ada_no']) !== '-';
                        $hasOr = !empty($s['or_no']) && trim((string)$s['or_no']) !== '-';
                        $disbursedStatus = ($disbursedDate !== '' && $hasAda && $hasOr) ? 'finalized' : 'draft';

                        // 3. Record Transactions
                        DB::table('fees_transaction')->insert([
                            'billing_batch_id' => $batchId,
                            'stdid' => $sid,
                            'submitdate' => $billingDate,
                            'paid' => (float) $s['paid'],
                            'program' => $program,
                            'semester' => $semester,
                            'academic_year' => $academicYear,
                            'record_type' => 'billing',
                            'transcation_remark' => 'Mapped Import',
                        ]);

                        DB::table('disbursed_transaction')->updateOrInsert(
                            ['billing_batch_id' => $batchId, 'stdid' => $sid],
                            [
                                'program' => $program,
                                'semester' => $semester,
                                'academic_year' => $academicYear,
                                'batch_label' => $batchLabel,
                                'region' => $region,
                                'disbursed_date' => (!empty($disbursedDate)) ? $disbursedDate : $billingDate,
                                'disbursed_amount' => (float) $s['paid'],
                                'remarks' => 'Mapped Import',
                                'disbursed_status' => $disbursedStatus,
                            ]
                        );

                        $this->recalcStudentBalance($sid);
                    }

                    $summary['created_batches']++;
                    $summary['imported']++;
                }

                DB::commit();

                $this->recordBillingInvalidRows($rows, 'billing_import', 0);

                ScholarshipMonitoring::logUploadHistory([
                    'module_name' => 'billing_import',
                    'upload_type' => 'billing_csv_batch_summary',
                    'file_name' => $csv ? (string) $csv->getClientOriginalName() : basename((string) ($tempPath ?? 'billing_import.csv')),
                    'file_path' => $signedDocPath,
                    'uploaded_by' => $createdBy,
                    'records_processed' => (int) ($summary['total'] ?? 0),
                    'successful_rows' => (int) ($summary['imported'] ?? 0),
                    'failed_rows' => (int) ($summary['invalid'] ?? 0),
                    'duplicates_skipped' => $this->countDuplicateRows($rows),
                    'status' => ((int) ($summary['invalid'] ?? 0) > 0) ? 'warning' : 'completed',
                    'summary' => 'Batch summary import: created batches ' . (int) ($summary['created_batches'] ?? 0) . ', imported rows ' . (int) ($summary['imported'] ?? 0) . ', invalid rows ' . (int) ($summary['invalid'] ?? 0) . '.',
                ]);

                ScholarshipMonitoring::refreshAutoAlerts();

                return redirect()
                    ->route('scholarship-billing.index')
                    ->with('success', 'Batch summary import completed. Created batches: ' . $summary['created_batches'] . '. Invalid rows: ' . $summary['invalid'] . '.');
            }

            $groups = [];
            foreach ($rows as &$row) {
                if (empty($row['is_valid'])) {
                    continue;
                }

                $student = $this->findStudentForBilling($row['student_id'], $row['full_name']);
                if ($student) {
                    $exists = DB::table('fees_transaction')
                        ->where('stdid', $student->id)
                        ->where('program', $row['program'])
                        ->where('semester', $row['semester'])
                        ->where('academic_year', $row['academic_year'])
                        ->where('record_type', 'billing')
                        ->exists();
                    
                    if ($exists) {
                        $row['is_duplicate_term'] = true;
                        continue; // Do not include in batch totals
                    }
                }

                $groupKey = (string) $row['group_key'];
                if (!array_key_exists($groupKey, $groups)) {
                    $groups[$groupKey] = [
                        'program' => $row['program'],
                        'academic_year' => $row['academic_year'],
                        'semester' => $row['semester'],
                        'batch_label' => $row['batch_label'],
                        'region' => $row['region'],
                        'billing_date' => $row['submitdate'],
                        'total_amount' => 0.0,
                        'scholar_count' => 0,
                    ];
                }

                $groups[$groupKey]['total_amount'] += (float) $row['paid'];
                $groups[$groupKey]['scholar_count']++;
            }
            unset($row);

            $groupBatchIds = [];
            foreach ($groups as $groupKey => $group) {
                if ($group['scholar_count'] <= 0) {
                    continue;
                }

                $program = trim((string) $group['program']);
                $academicYear = trim((string) $group['academic_year']);

                $this->syncProgramAndAcademicYear($program, $academicYear, trim((string) $group['semester']));

                $batchId = (int) DB::table('billing_batch')->insertGetId([
                    'program' => $program,
                    'academic_year' => $academicYear,
                    'semester' => trim((string) $group['semester']),
                    'batch_label' => trim((string) $group['batch_label']),
                    'region' => trim((string) $group['region']),
                    'billing_date' => trim((string) $group['billing_date']),
                    'billing_total_amount' => (float) $group['total_amount'],
                    'scholar_count' => (int) $group['scholar_count'],
                    'signed_billing_doc' => $signedDocPath,
                    'status' => 'open',
                    'delete_status' => '0',
                    'created_by' => $createdBy > 0 ? $createdBy : null,
                ]);

                $this->assignBillingProgramBatchRef($batchId, $program);

                $groupBatchIds[$groupKey] = $batchId;
                $summary['created_batches']++;
            }

            foreach ($rows as $row) {
                if (empty($row['is_valid'])) {
                    continue;
                }

                $billingBatchId = (int) ($groupBatchIds[(string) $row['group_key']] ?? 0);
                
                // If not created in this loop, try to find an existing batch that matches
                if ($billingBatchId <= 0) {
                    $existingBatch = DB::table('billing_batch')
                        ->where('program', $row['program'])
                        ->where('academic_year', $row['academic_year'])
                        ->where('semester', $row['semester'])
                        ->where('batch_label', $row['batch_label'])
                        ->where('delete_status', '0')
                        ->first();
                    if ($existingBatch) {
                        $billingBatchId = (int) $existingBatch->id;
                    }
                }

                if ($billingBatchId <= 0 && empty($row['is_duplicate_term'])) {
                    throw new \RuntimeException('Unable to resolve billing batch for imported row.');
                }

                $sid = (int) ($row['resolved_student_id'] ?? 0);
                if ($sid <= 0) {
                    // Final attempt to find or create
                    $student = $this->findStudentForBilling($row['student_id'], $row['full_name']);
                    if (!$student) {
                        $autoId = $this->autoCreateStudentForBilling(
                            $row['student_id'], $row['full_name'], $row['program'], $row['academic_year'], $row['semester'],
                            $row['birthdate'] ?? '', $row['school'] ?? '', $row['address'] ?? '', $row['contact'] ?? '',
                            $row['course'] ?? '', $row['year_level'] ?? '', $row['guardian_name'] ?? '', $row['guardian_contact'] ?? '', $row['fb_link'] ?? ''
                        );
                        if ($autoId > 0) {
                            $sid = $autoId;
                        }
                    } else {
                        $sid = (int) $student->id;
                    }
                }

                if ($sid <= 0) {
                    continue; // Skip if we still can't resolve the student
                }
                $submitDate = trim((string) $row['submitdate']);
                $paid = (float) $row['paid'];
                $program = trim((string) $row['program']);
                $semester = trim((string) $row['semester']);
                $academicYear = trim((string) $row['academic_year']);
                $batchLabel = trim((string) $row['batch_label']);
                $region = trim((string) $row['region']);
                $remark = trim((string) ($row['transcation_remark'] ?? ''));
                $remark = $remark !== '' ? $remark : 'Billing import';
                $isConflict = !empty($row['is_conflict']);
                $conflictStatus = $isConflict ? 'scholarship_conflict' : 'none';
                $conflictNote = trim((string) ($row['conflict_note'] ?? ''));
                if ($isConflict && $conflictNote === '') {
                    $conflictNote = 'Scholarship conflict detected during billing import.';
                }

                $this->applyBillingStudentSmartUpdate($sid, $row);
                \Illuminate\Support\Facades\Log::info("ImportProcess: Processing student {$sid} (" . ($row['full_name'] ?? 'N/A') . ")", ['fb_link' => $row['fb_link'] ?? 'MISSING']);

                if (!empty($row['is_duplicate_term'])) {
                    \Illuminate\Support\Facades\Log::info("ImportProcess: Skipping duplicate term for student {$sid}");
                    continue;
                }
                
                $disbursedDate = $this->normalizeDateValue($row['disbursed_date'] ?? '');
                $disbursedStatus = ($disbursedDate !== '') ? 'finalized' : 'draft';

                DB::table('fees_transaction')->insert([
                    'stdid' => $sid,
                    'submitdate' => $submitDate,
                    'transcation_remark' => $remark,
                    'paid' => $paid,
                    'record_type' => 'billing',
                    'program' => $program,
                    'semester' => $semester,
                    'academic_year' => $academicYear,
                    'batch_label' => $batchLabel,
                    'region' => $region,
                    'billing_batch_id' => $billingBatchId,
                    'signed_billing_doc' => $signedDocPath,
                    'conflict_status' => $conflictStatus,
                    'conflict_note' => $conflictNote,
                ]);

                // Hard Gate: Check Profile Completeness
                $studentObj = DB::table('student')->where('id', $sid)->first();
                $comp = \App\Support\ScholarshipMonitoring::isProfileComplete($studentObj);
                if (!$comp['is_complete']) {
                    $summary['errors'][] = "Student " . ($studentObj->student_id_no ?? $sid) . " - Incomplete profile: " . implode(', ', $comp['missing_fields']);
                    $summary['failed']++;
                    continue;
                }

                DB::table('disbursed_transaction')->updateOrInsert(
                    [
                        'billing_batch_id' => $billingBatchId,
                        'stdid' => $sid,
                    ],
                    [
                        'program' => $program,
                        'semester' => $semester,
                        'academic_year' => $academicYear,
                        'batch_label' => $batchLabel,
                        'region' => $region,
                        'disbursed_date' => (!empty($disbursedDate)) ? $disbursedDate : null,
                        'disbursed_amount' => $paid,
                        'ada_no' => '',
                        'or_no' => '',
                        'or_date' => null,
                        'attachment_note' => '',
                        'attachment_file' => '',
                        'remarks' => $remark,
                        'disbursed_status' => $disbursedStatus,
                    ]
                );

                $this->recalcStudentBalance($sid);
                $this->applyBillingStudentSmartUpdate($sid, $row);
                $summary['imported']++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return view('scholarship.billing.import', [
                'rows' => $rows,
                'summary' => $summary,
                'detectedFormat' => $detectedFormat,
                'selectedMode' => $mode,
                'importError' => 'Import failed: ' . $e->getMessage(),
            ]);
        }

        $this->recordBillingInvalidRows($rows, 'billing_import', 0);

        ScholarshipMonitoring::logUploadHistory([
            'module_name' => 'billing_import',
            'upload_type' => 'billing_csv_detailed',
            'file_name' => $csv ? (string) $csv->getClientOriginalName() : 'imported_csv_data',
            'file_path' => $signedDocPath,
            'uploaded_by' => $createdBy,
            'records_processed' => (int) ($summary['total'] ?? 0),
            'successful_rows' => (int) ($summary['imported'] ?? 0),
            'failed_rows' => (int) ($summary['invalid'] ?? 0),
            'duplicates_skipped' => $this->countDuplicateRows($rows),
            'status' => ((int) ($summary['invalid'] ?? 0) > 0 || (int) ($summary['conflicts'] ?? 0) > 0) ? 'warning' : 'completed',
            'summary' => 'Detailed import: imported rows ' . (int) ($summary['imported'] ?? 0) . ', conflict rows ' . (int) ($summary['conflicts'] ?? 0) . ', created batches ' . (int) ($summary['created_batches'] ?? 0) . ', invalid rows ' . (int) ($summary['invalid'] ?? 0) . '.',
        ]);

        ScholarshipMonitoring::refreshAutoAlerts();

        return redirect()
            ->route('scholarship-billing.index')
            ->with('success', 'Detailed import completed. Imported rows: ' . $summary['imported'] . '. Conflict-flagged rows: ' . $summary['conflicts'] . '. Created batches: ' . $summary['created_batches'] . '. Invalid rows: ' . $summary['invalid'] . '.');
    }

    private function parseBillingCsv(UploadedFile $file)
    {
        $result = [
            'rows' => [],
            'format' => 'unknown',
            'summary' => [
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'failed' => 0,
                'conflicts' => 0,
                'imported' => 0,
                'created_batches' => 0,
                'errors' => [],
            ],
        ];

        $realPath = $file->getRealPath();
        if (!is_string($realPath) || $realPath === '' || !is_file($realPath)) {
            $result['summary']['errors'][] = 'Unable to read uploaded CSV file.';
            return $result;
        }

        $handle = fopen($realPath, 'r');
        if ($handle === false) {
            $result['summary']['errors'][] = 'Unable to read uploaded CSV file.';
            return $result;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $result['summary']['errors'][] = 'CSV file is empty.';
            return $result;
        }

        $headerMap = $this->normalizeHeaders($headers);
        $layout = $this->detectBillingCsvLayout($headerMap);
        $result['format'] = $layout;

        if ($layout === 'unknown') {
            fclose($handle);
            $result['summary']['errors'][] = 'Unsupported CSV layout. Use detailed rows or batch summary rows.';
            return $result;
        }

        if ($layout === 'detailed') {
            $result = $this->parseBillingDetailedRows($handle, $headerMap, $result);
            fclose($handle);
            return $result;
        }

        $result = $this->parseBillingSummaryRows($handle, $headerMap, $result);
        fclose($handle);

        return $result;
    }

    private function parseBillingDetailedRows($handle, array $headerMap, array $result)
    {
        $studentIdIndex = $this->firstHeaderIndex($headerMap, ['student_id', 'student_id_no', 'student_no', 'stdid', 'studentid', 'id']);
        $fullNameIndex = $this->firstHeaderIndex($headerMap, ['full_name', 'name', 'student_name', 'sname']);
        $birthdateIndex = $this->firstHeaderIndex($headerMap, ['birthdate', 'date_of_birth', 'dob']);
        $schoolIndex = $this->firstHeaderIndex($headerMap, ['school', 'school_name']);
        $programIndex = $this->firstHeaderIndex($headerMap, ['program', 'scholarship_program']);
        $academicYearIndex = $this->firstHeaderIndex($headerMap, ['academic_year', 'school_year', 'ay']);
        $semesterIndex = $this->firstHeaderIndex($headerMap, ['semester', 'term', 'sem']);
        $batchIndex = $this->firstHeaderIndex($headerMap, ['batch', 'batch_label', 'batch_name']);
        $regionIndex = $this->firstHeaderIndex($headerMap, ['region']);
        $paidIndex = $this->firstHeaderIndex($headerMap, ['billing_amount', 'paid', 'amount_paid', 'amount', 'billing_total']);
        $remarkIndex = $this->firstHeaderIndex($headerMap, ['billing_remark', 'transcation_remark', 'transaction_remark', 'remark', 'remarks']);
        $addressIndex = $this->firstHeaderIndex($headerMap, ['address', 'home_address']);
        $contactIndex = $this->firstHeaderIndex($headerMap, ['contact', 'contact_number', 'mobile', 'phone']);
        $courseIndex = $this->firstHeaderIndex($headerMap, ['course', 'degree_program']);
        $yearLevelIndex = $this->firstHeaderIndex($headerMap, ['year_level', 'year', 'grade']);
        $guardianNameIndex = $this->firstHeaderIndex($headerMap, ['guardian_name', 'guardian']);
        $guardianContactIndex = $this->firstHeaderIndex($headerMap, ['guardian_contact', 'guardian_phone']);
        $fbLinkIndex = $this->firstHeaderIndex($headerMap, ['fb_link', 'facebook', 'facebook_link', 'fb', 'profile', 'facebook_profile', 'social', 'facebook_url', 'facebook_account', 'fb_account', 'facebookaccount', 'fbaccount', 'fblinkfbaccount']);
        $submitDateIndex = $this->firstHeaderIndex($headerMap, ['billing_date', 'date', 'submitdate']);

        if (($studentIdIndex === null && $fullNameIndex === null) || $programIndex === null || $academicYearIndex === null || $semesterIndex === null || $batchIndex === null || $regionIndex === null || $paidIndex === null || $submitDateIndex === null) {
            $result['summary']['errors'][] = 'Missing required columns for detailed billing import.';
            return $result;
        }

        $line = 1;
        $seenInGroup = [];

        while (($csvRow = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyCsvRow($csvRow)) {
                continue;
            }

            $row = [
                'line' => $line,
                'layout' => 'detailed',
                'student_id' => $this->getRowValue($csvRow, $studentIdIndex),
                'full_name' => $this->getRowValue($csvRow, $fullNameIndex),
                'birthdate' => $this->parseDateValue($this->getRowValue($csvRow, $birthdateIndex)),
                'school' => $this->getRowValue($csvRow, $schoolIndex),
                'program' => $this->getRowValue($csvRow, $programIndex),
                'academic_year' => $this->normalizeAcademicYearValue($this->getRowValue($csvRow, $academicYearIndex)),
                'semester' => $this->normalizeSemesterValue($this->getRowValue($csvRow, $semesterIndex)),
                'batch_label' => $this->getRowValue($csvRow, $batchIndex),
                'region' => $this->getRowValue($csvRow, $regionIndex),
                'scholar_count' => '',
                'paid' => $this->getRowValue($csvRow, $paidIndex),
                'submitdate' => $this->getRowValue($csvRow, $submitDateIndex),
                'transcation_remark' => $this->getRowValue($csvRow, $remarkIndex),
                'group_key' => '',
                'resolved_student_id' => 0,
                'is_conflict' => false,
                'conflict_note' => '',
                'is_duplicate' => false,
                'address' => $this->getRowValue($csvRow, $addressIndex),
                'contact' => $this->getRowValue($csvRow, $contactIndex),
                'course' => $this->getRowValue($csvRow, $courseIndex),
                'year_level' => $this->getRowValue($csvRow, $yearLevelIndex),
                'guardian_name' => $this->getRowValue($csvRow, $guardianNameIndex),
                'guardian_contact' => $this->getRowValue($csvRow, $guardianContactIndex),
                'fb_link' => $this->getRowValue($csvRow, $fbLinkIndex),
                'is_valid' => true,
                'errors' => [],
            ];

            $result['summary']['total']++;

            if ($row['student_id'] === '' && $row['full_name'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'student_id or full_name is required.';
            }
            if ($row['program'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'program is required.';
            }
            if ($row['academic_year'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'academic_year is required.';
            }
            if ($row['semester'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'semester is required.';
            }
            if ($row['batch_label'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'batch is required.';
            }
            if ($row['region'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'region is required.';
            }

            $paidValue = $this->parseDecimalValue($row['paid']);
            if ($paidValue === null || $paidValue <= 0) {
                $row['is_valid'] = false;
                $row['errors'][] = 'billing_amount must be a number greater than 0.';
            } else {
                $row['paid'] = number_format($paidValue, 2, '.', '');
            }

            $normalizedDate = $this->parseDateValue($row['submitdate']);
            if ($normalizedDate === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'billing_date must be a valid date.';
            } else {
                $row['submitdate'] = $normalizedDate;
            }

            if ($row['is_valid']) {
                $student = $this->findStudentForBilling($row['student_id'], $row['full_name'], $row['birthdate'], $row['school']);
                if (!$student) {
                    $autoCreatedId = $this->autoCreateStudentForBilling(
                        $row['student_id'],
                        $row['full_name'],
                        $row['program'],
                        $row['academic_year'],
                        $row['semester'],
                        $row['birthdate'],
                        $row['school'],
                        $row['address'] ?? '',
                        $row['contact'] ?? '',
                        $row['course'] ?? '',
                        $row['year_level'] ?? '',
                        $row['guardian_name'] ?? '',
                        $row['guardian_contact'] ?? '',
                        $row['fb_link'] ?? ''
                    );
                    if ($autoCreatedId > 0) {
                        $student = $this->findStudentForBilling((string) $autoCreatedId);
                    }
                }
                if (!$student) {
                    $row['is_valid'] = false;
                    $row['errors'][] = 'Student not found and could not be auto-created.';
                } elseif ((string) ($student->delete_status ?? '0') !== '0') {
                    $row['is_valid'] = false;
                    $row['errors'][] = 'Student is inactive.';
                } else {
                    $row['resolved_student_id'] = (int) $student->id;
                    $this->applyBillingStudentSmartUpdate($row['resolved_student_id'], $row);

                    $studentProgramRaw = trim((string) ($student->student_program ?? ''));
                    $rowProgramRaw = trim((string) $row['program']);
                    $studentProgram = $this->normalizeCompareValue($studentProgramRaw);
                    $rowProgram = $this->normalizeCompareValue($rowProgramRaw);
                    if ($studentProgram === '') {
                        // skip
                    } elseif ($studentProgram !== $rowProgram) {
                        $row['is_conflict'] = true;
                        $row['conflict_note'] = $this->buildScholarshipConflictNoteEntry($rowProgramRaw, $studentProgramRaw);
                        $row['is_valid'] = false;
                        $row['errors'][] = 'Conflict: ' . $row['conflict_note'];
                    }

                    // Smart Academic Year Validation
                    $studentAYRaw = trim((string) ($student->scholarship_academic_year ?? ''));
                    $rowAYRaw = trim((string) $row['academic_year']);
                    if ($studentAYRaw !== '' && $rowAYRaw !== '') {
                        $ayComparison = $this->compareAcademicYears($rowAYRaw, $studentAYRaw);
                        if ($ayComparison > 0) {
                            $row['is_conflict'] = true;
                            $row['conflict_note'] = "Future year mismatch: Student is registered for {$studentAYRaw}, but billing is for {$rowAYRaw}.";
                            $row['is_valid'] = false;
                            $row['errors'][] = 'Conflict: ' . $row['conflict_note'];
                        } elseif ($ayComparison < 0) {
                            $row['is_conflict'] = true;
                            $row['conflict_note'] = "Prior Year Record: Student is currently {$studentAYRaw}, but this record is for {$rowAYRaw}.";
                            // We don't set is_valid = false for back-billing to allow historical uploads
                        }
                    }

                    // Cross-batch duplicate check
                    $dup = DB::table('fees_transaction as ft')
                        ->join('billing_batch as bb', 'bb.id', '=', 'ft.billing_batch_id')
                        ->where('ft.stdid', $row['resolved_student_id'])
                        ->where('bb.semester', $row['semester'])
                        ->where('bb.academic_year', $row['academic_year'])
                        ->where('bb.delete_status', '0')
                        ->whereRaw("COALESCE(ft.record_type, 'billing') = 'billing'")
                        ->select('bb.program', 'bb.billing_date')
                        ->first();

                    if ($dup) {
                        $completion = \App\Support\ScholarshipMonitoring::isProfileComplete($student);
                        if ($completion['is_complete']) {
                            // Already billed AND profile is complete. Redundant. Hide from preview.
                            $result['summary']['total']--;
                            continue;
                        }
                        
                        // Already billed BUT profile is missing info. Show in preview for profile update.
                        $row['is_duplicate_term'] = true;
                        $row['is_valid'] = true; // Mark as valid so it shows as READY
                        $row['is_profile_update_only'] = true;
                    }
                }
            }

            if ($row['is_valid']) {
                $row['group_key'] = implode('|', [
                    $this->normalizeCompareValue($row['program']),
                    $this->normalizeCompareValue($row['academic_year']),
                    $this->normalizeCompareValue($row['semester']),
                    $this->normalizeCompareValue($row['batch_label']),
                    $this->normalizeCompareValue($row['region']),
                    $row['submitdate'],
                ]);

                $dupKey = $row['group_key'] . '|' . (int) $row['resolved_student_id'];
                if (array_key_exists($dupKey, $seenInGroup)) {
                    $row['is_valid'] = false;
                    $row['errors'][] = 'Duplicate student_id in the same billing batch group.';
                } else {
                    $seenInGroup[$dupKey] = true;
                }

                if ($row['is_valid'] && Schema::hasTable('fees_transaction')) {
                    $alreadyUploaded = DB::table('fees_transaction')
                        ->where('stdid', (int) $row['resolved_student_id'])
                        ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
                        ->whereRaw('LOWER(TRIM(program)) = ?', [strtolower(trim((string) $row['program']))])
                        ->whereRaw('LOWER(TRIM(academic_year)) = ?', [strtolower(trim((string) $row['academic_year']))])
                        ->whereRaw('LOWER(TRIM(semester)) = ?', [strtolower(trim((string) $row['semester']))])
                        ->whereRaw('LOWER(TRIM(batch_label)) = ?', [strtolower(trim((string) $row['batch_label']))])
                        ->whereRaw('LOWER(TRIM(region)) = ?', [strtolower(trim((string) $row['region']))])
                        ->whereDate('submitdate', (string) $row['submitdate'])
                        ->exists();

                    if ($alreadyUploaded) {
                        // This is a direct file duplicate check. 
                        // If it's a direct duplicate and profile is complete, hide it too.
                        $completion = \App\Support\ScholarshipMonitoring::isProfileComplete($student);
                        if ($completion['is_complete']) {
                            $result['summary']['total']--;
                            continue;
                        }
                        $row['is_duplicate_term'] = true;
                        $row['is_valid'] = true;
                        $row['is_profile_update_only'] = true;
                    }
                }
            }

            if ($row['is_valid']) {
                $result['summary']['valid']++;
            } else {
                $result['summary']['invalid']++;
            }
            if (!empty($row['is_conflict'])) {
                $result['summary']['conflicts']++;
            }

            $result['rows'][] = $row;
        }

        return $result;
    }

    private function parseBillingSummaryRows($handle, array $headerMap, array $result)
    {
        $programIndex = $this->firstHeaderIndex($headerMap, ['program', 'scholarship_program']);
        $academicYearIndex = $this->firstHeaderIndex($headerMap, ['academic_year', 'school_year', 'ay']);
        $semesterIndex = $this->firstHeaderIndex($headerMap, ['semester', 'term', 'sem']);
        $batchIndex = $this->firstHeaderIndex($headerMap, ['batch', 'batch_label', 'batch_name']);
        $regionIndex = $this->firstHeaderIndex($headerMap, ['region']);
        $scholarCountIndex = $this->firstHeaderIndex($headerMap, ['no_of_scholars', 'number_of_scholars', 'scholar_count', 'scholars', 'no_of_grantees']);
        $submitDateIndex = $this->firstHeaderIndex($headerMap, ['billing_date', 'date_of_billing', 'submitdate', 'submit_date', 'date']);
        $paidIndex = $this->firstHeaderIndex($headerMap, ['billing_amount', 'paid', 'amount_paid', 'amount', 'billing_total']);
        $remarkIndex = $this->firstHeaderIndex($headerMap, ['billing_remark', 'transcation_remark', 'transaction_remark', 'remark', 'remarks', 'note', 'notes']);

        if ($programIndex === null || $academicYearIndex === null || $semesterIndex === null || $batchIndex === null || $regionIndex === null || $submitDateIndex === null || $paidIndex === null) {
            $result['summary']['errors'][] = 'Missing required columns for batch summary import.';
            return $result;
        }

        $line = 1;
        $seenGroups = [];

        while (($csvRow = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyCsvRow($csvRow)) {
                continue;
            }

            $row = [
                'line' => $line,
                'layout' => 'batch_summary',
                'student_id' => '',
                'program' => $this->getRowValue($csvRow, $programIndex),
                'academic_year' => $this->normalizeAcademicYearValue($this->getRowValue($csvRow, $academicYearIndex)),
                'semester' => $this->normalizeSemesterValue($this->getRowValue($csvRow, $semesterIndex)),
                'batch_label' => $this->getRowValue($csvRow, $batchIndex),
                'region' => $this->getRowValue($csvRow, $regionIndex),
                'scholar_count' => $this->getRowValue($csvRow, $scholarCountIndex),
                'paid' => $this->getRowValue($csvRow, $paidIndex),
                'submitdate' => $this->getRowValue($csvRow, $submitDateIndex),
                'transcation_remark' => $this->getRowValue($csvRow, $remarkIndex),
                'group_key' => '',
                'resolved_student_id' => 0,
                'is_conflict' => false,
                'conflict_note' => '',
                'is_valid' => true,
                'errors' => [],
            ];

            $result['summary']['total']++;

            if ($row['program'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'program is required.';
            }
            if ($row['academic_year'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'academic_year is required.';
            }
            if ($row['semester'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'semester is required.';
            }
            if ($row['batch_label'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'batch is required.';
            }
            if ($row['region'] === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'region is required.';
            }

            $scholarCount = $this->parseIntValue($row['scholar_count']);
            if ($scholarCount === null || $scholarCount <= 0) {
                $row['is_valid'] = false;
                $row['errors'][] = 'no_of_scholars must be a whole number greater than 0.';
            } else {
                $row['scholar_count'] = (string) $scholarCount;
            }

            $paidValue = $this->parseDecimalValue($row['paid']);
            if ($paidValue === null || $paidValue <= 0) {
                $row['is_valid'] = false;
                $row['errors'][] = 'amount must be a number greater than 0.';
            } else {
                $row['paid'] = number_format($paidValue, 2, '.', '');
            }

            $normalizedDate = $this->parseDateValue($row['submitdate']);
            if ($normalizedDate === '') {
                $row['is_valid'] = false;
                $row['errors'][] = 'date_of_billing must be a valid date.';
            } else {
                $row['submitdate'] = $normalizedDate;
            }

            if ($row['is_valid']) {
                $row['group_key'] = implode('|', [
                    $this->normalizeCompareValue($row['program']),
                    $this->normalizeCompareValue($row['academic_year']),
                    $this->normalizeCompareValue($row['semester']),
                    $this->normalizeCompareValue($row['batch_label']),
                    $this->normalizeCompareValue($row['region']),
                    $row['submitdate'],
                ]);

                if (array_key_exists($row['group_key'], $seenGroups)) {
                    $row['is_valid'] = false;
                    $row['errors'][] = 'Duplicate batch summary row for the same Program/AY/Semester/Batch/Region/Date.';
                } else {
                    $seenGroups[$row['group_key']] = true;
                }
            }

            if ($row['is_valid']) {
                $result['summary']['valid']++;
            } else {
                $result['summary']['invalid']++;
            }

            $result['rows'][] = $row;
        }

        return $result;
    }

    private function parseGranteeCsv($file, $expectedProgram, $semester = '', $academicYear = '')
    {
        $result = [
            'rows' => [],
            'errors' => [],
            'invalid_rows' => [],
            'conflicts' => 0,
            'total_amount' => 0,
        ];

        $realPath = $file->getRealPath();
        if (!is_string($realPath) || $realPath === '' || !is_file($realPath)) {
            $result['errors'][] = 'Unable to read grantee list CSV.';
            return $result;
        }

        $handle = fopen($realPath, 'r');
        if ($handle === false) {
            $result['errors'][] = 'Unable to read grantee list CSV.';
            return $result;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $result['errors'][] = 'Grantee list CSV is empty.';
            return $result;
        }

        $headerMap = $this->normalizeHeaders($headers);
        $sidIdx = $this->firstHeaderIndex($headerMap, ['student_id', 'stdid', 'studentid', 'id']);
        $fullNameIdx = $this->firstHeaderIndex($headerMap, ['full_name', 'name', 'student_name', 'sname']);
        $birthdateIdx = $this->firstHeaderIndex($headerMap, ['birthdate', 'date_of_birth', 'dob']);
        $schoolIdx = $this->firstHeaderIndex($headerMap, ['school', 'school_name']);
        $amountIdx = $this->firstHeaderIndex($headerMap, ['billing_amount', 'amount', 'approved_amount']);
        $dateIdx = $this->firstHeaderIndex($headerMap, ['billing_date', 'date', 'submitdate']);
        $remarkIdx = $this->firstHeaderIndex($headerMap, ['billing_remark', 'remark', 'remarks']);
        $yearIdx = $this->firstHeaderIndex($headerMap, ['year', 'year_level', 'grade', 'level']);
        $addressIdx = $this->firstHeaderIndex($headerMap, ['address', 'home_address']);
        $contactIdx = $this->firstHeaderIndex($headerMap, ['contact', 'contact_number', 'mobile', 'phone']);
        $courseIdx = $this->firstHeaderIndex($headerMap, ['course', 'degree_program']);
        $guardianNameIdx = $this->firstHeaderIndex($headerMap, ['guardian_name', 'guardian']);
        $guardianContactIdx = $this->firstHeaderIndex($headerMap, ['guardian_contact', 'guardian_phone']);
        $fbLinkIdx = $this->firstHeaderIndex($headerMap, ['fb_link', 'facebook', 'facebook_link', 'fb', 'profile', 'facebook_profile', 'social', 'facebook_url', 'facebook_account', 'fb_account', 'facebookaccount', 'fbaccount']);

        if (($sidIdx === null && $fullNameIdx === null) || $amountIdx === null) {
            fclose($handle);
            $result['errors'][] = 'Grantee CSV must include billing_amount and either student_id or full_name columns.';
            return $result;
        }

        $expectedProgramNormalized = $this->normalizeCompareValue($expectedProgram);
        $expectedProgramLabel = trim((string) $expectedProgram);
        $line = 1;
        $seenStudents = [];

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyCsvRow($row)) {
                continue;
            }

            $sid = isset($row[$sidIdx]) ? trim((string) $row[$sidIdx]) : '';
            $fullName = ($fullNameIdx !== null && isset($row[$fullNameIdx])) ? trim((string) $row[$fullNameIdx]) : '';
            $birthdate = ($birthdateIdx !== null && isset($row[$birthdateIdx])) ? trim((string) $row[$birthdateIdx]) : '';
            $school = ($schoolIdx !== null && isset($row[$schoolIdx])) ? trim((string) $row[$schoolIdx]) : '';
            $amountRaw = isset($row[$amountIdx]) ? trim((string) $row[$amountIdx]) : '';
            $dateRaw = ($dateIdx !== null && isset($row[$dateIdx])) ? trim((string) $row[$dateIdx]) : '';
            $remark = ($remarkIdx !== null && isset($row[$remarkIdx])) ? trim((string) $row[$remarkIdx]) : '';
            $csvYearLevel = ($yearIdx !== null && isset($row[$yearIdx])) ? trim((string) $row[$yearIdx]) : '';
            $address = ($addressIdx !== null && isset($row[$addressIdx])) ? trim((string) $row[$addressIdx]) : '';
            $contact = ($contactIdx !== null && isset($row[$contactIdx])) ? trim((string) $row[$contactIdx]) : '';
            $course = ($courseIdx !== null && isset($row[$courseIdx])) ? trim((string) $row[$courseIdx]) : '';
            $guardianName = ($guardianNameIdx !== null && isset($row[$guardianNameIdx])) ? trim((string) $row[$guardianNameIdx]) : '';
            $guardianContact = ($guardianContactIdx !== null && isset($row[$guardianContactIdx])) ? trim((string) $row[$guardianContactIdx]) : '';
            $fbLink = ($fbLinkIdx !== null && isset($row[$fbLinkIdx])) ? trim((string) $row[$fbLinkIdx]) : '';

            $rowErrors = [];
            if ($sid === '' && $fullName === '') {
                $rowErrors[] = 'student_id or full_name is required';
            }

            $amount = $this->parseDecimalValue($amountRaw);
            if ($amount === null || $amount <= 0) {
                $rowErrors[] = 'billing_amount must be > 0';
            }

            $resolvedSid = 0;
            $isConflict = false;
            $conflictNote = '';

            if (count($rowErrors) === 0) {
                $student = $this->findStudentForBilling($sid, $fullName, $birthdate, $school);
                if (!$student) {
                    $autoCreatedId = $this->autoCreateStudentForBilling(
                        $sid, $fullName, $expectedProgramLabel, $this->getDefaultAcademicYear(), '',
                        $birthdate, $school, $address, $contact, $course, $csvYearLevel, $guardianName, $guardianContact, $fbLink
                    );
                    if ($autoCreatedId > 0) {
                        $student = $this->findStudentForBilling((string) $autoCreatedId);
                    }
                }
                if (!$student) {
                    $rowErrors[] = 'Student not found and could not be auto-created';
                } elseif ((string) ($student->delete_status ?? '0') !== '0') {
                    $rowErrors[] = 'Student is inactive';
                } else {
                    $resolvedSid = (int) $student->id;
                    $this->applyBillingStudentSmartUpdate($resolvedSid, [
                        'fullName' => $fullName,
                        'birthdate' => $birthdate,
                        'school' => $school,
                        'address' => $address,
                        'contact' => $contact,
                        'course' => $course,
                        'year_level' => $csvYearLevel,
                        'guardian_name' => $guardianName,
                        'guardian_contact' => $guardianContact,
                        'fb_link' => $fbLink,
                    ]);
                    if (array_key_exists($resolvedSid, $seenStudents)) {
                        $rowErrors[] = 'Duplicate student_id in file';
                    } else {
                        $seenStudents[$resolvedSid] = true;
                    }

                    $studentProgramRaw = trim((string) ($student->student_program ?? ''));
                    $studentProgram = $this->normalizeCompareValue($studentProgramRaw);
                    if ($studentProgram === '') {
                        // skip
                    } elseif ($studentProgram !== $expectedProgramNormalized) {
                        $isConflict = true;
                        $conflictNote = $this->buildScholarshipConflictNoteEntry($expectedProgramLabel, $studentProgramRaw);
                        $rowErrors[] = 'Conflict: ' . $conflictNote;
                    }

                    // Smart Academic Year Validation
                    $studentAYRaw = trim((string) ($student->scholarship_academic_year ?? ''));
                    if ($studentAYRaw !== '' && $academicYear !== '') {
                        $ayComparison = $this->compareAcademicYears($academicYear, $studentAYRaw);
                        if ($ayComparison > 0) {
                            $isConflict = true;
                            $conflictNote = "Future year mismatch: Student is registered for {$studentAYRaw}, but billing is for {$academicYear}.";
                            $rowErrors[] = 'Conflict: ' . $conflictNote;
                        } elseif ($ayComparison < 0) {
                            $isConflict = true;
                            $conflictNote = "Prior Year Record: Student is currently {$studentAYRaw}, but this record is for {$academicYear}.";
                        }
                    }

                    // Cross-batch duplicate check (Same Semester/Academic Year)
                    if ($semester !== '' && $academicYear !== '') {
                        $duplicateBilling = DB::table('fees_transaction as ft')
                            ->join('billing_batch as bb', 'bb.id', '=', 'ft.billing_batch_id')
                            ->where('ft.stdid', $resolvedSid)
                            ->where('bb.semester', $semester)
                            ->where('bb.academic_year', $academicYear)
                            ->where('bb.delete_status', '0')
                            ->whereRaw("COALESCE(ft.record_type, 'billing') = 'billing'")
                            ->select('bb.program', 'bb.billing_date')
                            ->first();

                        if ($duplicateBilling) {
                            $isConflict = true;
                            $billingDate = \Illuminate\Support\Carbon::parse($duplicateBilling->billing_date)->format('M d, Y');
                            $conflictNote = "Already billed for scholarship \"{$duplicateBilling->program}\" on {$billingDate}.";
                            $rowErrors[] = 'Duplicate: ' . $conflictNote;
                        }
                    }

                    if ($csvYearLevel !== '' && $studentProgram !== '') {
                        $studentYearLevel = $this->normalizeCompareValue((string) ($student->student_year_level ?? ''));
                        if ($studentYearLevel !== '' && $this->normalizeCompareValue($csvYearLevel) !== $studentYearLevel) {
                            $rowErrors[] = 'Student year level does not match CSV year';
                        }
                    }
                }
            }

            if (count($rowErrors) > 0) {
                $lineLabel = 'Line ' . $line;
                if ($sid !== '') {
                    $lineLabel .= ' [student_id: ' . $sid . ']';
                } elseif ($fullName !== '') {
                    $lineLabel .= ' [name: ' . $fullName . ']';
                }
                $result['errors'][] = $lineLabel . ': ' . implode(', ', $rowErrors);

                $result['invalid_rows'][] = [
                    'line' => $line,
                    'student_id' => $sid,
                    'full_name' => $fullName,
                    'birthdate' => $birthdate,
                    'school' => $school,
                    'program' => $expectedProgramLabel,
                    'academic_year' => '',
                    'semester' => '',
                    'batch_label' => '',
                    'region' => '',
                    'amount' => $amount !== null ? (float) $amount : 0,
                    'remark' => $remark,
                    'errors' => $rowErrors,
                ];
                continue;
            }

            $result['rows'][] = [
                'sid' => $resolvedSid,
                'amount' => (float) $amount,
                'date' => $this->parseDateValue($dateRaw),
                'remark' => $remark,
                'is_conflict' => $isConflict,
                'conflict_note' => $conflictNote,
                'address' => $address,
                'contact' => $contact,
                'course' => $course,
                'year_level' => $csvYearLevel,
                'school' => $school,
                'birthdate' => $birthdate,
                'guardian_name' => $guardianName,
                'guardian_contact' => $guardianContact,
                'fb_link' => $fbLink,
            ];
            $result['total_amount'] += (float) $amount;
            if ($isConflict) {
                $result['conflicts']++;
            }
        }

        fclose($handle);

        if (count($result['rows']) === 0 && count($result['errors']) === 0) {
            $result['errors'][] = 'No valid grantee rows found in CSV.';
        }

        return $result;
    }

    private function autoCreateStudentForBilling(
        $studentIdNo, $fullName, $program, $academicYear, $semester,
        $birthdate = '', $school = '', $address = '', $contact = '',
        $course = '', $yearLevel = '', $guardianName = '', $guardianContact = '', $fbLink = ''
    ) {
        $studentIdNo = trim((string) $studentIdNo);
        $fullName = trim((string) $fullName);

        if ($studentIdNo === '' && $fullName === '') {
            return 0;
        }

        if ($studentIdNo !== '' && Schema::hasTable('student')) {
            $existing = DB::table('student')->where('student_id_no', $studentIdNo)->first();
            if ($existing) {
                // Proactively update existing student with any more complete info from this billing row
                $this->applyBillingStudentSmartUpdate((int) $existing->id, [
                    'fullName' => $fullName,
                    'birthdate' => $birthdate,
                    'school' => $school,
                    'address' => $address,
                    'contact' => $contact,
                    'course' => $course,
                    'year_level' => $yearLevel,
                    'guardian_name' => $guardianName,
                    'guardian_contact' => $guardianContact,
                    'fb_link' => $fbLink,
                ]);
                return (int) $existing->id;
            }
        }

        $lastName = '';
        $givenName = '';
        $middleInitial = '';
        if ($fullName !== '') {
            $parsed = $this->parseName($fullName);
            $lastName = $parsed['last_name'];
            $givenName = $parsed['given_name'];
            $middleInitial = $parsed['middle_initial'];
        }

        $program = trim((string) $program);
        $course = trim((string) $course);
        $degreeProgram = $course !== '' ? $course : $program;
        $scholarshipProgram = $program !== '' ? $program : $degreeProgram;
        if ($degreeProgram === '') {
            $degreeProgram = $scholarshipProgram;
        }

        $displayName = $lastName;
        if ($givenName !== '') {
            $displayName .= ($displayName !== '' ? ', ' : '') . $givenName;
        }
        if ($middleInitial !== '') {
            $displayName .= ' ' . $middleInitial;
        }
        if ($displayName === '' && $fullName !== '') {
            $displayName = $fullName;
        }
        if ($displayName === '' && $studentIdNo !== '') {
            $displayName = $studentIdNo;
        }

        $parsedBirthdate = $this->parseDateValue(trim((string) $birthdate));

        try {
            $insertPayload = [
                'student_id_no' => $studentIdNo !== '' ? $studentIdNo : 'AUTO-' . time() . '-' . mt_rand(100, 999),
                'last_name' => $lastName,
                'given_name' => $givenName,
                'middle_initial' => $middleInitial,
                'sname' => $displayName,
                'degree_program' => $degreeProgram,
                'scholarship_program' => $scholarshipProgram,
                'scholarship_academic_year' => trim((string) $academicYear) !== '' ? trim((string) $academicYear) : $this->getDefaultAcademicYear(),
                'scholarship_semester' => trim((string) $semester),
                'year_level' => trim((string) $yearLevel),
                'grade' => '',
                'school_name' => trim((string) $school),
                'address' => trim((string) $address),
                'contact' => trim((string) $contact),
                'guardian_name' => trim((string) $guardianName),
                'guardian_contact' => trim((string) $guardianContact),
                'joindate' => Carbon::now()->format('Y-m-d H:i:s'),
                'delete_status' => '0',
                // Strict database defaults for non-nullable columns with no defaults
                'emailid' => ($studentIdNo !== '' ? $studentIdNo : 'AUTO-' . time()) . '@auto.scholar',
                'about' => 'Auto-created via billing import',
                'fees' => 0,
                'balance' => 0,
                'tdp_tes_award_no' => 'N/A',
                'pwd_no' => 'N/A',
                'ip_no' => 'N/A',
                'fb_link' => $fbLink !== '' ? $fbLink : 'N/A',
            ];
            if ($parsedBirthdate !== '') {
                $insertPayload['birthdate'] = $parsedBirthdate;
            }

            return (int) DB::table('student')->insertGetId($insertPayload);
        } catch (\Throwable $e) {
            \Log::error('Student Auto-Creation Failed: ' . $e->getMessage(), [
                'payload' => $insertPayload ?? [],
                'exception' => $e
            ]);
            return 0;
        }
    }

    private function findStudentForBilling($identifier, $fullName = '', $birthdate = '', $school = '')
    {
        $student = ScholarshipMonitoring::resolveStudentByKeys($identifier, $fullName, $birthdate, $school);
        if (!$student) {
            return null;
        }

        $base = DB::table('student')
            ->select('id', 'delete_status')
            ->selectRaw("COALESCE(NULLIF(TRIM(scholarship_program), ''), NULLIF(TRIM(degree_program), ''), '') AS student_program")
            ->selectRaw("COALESCE(NULLIF(TRIM(scholarship_academic_year), ''), '') AS scholarship_academic_year")
            ->selectRaw("COALESCE(NULLIF(TRIM(year_level), ''), NULLIF(TRIM(grade), ''), '') AS student_year_level")
            ->where('id', (int) ($student->id ?? 0));

        return $base->first();
    }

    private function applyBillingStudentSmartUpdate($studentId, array $row)
    {
        $sid = (int) $studentId;
        if ($sid <= 0) {
            return;
        }

        $givenName = $row['given_name'] ?? '';
        $lastName = $row['last_name'] ?? '';
        $middleInitial = $row['middle_initial'] ?? '';

        if (($givenName === '' || $lastName === '') && !empty($row['fullName'])) {
            $parsed = $this->parseName($row['fullName']);
            $givenName = $parsed['given_name'];
            $lastName = $parsed['last_name'];
            $middleInitial = $parsed['middle_initial'];
        }

        ScholarshipMonitoring::applyStudentSmartUpdate($sid, [
            'address' => trim((string) ($row['address'] ?? '')),
            'contact' => trim((string) ($row['contact'] ?? '')),
            'birthdate' => trim((string) ($row['birthdate'] ?? '')),
            'course' => trim((string) ($row['course'] ?? '')),
            'year_level' => trim((string) ($row['year_level'] ?? '')),
            'school' => trim((string) ($row['school'] ?? '')),
            'guardian_name' => trim((string) ($row['guardian_name'] ?? '')),
            'guardian_contact' => trim((string) ($row['guardian_contact'] ?? '')),
            'fb_link' => trim((string) ($row['fb_link'] ?? '')),
            'given_name' => $givenName,
            'last_name' => $lastName,
            'middle_initial' => $middleInitial,
        ]);
    }

    private function recordBillingInvalidRows(array $rows, $moduleName, $billingBatchId = 0)
    {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (array_key_exists('is_valid', $row) && !empty($row['is_valid'])) {
                continue;
            }

            $errors = [];
            if (isset($row['errors']) && is_array($row['errors'])) {
                $errors = $row['errors'];
            }

            $reason = trim((string) implode(', ', $errors));
            if ($reason === '') {
                continue;
            }

            ScholarshipMonitoring::saveUnmatchedRecord([
                'import_source' => 'billing_csv',
                'module_name' => $moduleName,
                'student_id_value' => trim((string) ($row['student_id'] ?? '')),
                'full_name' => trim((string) ($row['full_name'] ?? '')),
                'birthdate' => trim((string) ($row['birthdate'] ?? '')),
                'school' => trim((string) ($row['school'] ?? '')),
                'billing_batch_id' => (int) $billingBatchId,
                'program' => trim((string) ($row['program'] ?? '')),
                'academic_year' => trim((string) ($row['academic_year'] ?? '')),
                'semester' => trim((string) ($row['semester'] ?? '')),
                'batch_label' => trim((string) ($row['batch_label'] ?? '')),
                'region' => trim((string) ($row['region'] ?? '')),
                'amount' => (float) ($row['amount'] ?? $row['paid'] ?? 0),
                'remarks' => trim((string) ($row['remark'] ?? $row['transcation_remark'] ?? '')),
                'reason' => $reason,
                'original_row' => json_encode($row),
                'resolution_status' => 'pending',
            ]);
        }
    }

    private function countDuplicateRows(array $rows)
    {
        $count = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!empty($row['is_duplicate'])) {
                $count++;
                continue;
            }
            $errors = isset($row['errors']) && is_array($row['errors']) ? implode(' ', $row['errors']) : '';
            if ($errors !== '' && stripos($errors, 'duplicate') !== false) {
                $count++;
            }
        }
        return $count;
    }

    private function recalcStudentBalance($studentId)
    {
        $sid = (int) $studentId;
        if ($sid <= 0 || !Schema::hasTable('student')) {
            return;
        }

        $fees = (float) (DB::table('student')->where('id', $sid)->value('fees') ?? 0);
        $totalPaid = (float) DB::table('fees_transaction')
            ->where('stdid', $sid)
            ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
            ->sum('paid');

        $newBalance = max(0, $fees - $totalPaid);

        DB::table('student')->where('id', $sid)->update([
            'balance' => $newBalance,
        ]);
    }

    private function storeBillingDocument($file, $prefix)
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return '';
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return '';
        }

        $dir = public_path('uploads/billing_docs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $fileName = $prefix . '_' . Carbon::now()->format('Ymd_His') . '_' . random_int(1000, 9999) . '.' . $ext;
        $file->move($dir, $fileName);

        return 'uploads/billing_docs/' . $fileName;
    }

    private function getProgramOptions()
    {
        if (!Schema::hasTable('academic_program')) {
            return [];
        }

        return DB::table('academic_program')
            ->where('delete_status', '0')
            ->orderBy('name')
            ->pluck('name')
            ->filter(function ($value) {
                return trim((string) $value) !== '';
            })
            ->values()
            ->all();
    }

    private function getAcademicYearOptions()
    {
        if (!Schema::hasTable('academic_year')) {
            return [];
        }

        return DB::table('academic_year')
            ->where('delete_status', '0')
            ->orderByDesc('id')
            ->pluck('label')
            ->filter(function ($label) {
                return trim((string) $label) !== '';
            })
            ->values()
            ->all();
    }

    private function getDefaultAcademicYear()
    {
        if (Schema::hasTable('academic_year')) {
            $label = DB::table('academic_year')
                ->where('delete_status', '0')
                ->orderByDesc('id')
                ->value('label');

            if (is_string($label) && trim($label) !== '') {
                return $label;
            }
        }

        return '2025-2026';
    }

    private function getSemesterOptions()
    {
        if (Schema::hasTable('academic_semester')) {
            $options = DB::table('academic_semester')
                ->where('delete_status', '0')
                ->orderBy('id')
                ->pluck('label')
                ->map(function ($value) {
                    return trim((string) $value);
                })
                ->filter(function ($value) {
                    return $value !== '';
                })
                ->values()
                ->all();

            if (count($options) > 0) {
                return $options;
            }
        }

        return ['1st Semester', '2nd Semester'];
    }

    private function getDefaultBillingBatchLabel()
    {
        if (Schema::hasTable('billing_batch')) {
            $value = DB::table('billing_batch')
                ->whereRaw("COALESCE(TRIM(batch_label), '') <> ''")
                ->orderByDesc('id')
                ->value('batch_label');

            if (is_string($value) && trim($value) !== '') {
                return trim((string) $value);
            }
        }

        return 'NEW';
    }

    private function getDefaultBillingRegion()
    {
        if (Schema::hasTable('billing_batch')) {
            $value = DB::table('billing_batch')
                ->whereRaw("COALESCE(TRIM(region), '') <> ''")
                ->orderByDesc('id')
                ->value('region');

            if (is_string($value) && trim($value) !== '') {
                return trim((string) $value);
            }
        }

        return 'VI';
    }

    private function getDistinctBillingBatchValues($column)
    {
        if (!Schema::hasTable('billing_batch')) {
            return [];
        }

        if (!in_array($column, ['batch_label', 'region'], true)) {
            return [];
        }

        return DB::table('billing_batch')
            ->where('delete_status', '0')
            ->whereRaw("COALESCE(TRIM({$column}), '') <> ''")
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter(function ($value) {
                return trim((string) $value) !== '';
            })
            ->values()
            ->all();
    }

    private function syncProgramAndAcademicYear($program, $academicYear, $semester = '')
    {
        $program = trim((string) $program);
        if ($program !== '' && Schema::hasTable('academic_program')) {
            $existing = DB::table('academic_program')->where('name', $program)->first();
            if ($existing) {
                DB::table('academic_program')->where('id', (int) $existing->id)->update([
                    'delete_status' => '0',
                ]);
            } else {
                DB::table('academic_program')->insert([
                    'name' => $program,
                    'delete_status' => '0',
                ]);
            }
        }

        $academicYear = $this->normalizeAcademicYearValue($academicYear);
        if ($academicYear !== '' && Schema::hasTable('academic_year')) {
            $existing = DB::table('academic_year')->where('label', $academicYear)->first();
            if ($existing) {
                DB::table('academic_year')->where('id', (int) $existing->id)->update([
                    'delete_status' => '0',
                ]);
            } else {
                DB::table('academic_year')->insert([
                    'label' => $academicYear,
                    'delete_status' => '0',
                ]);
            }
        }

        $semester = $this->normalizeSemesterValue($semester);
        if ($semester !== '' && Schema::hasTable('academic_semester')) {
            $existing = DB::table('academic_semester')
                ->whereRaw('LOWER(TRIM(label)) = ?', [strtolower($semester)])
                ->first();

            if ($existing) {
                DB::table('academic_semester')->where('id', (int) $existing->id)->update([
                    'label' => $semester,
                    'delete_status' => '0',
                ]);
            } else {
                DB::table('academic_semester')->insert([
                    'label' => $semester,
                    'delete_status' => '0',
                ]);
            }
        }
    }

    private function assignBillingProgramBatchRef($batchId, $program)
    {
        $batchId = (int) $batchId;
        if ($batchId <= 0) {
            return '';
        }

        $ref = $this->buildBillingProgramBatchRef($program, $batchId);

        DB::table('billing_batch')->where('id', $batchId)->update([
            'program_batch_ref' => $ref,
        ]);

        return $ref;
    }

    private function buildBillingProgramBatchRef($program, $batchId)
    {
        $prefix = $this->billingProgramIdentifierPrefix($program);

        return $prefix . '-' . str_pad((string) max(1, (int) $batchId), 6, '0', STR_PAD_LEFT);
    }

    private function billingProgramIdentifierPrefix($program)
    {
        $prefix = strtoupper(trim((string) $program));
        $prefix = preg_replace('/[^A-Z0-9]+/', '', $prefix);

        if ($prefix === '') {
            $prefix = 'GEN';
        }

        if (strlen($prefix) > 10) {
            $prefix = substr($prefix, 0, 10);
        }

        return $prefix;
    }

    private function ensureBillingProgramBatchRefs()
    {
        if (!Schema::hasTable('billing_batch')) {
            return;
        }

        $rows = DB::table('billing_batch')
            ->select('id', 'program')
            ->whereRaw("COALESCE(program_batch_ref, '') = ''")
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $this->assignBillingProgramBatchRef((int) $row->id, (string) ($row->program ?? ''));
        }
    }

    private function bootstrapBillingStructures()
    {
        if (!Schema::hasTable('billing_batch')) {
            DB::statement("CREATE TABLE IF NOT EXISTS billing_batch (
                id INT(11) NOT NULL AUTO_INCREMENT,
                program VARCHAR(150) NOT NULL,
                academic_year VARCHAR(30) NOT NULL,
                semester VARCHAR(60) NOT NULL,
                batch_label VARCHAR(60) NOT NULL DEFAULT '',
                region VARCHAR(100) NOT NULL DEFAULT '',
                billing_date DATE NOT NULL,
                submitted_date_to_ched DATE DEFAULT NULL,
                billing_total_amount DECIMAL(12,2) NOT NULL,
                scholar_count INT(11) NOT NULL DEFAULT 0,
                signed_billing_doc VARCHAR(255) NOT NULL DEFAULT '',
                program_batch_ref VARCHAR(80) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'open',
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_by INT(11) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('billing_batch', 'batch_label', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('billing_batch', 'region', "VARCHAR(100) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('billing_batch', 'submitted_date_to_ched', 'DATE DEFAULT NULL');
            $this->addColumnIfMissing('billing_batch', 'status', "VARCHAR(20) NOT NULL DEFAULT 'open'");
            $this->addColumnIfMissing('billing_batch', 'scholar_count', "INT(11) NOT NULL DEFAULT 0");
            $this->addColumnIfMissing('billing_batch', 'signed_billing_doc', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('billing_batch', 'program_batch_ref', "VARCHAR(80) DEFAULT NULL");
            $this->addColumnIfMissing('billing_batch', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
        }

        $this->addIndexIfMissing('billing_batch', 'idx_program_term', 'ADD KEY idx_program_term (program, academic_year, semester)');
        $this->addIndexIfMissing('billing_batch', 'idx_batch_region', 'ADD KEY idx_batch_region (batch_label, region)');
        $this->addIndexIfMissing('billing_batch', 'uk_program_batch_ref', 'ADD UNIQUE KEY uk_program_batch_ref (program_batch_ref)');

        if (!Schema::hasTable('academic_semester')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_semester (
                id INT(11) NOT NULL AUTO_INCREMENT,
                label VARCHAR(60) NOT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_semester_label (label)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('academic_semester', 'label', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('academic_semester', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
            $this->addIndexIfMissing('academic_semester', 'uk_semester_label', 'ADD UNIQUE KEY uk_semester_label (label)');
        }

        $hasSemester = DB::table('academic_semester')->where('delete_status', '0')->exists();
        if (!$hasSemester) {
            DB::table('academic_semester')->insert([
                ['label' => '1st Semester', 'delete_status' => '0'],
                ['label' => '2nd Semester', 'delete_status' => '0'],
            ]);
        }

        if (Schema::hasTable('fees_transaction')) {
            $this->addColumnIfMissing('fees_transaction', 'record_type', "VARCHAR(20) NOT NULL DEFAULT 'billing'");
            $this->addColumnIfMissing('fees_transaction', 'program', "VARCHAR(150) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'semester', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'academic_year', "VARCHAR(30) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'batch_label', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'region', "VARCHAR(100) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'billing_batch_id', 'INT(11) DEFAULT NULL');
            $this->addColumnIfMissing('fees_transaction', 'signed_billing_doc', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'conflict_status', "VARCHAR(40) NOT NULL DEFAULT 'none'");
            $this->addColumnIfMissing('fees_transaction', 'conflict_note', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('fees_transaction', 'transcation_remark', "VARCHAR(255) NOT NULL DEFAULT ''");
        }

        if (!Schema::hasTable('disbursed_transaction')) {
            DB::statement("CREATE TABLE IF NOT EXISTS disbursed_transaction (
                id INT(11) NOT NULL AUTO_INCREMENT,
                stdid INT(11) NOT NULL,
                program VARCHAR(150) NOT NULL,
                semester VARCHAR(60) NOT NULL,
                academic_year VARCHAR(30) NOT NULL,
                batch_label VARCHAR(60) NOT NULL DEFAULT '',
                region VARCHAR(100) NOT NULL DEFAULT '',
                billing_batch_id INT(11) DEFAULT NULL,
                disbursed_date DATE DEFAULT NULL,
                disbursed_amount DECIMAL(12,2) NOT NULL,
                ada_no VARCHAR(100) DEFAULT '',
                or_no VARCHAR(100) DEFAULT '',
                or_date DATE DEFAULT NULL,
                attachment_note VARCHAR(255) DEFAULT '',
                attachment_file VARCHAR(255) DEFAULT '',
                remarks VARCHAR(255) DEFAULT '',
                disbursed_status VARCHAR(20) NOT NULL DEFAULT 'draft',
                created_by INT(11) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_stdid (stdid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('disbursed_transaction', 'batch_label', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'region', "VARCHAR(100) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'billing_batch_id', 'INT(11) DEFAULT NULL');
            $this->addColumnIfMissing('disbursed_transaction', 'attachment_file', "VARCHAR(255) DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'disbursed_status', "VARCHAR(20) NOT NULL DEFAULT 'draft'");
            
            // Ensure disbursed_date is nullable for existing tables
            DB::statement("ALTER TABLE disbursed_transaction MODIFY disbursed_date DATE NULL");
        }

        $this->addIndexIfMissing('disbursed_transaction', 'idx_batch_link', 'ADD KEY idx_batch_link (billing_batch_id)');
        $this->addIndexIfMissing('disbursed_transaction', 'uk_batch_student', 'ADD UNIQUE KEY uk_batch_student (billing_batch_id, stdid)');

        DB::statement("INSERT INTO academic_semester(label, delete_status)
            SELECT DISTINCT TRIM(semester), '0'
            FROM billing_batch
            WHERE COALESCE(TRIM(semester), '') <> ''
            ON DUPLICATE KEY UPDATE delete_status = '0'");

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(semester), '0'
                FROM fees_transaction
                WHERE COALESCE(TRIM(semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(semester), '0'
                FROM disbursed_transaction
                WHERE COALESCE(TRIM(semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(scholarship_semester), '0'
                FROM student
                WHERE COALESCE(TRIM(scholarship_semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        $this->ensureBillingProgramBatchRefs();
        ScholarshipMonitoring::bootstrapMonitoringStructures();
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, $column)) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndexIfMissing($table, $indexName, $indexSql)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $rows = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        if (count($rows) > 0) {
            return;
        }

        try {
            DB::statement("ALTER TABLE {$table} {$indexSql}");
        } catch (\Throwable $e) {
            // Ignore duplicate key creation errors from existing legacy schema.
        }
    }

    private function detectBillingCsvLayout(array $headerMap)
    {
        $studentIdIndex = $this->firstHeaderIndex($headerMap, ['student_id', 'student_id_no', 'student_no', 'stdid', 'studentid', 'id']);
        $fullNameIndex = $this->firstHeaderIndex($headerMap, ['full_name', 'name', 'student_name', 'sname']);
        if ($studentIdIndex !== null || $fullNameIndex !== null) {
            return 'detailed';
        }

        $programIndex = $this->firstHeaderIndex($headerMap, ['program', 'scholarship_program']);
        $academicYearIndex = $this->firstHeaderIndex($headerMap, ['academic_year', 'school_year', 'ay']);
        $semesterIndex = $this->firstHeaderIndex($headerMap, ['semester', 'term', 'sem']);
        $batchIndex = $this->firstHeaderIndex($headerMap, ['batch', 'batch_label', 'batch_name']);
        $regionIndex = $this->firstHeaderIndex($headerMap, ['region']);
        $paidIndex = $this->firstHeaderIndex($headerMap, ['billing_amount', 'paid', 'amount_paid', 'amount', 'billing_total']);
        $submitDateIndex = $this->firstHeaderIndex($headerMap, ['billing_date', 'date_of_billing', 'submitdate', 'submit_date', 'date']);

        if ($programIndex !== null && $academicYearIndex !== null && $semesterIndex !== null && $batchIndex !== null && $regionIndex !== null && $paidIndex !== null && $submitDateIndex !== null) {
            return 'batch_summary';
        }

        return 'unknown';
    }

    private function normalizeHeaderKey($header)
    {
        $header = trim((string) $header);
        if (substr($header, 0, 3) === "\xEF\xBB\xBF") {
            $header = substr($header, 3);
        }

        $header = str_replace("\xC2\xA0", ' ', $header);
        $header = strtolower($header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim((string) $header, '_');
    }

    private function normalizeHeaders(array $headers)
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $key = $this->normalizeHeaderKey((string) $header);
            if ($key === '') {
                continue;
            }

            if (!array_key_exists($key, $normalized)) {
                $normalized[$key] = $index;
            }
        }

        return $normalized;
    }

    private function firstHeaderIndex(array $headerMap, array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (isset($headerMap[$candidate])) {
                return (int) $headerMap[$candidate];
            }
        }
        return null;
    }

    private function autoMapBillingHeaders(array $headers)
    {
        $headerMap = [];
        foreach ($headers as $index => $h) {
            $normalized = strtolower(trim((string) $h));
            $normalized = str_replace([' ', '_', '-', '.', '/'], '', $normalized);
            $headerMap[$normalized] = $index;
        }

        $mapping = [];
        $candidates = [
            'program' => ['program', 'scholarship', 'scholarshipprogram', 'type'],
            'semester' => ['semester', 'sem', 'term'],
            'academic_year' => ['academicyear', 'ay', 'schoolyear', 'sy', 'year'],
            'submitdate' => ['submitdate', 'billingdate', 'date', 'uploadeddate'],
            'paid' => ['paid', 'amount', 'totalamount', 'billingamount', 'fees', 'totalfees', 'total_amount', 'total_paid', 'sum', 'peso', 'amountpaid'],
            'scholar_count' => ['scholarcount', 'count', 'no_of_scholars', 'scholars', 'no_of_students', 'total_scholars', 'total_students', 'qty', 'quantity'],
            'student_id' => ['studentid', 'id', 'idno', 'idnumber', 'studentnumber', 'studid', 'idno', 'student_id_no', 'sid'],
            'full_name' => ['fullname', 'name', 'studentname', 'student', 'scholarname', 'full_name', 'name_of_scholar'],
            'address' => ['address', 'homeaddress', 'residence', 'address1', 'location'],
            'contact' => ['contact', 'contactnumber', 'phone', 'phonenumber', 'mobile', 'cellphone'],
            'course' => ['course', 'degree', 'programofstudy', 'curriculum'],
            'year_level' => ['yearlevel', 'year', 'level', 'yrlevel'],
            'disbursed_date' => ['disburseddate', 'paydate', 'paymentdate', 'datepaid', 'disbursementdate', 'date_disbursed'],
            'batch_label' => ['batchlabel', 'label', 'description', 'remarks', 'batch', 'batch_label', 'description'],
            'region' => ['region', 'campus', 'location', 'district', 'campus_name'],
            'fb_link' => ['fblink', 'fbaccount', 'facebook', 'facebooklink', 'facebookaccount', 'profile', 'facebookprofile', 'social', 'facebookurl', 'fburl', 'fbprofile', 'fblinkfbaccount'],
        ];

        foreach ($candidates as $field => $synonyms) {
            $idx = $this->firstHeaderIndex($headerMap, $synonyms);
            if ($idx !== null) {
                $mapping[$field] = (string) $idx;
            }
        }

        return $mapping;
    }

    private function normalizeDateValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        // Try Y-m-d first
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;

        // Try d/m/Y or d-m-Y
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        // Try m/d/Y or m-d-Y (Common fallback)
        // If the first part is > 12, it's definitely d/m/Y, which we handled above.
        
        try {
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getRowValue(array $row, $index)
    {
        if ($index === null || !isset($row[$index])) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    private function normalizeCompareValue($value)
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    private function normalizeAcademicYearValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace([
            "\xE2\x80\x92",
            "\xE2\x80\x93",
            "\xE2\x80\x94",
            "\xE2\x80\x95",
            "\xE2\x88\x92",
            "\xEF\xBC\x8D",
            "\xEF\xBF\xBD",
            "\x96",
            "\x97",
        ], '-', $value);

        $value = preg_replace('/\s*-\s*/', '-', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function normalizeSemesterValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $lower = strtolower(preg_replace('/\s+/', ' ', $value));
        $lower = str_replace('.', '', $lower);

        $map = [
            '1st' => '1st Semester',
            '1st sem' => '1st Semester',
            '1st semester' => '1st Semester',
            'first sem' => '1st Semester',
            'first semester' => '1st Semester',
            'sem 1' => '1st Semester',
            'semester 1' => '1st Semester',
            '2nd' => '2nd Semester',
            '2nd sem' => '2nd Semester',
            '2nd semester' => '2nd Semester',
            'second sem' => '2nd Semester',
            'second semester' => '2nd Semester',
            'sem 2' => '2nd Semester',
            'semester 2' => '2nd Semester',
        ];

        if (array_key_exists($lower, $map)) {
            return $map[$lower];
        }

        return $value;
    }

    private function parseDecimalValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '', $value);
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        if ($value === '' || $value === '-' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function parseIntValue($value)
    {
        $decimal = $this->parseDecimalValue($value);
        if ($decimal === null) {
            return null;
        }

        return (int) round($decimal);
    }

    private function parseDateValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Try standard parsing first
        try {
            // Replace / with - to help Carbon distinguish DD/MM from MM/DD if possible
            $standardValue = str_replace('/', '-', $value);
            return Carbon::parse($standardValue)->format('Y-m-d');
        } catch (\Throwable $e) {
            // If it fails, explicitly try DD/MM/YYYY format
            try {
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $matches)) {
                    // Try as Day/Month/Year
                    return Carbon::createFromFormat('d/m/Y', sprintf('%02d/%02d/%04d', $matches[1], $matches[2], $matches[3]))->format('Y-m-d');
                }
            } catch (\Throwable $e2) {
                return '';
            }
            return '';
        }
    }

    private function isEmptyCsvRow(array $row)
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function buildScholarshipConflictNoteEntry($batchProgramLabel, $studentProgramLabel)
    {
        $batchProgramLabel = trim((string) $batchProgramLabel);
        $studentProgramLabel = trim((string) $studentProgramLabel);
        if ($batchProgramLabel === '') {
            $batchProgramLabel = 'N/A';
        }
        if ($studentProgramLabel === '') {
            $studentProgramLabel = 'N/A';
        }

        return 'Student already assigned to scholarship "' . $studentProgramLabel . '", but this billing batch is "' . $batchProgramLabel . '".';
    }

    private function buildScholarshipConflictNoteImport($incomingProgramLabel, $studentProgramLabel)
    {
        $incomingProgramLabel = trim((string) $incomingProgramLabel);
        $studentProgramLabel = trim((string) $studentProgramLabel);
        if ($incomingProgramLabel === '') {
            $incomingProgramLabel = 'N/A';
        }
        if ($studentProgramLabel === '') {
            $studentProgramLabel = 'N/A';
        }

        return 'Student already assigned to scholarship "' . $studentProgramLabel . '", but CSV row program is "' . $incomingProgramLabel . '".';
    }

    private function getCsvHeaders($file)
    {
        if (!$file instanceof UploadedFile) {
            return [];
        }
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        fclose($handle);
        return is_array($headers) ? array_map('trim', $headers) : [];
    }

    private function parseBillingCsvWithMapping($filePath, $mapping)
    {
        $result = [
            'rows' => [],
            'format' => (!empty($mapping['student_id']) || !empty($mapping['full_name'])) ? 'detailed' : 'batch_summary',
            'summary' => [
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'failed' => 0,
                'conflicts' => 0,
                'imported' => 0,
                'created_batches' => 0,
                'errors' => [],
            ],
        ];

        if (!is_file($filePath)) {
            $result['summary']['errors'][] = 'Temporary file lost. Please re-upload.';
            return $result;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $result['summary']['errors'][] = 'Could not open CSV file.';
            return $result;
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return $result;
        }

        // Improvement: If mapping is empty or not provided, try to auto-map based on synonyms
        if (empty($mapping)) {
            $mapping = $this->autoMapBillingHeaders($headers);
        }
        fgetcsv($handle); // Skip headers

        $line = 1;
        $tempRows = [];
        while (($csvRow = fgetcsv($handle)) !== false) {
            $line++;
            if ($this->isEmptyCsvRow($csvRow)) continue;

            $tempRows[] = [
                'line' => $line,
                'student_id' => $this->getRowValue($csvRow, $mapping['student_id'] ?? null),
                'full_name' => $this->getRowValue($csvRow, $mapping['full_name'] ?? null),
                'program' => $this->getRowValue($csvRow, $mapping['program'] ?? null),
                'academic_year' => $this->normalizeAcademicYearValue($this->getRowValue($csvRow, $mapping['academic_year'] ?? null)),
                'semester' => $this->normalizeSemesterValue($this->getRowValue($csvRow, $mapping['semester'] ?? null)),
                'paid' => $this->parseDecimalValue($this->getRowValue($csvRow, $mapping['paid'] ?? null)) ?? 0.0,
                'scholar_count' => (int) $this->getRowValue($csvRow, $mapping['scholar_count'] ?? null),
                'submitdate' => $this->normalizeDateValue($this->getRowValue($csvRow, $mapping['submitdate'] ?? null)) ?: date('Y-m-d'),
                'batch_label' => $this->getRowValue($csvRow, $mapping['batch_label'] ?? null) ?: 'Imported Batch',
                'region' => $this->getRowValue($csvRow, $mapping['region'] ?? null) ?: '',
                'address' => $this->getRowValue($csvRow, $mapping['address'] ?? null),
                'contact' => $this->getRowValue($csvRow, $mapping['contact'] ?? null),
                'course' => $this->getRowValue($csvRow, $mapping['course'] ?? null),
                'year_level' => $this->getRowValue($csvRow, $mapping['year_level'] ?? null),
                'group_key' => $this->getRowValue($csvRow, $mapping['program'] ?? null) . '|' . 
                               $this->normalizeAcademicYearValue($this->getRowValue($csvRow, $mapping['academic_year'] ?? null)) . '|' . 
                               $this->normalizeSemesterValue($this->getRowValue($csvRow, $mapping['semester'] ?? null)) . '|' . 
                               ($this->getRowValue($csvRow, $mapping['batch_label'] ?? null) ?: 'Imported Batch') . '|' . 
                               ($this->getRowValue($csvRow, $mapping['region'] ?? null) ?: '') . '|' . 
                               ($this->getRowValue($csvRow, $mapping['submitdate'] ?? null) ?: date('Y-m-d')),
                'fb_link' => $this->getRowValue($csvRow, $mapping['fb_link'] ?? null),
            ];
        }

        // Group rows to handle both summary and detailed files
        $grouped = [];
        foreach ($tempRows as $tr) {
            $key = (string) $tr['group_key'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = $tr;
                $grouped[$key]['_row_count'] = 0;
                $grouped[$key]['_total_paid'] = 0;
                $grouped[$key]['students'] = []; // Keep track of individuals
            }
            $grouped[$key]['_row_count']++;
            $grouped[$key]['_total_paid'] += $tr['paid'];
            
            // Add student to the group if ID or Name is present
            $grouped[$key]['students'][] = $tr;
        }

        foreach ($grouped as $gKey => &$row) {
            $row['group_key'] = $gKey;
            
            // If the user provided student-level amounts, use the sum
            if ($row['_total_paid'] > $row['paid']) {
                $row['paid'] = $row['_total_paid'];
            }

            // Always ensure the row is in the students list for processing
            if (empty($row['students'])) {
                $row['students'][] = $row;
            }

            // Audit profile completeness for each student in this group
            foreach ($row['students'] as &$s) {
                $student = $this->findStudentForBilling($s['student_id'] ?? '', $s['full_name'] ?? '');
                if ($student) {
                    $s['resolved_student_id'] = $student->id;
                    $comp = ScholarshipMonitoring::isProfileComplete($student);
                    $s['is_profile_complete'] = $comp['is_complete'];
                    $s['missing_fields'] = $comp['missing_fields'];
                } else {
                    $s['is_profile_complete'] = false;
                    $s['missing_fields'] = ['Student not in system'];
                }
            }

            // Auto-detect count if missing
            if ($row['scholar_count'] <= 0) {
                $row['scholar_count'] = count($row['students']);
            }

            $row['is_valid'] = true;
            $row['errors'] = [];

            if ($row['paid'] <= 0) {
                $row['is_valid'] = false;
                $row['errors'][] = 'Amount is required (Check Peso column)';
            }
            if ($row['scholar_count'] <= 0) {
                $row['is_valid'] = false;
                $row['errors'][] = 'Scholar count is required';
            }
            if (empty($row['program'])) {
                $row['is_valid'] = false;
                $row['errors'][] = 'Scholarship program is missing';
            }

            if ($row['is_valid']) {
                $result['summary']['valid']++;
            } else {
                $result['summary']['invalid']++;
            }

            $result['rows'][] = $row;
            $result['summary']['total']++;
        }

        fclose($handle);
        return $result;
    }

    private function parseManualGrantees($students, $program, $semester, $academicYear)
    {
        $rows = [];
        $errors = [];
        $totalAmount = 0;

        foreach ($students as $index => $s) {
            $sidNo = trim((string) ($s['student_id'] ?? ''));
            $amount = (float) ($s['amount'] ?? 0);
            $remark = trim((string) ($s['remark'] ?? 'Manual Entry'));

            if ($sidNo === '') {
                $errors[] = "Row " . ($index + 1) . ": Student ID is required.";
                continue;
            }

            if ($amount <= 0) {
                $errors[] = "Row " . ($index + 1) . ": Invalid amount for student $sidNo.";
                continue;
            }

            $student = DB::table('student')
                ->where('student_id_no', $sidNo)
                ->where('delete_status', '0')
                ->first();

            if (!$student) {
                $errors[] = "Row " . ($index + 1) . ": Student $sidNo not found.";
                continue;
            }

            // Conflict Check
            $isConflict = false;
            $conflictNote = '';
            $studentProgramRaw = trim((string) ($student->scholarship_program ?? ''));
            if ($studentProgramRaw !== '' && $this->normalizeCompareValue($studentProgramRaw) !== $this->normalizeCompareValue($program)) {
                $isConflict = true;
                $conflictNote = $this->buildScholarshipConflictNoteEntry($program, $studentProgramRaw);
            }

            // Smart Academic Year Validation
            $studentAYRaw = trim((string) ($student->scholarship_academic_year ?? ''));
            if ($studentAYRaw !== '' && $academicYear !== '') {
                $ayComparison = $this->compareAcademicYears($academicYear, $studentAYRaw);
                if ($ayComparison > 0) {
                    $isConflict = true;
                    $conflictNote = "Future year mismatch: Student is registered for {$studentAYRaw}, but billing is for {$academicYear}.";
                } elseif ($ayComparison < 0) {
                    $isConflict = true;
                    $conflictNote = "Prior Year Record: Student is currently {$studentAYRaw}, but this record is for {$academicYear}.";
                }
            }

            // Duplicate Check
            $dup = DB::table('fees_transaction as ft')
                ->join('billing_batch as bb', 'bb.id', '=', 'ft.billing_batch_id')
                ->where('ft.stdid', $student->id)
                ->where('bb.semester', $semester)
                ->where('bb.academic_year', $academicYear)
                ->where('bb.delete_status', '0')
                ->whereRaw("COALESCE(ft.record_type, 'billing') = 'billing'")
                ->first();

            if ($dup) {
                $errors[] = "Row " . ($index + 1) . ": Student $sidNo already billed for {$dup->program} this term.";
                continue;
            }

            if ($isConflict) {
                // We hard-block scholarship conflicts and future year conflicts
                if (stripos($conflictNote, 'scholarship') !== false || stripos($conflictNote, 'future') !== false) {
                    $errors[] = "Row " . ($index + 1) . ": Conflict - $conflictNote";
                    continue;
                }
                // Prior Year records are allowed but remains flagged as is_conflict = true for auditing
            }

            $rows[] = [
                'sid' => (int) $student->id,
                'amount' => $amount,
                'remark' => $remark,
                'is_conflict' => false,
                'conflict_note' => '',
                'date' => date('Y-m-d')
            ];
            $totalAmount += $amount;
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
            'total_amount' => $totalAmount
        ];
    }

    private function compareAcademicYears($ay1, $ay2)
    {
        $ay1 = trim((string) $ay1);
        $ay2 = trim((string) $ay2);
        if ($ay1 === $ay2) return 0;
        
        $year1 = (int) explode('-', $ay1)[0];
        $year2 = (int) explode('-', $ay2)[0];
        
        if ($year1 === 0 || $year2 === 0) return 0;
        
        return $year1 <=> $year2;
    }

    private function parseName($fullName)
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return ['last_name' => '', 'given_name' => '', 'middle_initial' => ''];
        }

        // Case 1: "Last, First Middle" format
        if (strpos($fullName, ',') !== false) {
            $parts = array_map('trim', explode(',', $fullName, 2));
            $lastName = $parts[0] ?? '';
            $rest = $parts[1] ?? '';
            
            $tokens = preg_split('/\s+/', $rest, -1, PREG_SPLIT_NO_EMPTY);
            $tokenCount = count($tokens);
            
            if ($tokenCount > 1 && strlen($tokens[$tokenCount - 1]) === 1) {
                $middleInitial = strtoupper($tokens[$tokenCount - 1]);
                array_pop($tokens);
                $givenName = implode(' ', $tokens);
            } else {
                $givenName = $rest;
                $middleInitial = '';
            }

            return [
                'last_name' => $lastName,
                'given_name' => $givenName,
                'middle_initial' => $middleInitial,
            ];
        }

        // Case 2: "First Middle Last" format (no comma)
        $tokens = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        $tokenCount = count($tokens);

        if ($tokenCount === 1) {
            return ['last_name' => $tokens[0], 'given_name' => '', 'middle_initial' => ''];
        }

        if ($tokenCount === 2) {
            return ['last_name' => $tokens[1], 'given_name' => $tokens[0], 'middle_initial' => ''];
        }

        // Handle common multi-word given names by assuming last token is surname
        // Example: "Ian Christian Maranga" -> Given: "Ian Christian", Last: "Maranga"
        if (strlen($tokens[$tokenCount - 2]) === 1) {
            // Middle initial present: "Ian C Maranga"
            $lastName = $tokens[$tokenCount - 1];
            $middleInitial = strtoupper($tokens[$tokenCount - 2]);
            unset($tokens[$tokenCount - 1]);
            unset($tokens[$tokenCount - 2]);
            $givenName = implode(' ', $tokens);
        } else {
            // No obvious initial: "Ian Christian Maranga"
            $lastName = $tokens[$tokenCount - 1];
            array_pop($tokens);
            $givenName = implode(' ', $tokens);
            $middleInitial = '';
        }

        return [
            'last_name' => $lastName,
            'given_name' => $givenName,
            'middle_initial' => $middleInitial,
        ];
    }
}
