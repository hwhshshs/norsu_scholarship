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

class ScholarshipDisbursedController extends Controller
{
    public function report(Request $request)
    {
        return $this->fundReport($request);
    }

    public function fundReport(Request $request)
    {
        $this->bootstrapDisbursedStructures();

        $program = trim((string) $request->query('program', ''));
        $semester = trim((string) $request->query('semester', ''));

        $billingRowsQuery = null;

        if (Schema::hasTable('academic_program')) {
            $billingRowsQuery = DB::table('academic_program as ap')
                ->selectRaw('TRIM(ap.name) AS program')
                ->where('ap.delete_status', '0')
                ->whereRaw("COALESCE(TRIM(ap.name), '') <> ''");

            if ($program !== '') {
                $billingRowsQuery->whereRaw('LOWER(TRIM(ap.name)) = ?', [strtolower($program)]);
            }

            if (Schema::hasTable('fees_transaction') && Schema::hasTable('billing_batch')) {
                if ($semester !== '') {
                    $billingRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT ft.stdid)
                        FROM fees_transaction ft
                        INNER JOIN billing_batch bb2 ON bb2.id = ft.billing_batch_id
                        WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                          AND bb2.delete_status = '0'
                          AND LOWER(TRIM(bb2.program)) = LOWER(TRIM(ap.name))
                          AND bb2.semester = ?
                    ) AS actual_scholars", [$semester]);
                } else {
                    $billingRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT ft.stdid)
                        FROM fees_transaction ft
                        INNER JOIN billing_batch bb2 ON bb2.id = ft.billing_batch_id
                        WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                          AND bb2.delete_status = '0'
                          AND LOWER(TRIM(bb2.program)) = LOWER(TRIM(ap.name))
                    ) AS actual_scholars");
                }
            } else {
                $billingRowsQuery->selectRaw('0 AS actual_scholars');
            }

            $billingRows = $billingRowsQuery
                ->orderBy('ap.name')
                ->limit(500)
                ->get();
        } else {
            $billingRowsQuery = DB::table('billing_batch as bb')
                ->select('bb.program')
                ->where('bb.delete_status', '0')
                ->whereRaw("COALESCE(TRIM(bb.program), '') <> ''");

            if ($program !== '') {
                $billingRowsQuery->where('bb.program', $program);
            }

            if ($semester !== '') {
                $billingRowsQuery->where('bb.semester', $semester);
            }

            if (Schema::hasTable('fees_transaction')) {
                if ($semester !== '') {
                    $billingRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT ft.stdid)
                        FROM fees_transaction ft
                        INNER JOIN billing_batch bb2 ON bb2.id = ft.billing_batch_id
                        WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                          AND bb2.delete_status = '0'
                          AND bb2.program = bb.program
                          AND bb2.semester = ?
                    ) AS actual_scholars", [$semester]);
                } else {
                    $billingRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT ft.stdid)
                        FROM fees_transaction ft
                        INNER JOIN billing_batch bb2 ON bb2.id = ft.billing_batch_id
                        WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                          AND bb2.delete_status = '0'
                          AND bb2.program = bb.program
                    ) AS actual_scholars");
                }
            } else {
                $billingRowsQuery->selectRaw('0 AS actual_scholars');
            }

            $billingRows = $billingRowsQuery
                ->groupBy('bb.program')
                ->orderBy('bb.program')
                ->limit(500)
                ->get();
        }

        $disbursedRowsQuery = null;

        if (Schema::hasTable('academic_program')) {
            $disbursedRowsQuery = DB::table('academic_program as ap')
                ->selectRaw('TRIM(ap.name) AS program')
                ->where('ap.delete_status', '0')
                ->whereRaw("COALESCE(TRIM(ap.name), '') <> ''");

            if ($program !== '') {
                $disbursedRowsQuery->whereRaw('LOWER(TRIM(ap.name)) = ?', [strtolower($program)]);
            }

            if (Schema::hasTable('disbursed_transaction') && Schema::hasTable('billing_batch')) {
                if ($semester !== '') {
                    $disbursedRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT dt.stdid)
                        FROM disbursed_transaction dt
                        INNER JOIN billing_batch bb2 ON bb2.id = dt.billing_batch_id
                        WHERE COALESCE(dt.disbursed_status, 'draft') = 'finalized'
                          AND bb2.delete_status = '0'
                          AND LOWER(TRIM(bb2.program)) = LOWER(TRIM(ap.name))
                          AND bb2.semester = ?
                    ) AS disbursed_scholars", [$semester]);
                } else {
                    $disbursedRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT dt.stdid)
                        FROM disbursed_transaction dt
                        INNER JOIN billing_batch bb2 ON bb2.id = dt.billing_batch_id
                        WHERE COALESCE(dt.disbursed_status, 'draft') = 'finalized'
                          AND bb2.delete_status = '0'
                          AND LOWER(TRIM(bb2.program)) = LOWER(TRIM(ap.name))
                    ) AS disbursed_scholars");
                }
            } else {
                $disbursedRowsQuery->selectRaw('0 AS disbursed_scholars');
            }

            $disbursedRows = $disbursedRowsQuery
                ->orderBy('ap.name')
                ->limit(500)
                ->get();
        } else {
            $disbursedRowsQuery = DB::table('billing_batch as bb')
                ->select('bb.program')
                ->where('bb.delete_status', '0')
                ->whereRaw("COALESCE(TRIM(bb.program), '') <> ''");

            if ($program !== '') {
                $disbursedRowsQuery->where('bb.program', $program);
            }

            if ($semester !== '') {
                $disbursedRowsQuery->where('bb.semester', $semester);
            }

            if (Schema::hasTable('disbursed_transaction')) {
                if ($semester !== '') {
                    $disbursedRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT dt.stdid)
                        FROM disbursed_transaction dt
                        INNER JOIN billing_batch bb2 ON bb2.id = dt.billing_batch_id
                        WHERE COALESCE(dt.disbursed_status, 'draft') = 'finalized'
                          AND bb2.delete_status = '0'
                          AND bb2.program = bb.program
                          AND bb2.semester = ?
                    ) AS disbursed_scholars", [$semester]);
                } else {
                    $disbursedRowsQuery->selectRaw("(
                        SELECT COUNT(DISTINCT dt.stdid)
                        FROM disbursed_transaction dt
                        INNER JOIN billing_batch bb2 ON bb2.id = dt.billing_batch_id
                        WHERE COALESCE(dt.disbursed_status, 'draft') = 'finalized'
                          AND bb2.delete_status = '0'
                          AND bb2.program = bb.program
                    ) AS disbursed_scholars");
                }
            } else {
                $disbursedRowsQuery->selectRaw('0 AS disbursed_scholars');
            }

            $disbursedRows = $disbursedRowsQuery
                ->groupBy('bb.program')
                ->orderBy('bb.program')
                ->limit(500)
                ->get();
        }

        $search = trim((string) $request->input('search'));

        $rowsQuery = DB::table('billing_batch as bb')
            ->leftJoin('disbursed_batch_details as dbd', 'dbd.billing_batch_id', '=', 'bb.id')
            ->select('bb.*')
            ->selectRaw("(
                SELECT GROUP_CONCAT(CONCAT(s.given_name, ' ', s.last_name) SEPARATOR ', ')
                FROM fees_transaction ft
                JOIN student s ON s.id = ft.stdid
                WHERE ft.billing_batch_id = bb.id
            ) AS student_names")
            ->selectRaw("(
                SELECT COUNT(DISTINCT ft.stdid)
                FROM fees_transaction ft
                WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                    AND ft.billing_batch_id = bb.id
            ) AS actual_scholars")
            ->selectRaw("(
                SELECT COUNT(DISTINCT dt.stdid)
                FROM disbursed_transaction dt
                WHERE dt.billing_batch_id = bb.id
                  AND COALESCE(dt.disbursed_status, 'draft') = 'finalized'
            ) AS disbursed_scholars")
            ->selectRaw("(
                SELECT MAX(dt.disbursed_date)
                FROM disbursed_transaction dt
                WHERE dt.billing_batch_id = bb.id
                  AND COALESCE(dt.disbursed_status, 'draft') = 'finalized'
            ) AS last_disbursed_date")
            ->selectRaw("(
                SELECT COALESCE(SUM(CASE WHEN COALESCE(dt.disbursed_status, 'draft') = 'finalized' THEN dt.disbursed_amount ELSE 0 END), 0)
                FROM disbursed_transaction dt
                WHERE dt.billing_batch_id = bb.id
            ) AS finalized_amount")
            ->selectRaw("COALESCE(NULLIF(TRIM(dbd.ada_no), ''), '') AS batch_ada_no")
            ->selectRaw("COALESCE(NULLIF(TRIM(dbd.or_number), ''), '') AS batch_or_no")
            ->selectRaw("dbd.or_date AS batch_or_date");

        if ($program !== '') {
            $rowsQuery->where('bb.program', $program);
        }
        if ($semester !== '') {
            $rowsQuery->where('bb.semester', $semester);
        }
        if ($search !== '') {
            $rowsQuery->whereExists(function($q) use ($search) {
                $q->from('fees_transaction as ft_search')
                    ->join('student as s_search', 's_search.id', '=', 'ft_search.stdid')
                    ->whereRaw('ft_search.billing_batch_id = bb.id')
                    ->where(function($qq) use ($search) {
                        $qq->where('s_search.given_name', 'like', "%$search%")
                          ->orWhere('s_search.last_name', 'like', "%$search%")
                          ->orWhere('s_search.student_id_no', 'like', "%$search%");
                    });
            });
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

        return view('scholarship.fund-report.index', [
            'billingRows' => $billingRows,
            'disbursedRows' => $disbursedRows,
            'rows' => $rows,
            'program' => $program,
            'semester' => $semester,
            'search' => $search,
            'programOptions' => $this->getFundReportProgramOptions(),
            'semesterOptions' => $this->getSemesterOptions(),
        ]);
    }

    public function show(Request $request, $batch)
    {
        $this->bootstrapDisbursedStructures();

        $batchId = (int) $batch;
        $batchRow = DB::table('billing_batch')->where('id', $batchId)->first();
        if (!$batchRow) {
            abort(404);
        }

        $rowsQuery = DB::table('disbursed_transaction as dt')
            ->leftJoin('student as s', 's.id', '=', 'dt.stdid')
            ->select([
                'dt.stdid',
                'dt.disbursed_date',
                'dt.disbursed_amount',
                'dt.ada_no',
                'dt.or_no',
                'dt.or_date',
                'dt.attachment_file',
                's.sname',
                's.student_id_no',
                's.fb_link',
            ])
            ->where('dt.billing_batch_id', $batchId);

        $rows = $rowsQuery
            ->orderBy('s.sname')
            ->orderBy('dt.stdid')
            ->limit(1000)
            ->get();

        $totals = [
            'rows' => (int) DB::table('disbursed_transaction')->where('billing_batch_id', $batchId)->count(),
            'finalized_rows' => (int) DB::table('disbursed_transaction')
                ->where('billing_batch_id', $batchId)
                ->whereRaw("COALESCE(disbursed_status, 'draft') = 'finalized'")
                ->count(),
            'finalized_amount' => (float) DB::table('disbursed_transaction')
                ->where('billing_batch_id', $batchId)
                ->whereRaw("COALESCE(disbursed_status, 'draft') = 'finalized'")
                ->sum('disbursed_amount'),
        ];

        $batchDetails = DB::table('disbursed_batch_details')
            ->where('billing_batch_id', $batchId)
            ->first();

        return view('scholarship.disbursed.show', [
            'batch' => $batchRow,
            'rows' => $rows,
            'totals' => $totals,
            'batchDetails' => $batchDetails,
        ]);
    }

    public function entryForm(Request $request)
    {
        $this->bootstrapDisbursedStructures();

        $selectedBatchId = (int) $request->query('batch_id', 0);
        $selectedProgram = trim((string) $request->query('program', ''));
        $selectedSemester = $this->normalizeSemesterValue($request->query('semester', ''));
        if ($request->query('semester', '') === '') $selectedSemester = '';
        $resolvedBatch = null;

        if ($selectedBatchId > 0) {
            $resolvedBatch = $this->findActiveBatch($selectedBatchId, false);
            if ($resolvedBatch) {
                if ($selectedProgram === '') {
                    $selectedProgram = trim((string) ($resolvedBatch->program ?? ''));
                }
                if ($selectedSemester === '') {
                    $selectedSemester = trim((string) ($resolvedBatch->semester ?? ''));
                }
            }
        }

        if (!$resolvedBatch && $selectedProgram !== '' && $selectedSemester !== '') {
            $resolvedBatch = $this->findActiveBatchByProgramSemester($selectedProgram, $selectedSemester);
        }

        $pendingStudents = [];
        if ($resolvedBatch) {
            $pendingStudents = DB::table('fees_transaction as ft')
                ->join('student as s', 's.id', '=', 'ft.stdid')
                ->select([
                    'ft.stdid',
                    'ft.paid as billed_amount',
                    's.sname',
                    's.student_id_no',
                ])
                ->where('ft.billing_batch_id', $resolvedBatch->id)
                ->whereRaw("COALESCE(ft.record_type, 'billing') = 'billing'")
                ->whereNotExists(function ($query) use ($resolvedBatch) {
                    $query->select(DB::raw(1))
                        ->from('disbursed_transaction as dt')
                        ->whereRaw('dt.stdid = ft.stdid')
                        ->where('dt.billing_batch_id', $resolvedBatch->id)
                        ->whereRaw("COALESCE(dt.disbursed_status, 'draft') = 'finalized'");
                })
                ->orderBy('s.sname')
                ->get();
        }

        return view('scholarship.disbursed.entry', [
            'resolvedBatch' => $resolvedBatch,
            'pendingStudents' => $pendingStudents,
            'selectedProgram' => $selectedProgram,
            'selectedSemester' => $selectedSemester,
            'programOptions' => $this->getProgramOptions(),
            'semesterOptions' => $this->getSemesterOptions(),
        ]);
    }

    public function entryStore(Request $request)
    {
        $this->bootstrapDisbursedStructures();

        $validated = $request->validate([
            'program' => 'required|string|max:150',
            'semester' => 'required|string|max:60',
            'disbursed_date' => 'required|date',
            'disbursed_amount' => 'required|numeric|min:0.01',
            'grantee_csv' => 'nullable|file|mimes:csv,txt',
            'ada_no' => 'required|string|max:100',
            'or_no' => 'required|string|max:100',
            'or_date' => 'required|date',
            'attachment_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,csv,txt,xlsx,xls|max:10240',
        ]);

        $program = trim((string) $validated['program']);
        $semester = $this->normalizeSemesterValue((string) $validated['semester']);

        $batch = $this->findActiveBatchByProgramSemester($program, $semester);
        if (!$batch) {
            return back()->withErrors([
                'program' => 'No active billing batch found for the selected Scholarship Program and Semester.',
            ])->withInput();
        }
        $batchId = (int) ($batch->id ?? 0);

        // Prevent duplicate ADA or OR numbers across batches
        $adaNo = trim((string) ($request->input('ada_no') ?? ''));
        $orNo = trim((string) ($request->input('or_no') ?? ''));

        if ($adaNo !== '') {
            $existingAda = DB::table('disbursed_batch_details')
                ->where('ada_no', $adaNo)
                ->where('billing_batch_id', '<>', $batchId)
                ->first();
            if ($existingAda) {
                return back()->withErrors([
                    'ada_no' => 'ADA No. ' . $adaNo . ' is already used in another batch.',
                ])->withInput();
            }
        }

        if ($orNo !== '') {
            $existingOr = DB::table('disbursed_batch_details')
                ->where('or_number', $orNo)
                ->where('billing_batch_id', '<>', $batchId)
                ->first();
            if ($existingOr) {
                return back()->withErrors([
                    'or_no' => 'OR No. ' . $orNo . ' is already used in another batch.',
                ])->withInput();
            }
        }

        $parsedRows = [];
        $totalAmount = 0;
        $csv = $request->file('grantee_csv');
        $parsed = ['rows' => [], 'errors' => [], 'total' => 0, 'valid' => 0, 'invalid' => 0, 'total_amount' => 0];

        if ($csv) {
            $parsed = $this->parseDisbursedCsv($csv, $batch);
            $parsedRows = $parsed['rows'] ?? [];
            $totalAmount = (float) ($parsed['total_amount'] ?? 0);
        } elseif ($request->has('manual_students')) {
            foreach ($request->input('manual_students') as $ms) {
                if (empty($ms['selected'])) continue;
                $parsedRows[] = [
                    'sid' => $ms['stdid'],
                    'amount' => (float) $ms['amount'],
                    'remark' => $ms['remark'] ?? '',
                    'is_valid' => true
                ];
                $totalAmount += (float) $ms['amount'];
            }
        }

        if ((int) (count($parsedRows) ?? 0) <= 0) {
            $errorMessage = count($parsed['errors']) > 0
                ? implode(' | ', array_slice($parsed['errors'], 0, 8))
                : 'No valid rows found in disbursed CSV.';

            $this->recordDisbursedInvalidRows((array) ($parsedRows ?? []), 'disbursed_entry', $batchId);

            return back()->withErrors([
                'grantee_csv' => $errorMessage,
            ])->withInput();
        }

        $declaredTotal = (float) $validated['disbursed_amount'];
        if (abs((float) $totalAmount - $declaredTotal) > 0.01) {
            return back()->withErrors([
                'disbursed_amount' => 'Disbursed amount does not match sum of grantee values.',
            ])->withInput();
        }

        $disbursedDate = $this->parseDateValue((string) $validated['disbursed_date']);
        $orDate = $this->parseDateValue((string) ($validated['or_date'] ?? ''));
        $adaNo = trim((string) ($validated['ada_no'] ?? ''));
        $orNo = trim((string) ($validated['or_no'] ?? ''));
        $createdBy = (int) (Auth::id() ?? 0);
        $attachmentPath = $this->storeDisbursedAttachment($request->file('attachment_file'), 'disbursed_entry');

        $finalizedRows = 0;
        $skippedErrors = [];
        DB::beginTransaction();
        try {
            foreach ($parsedRows as $row) {
                if (empty($row['is_valid'])) continue;
                
                $sid = (int) $row['sid'];
                $amount = (float) $row['amount'];

                $this->applyDisbursedStudentSmartUpdate($sid, $row);

                // Hard Gate: Check Profile Completeness
                $studentObj = DB::table('student')->where('id', $sid)->first();
                $comp = ScholarshipMonitoring::isProfileComplete($studentObj);
                if (!$comp['is_complete']) {
                    $skippedErrors[] = "Student " . ($studentObj->student_id_no ?? $sid) . " - Incomplete profile: " . implode(', ', $comp['missing_fields']);
                    continue;
                }

                $sid = (int) $row['sid'];
                $amount = (float) $row['amount'];

                // Term-Wide Duplicate Check
                $alreadyDisbursed = DB::table('disbursed_transaction')
                    ->where('stdid', $sid)
                    ->where('program', (string) ($batch->program ?? ''))
                    ->where('semester', (string) ($batch->semester ?? ''))
                    ->where('academic_year', (string) ($batch->academic_year ?? ''))
                    ->where('disbursed_status', 'finalized')
                    ->exists();

                if ($alreadyDisbursed) continue;

                $rowRemark = trim((string) ($row['remark'] ?? 'Disbursed finalization'));

                DB::table('disbursed_transaction')->updateOrInsert(
                    ['billing_batch_id' => $batchId, 'stdid' => $sid],
                    [
                        'program' => (string) ($batch->program ?? ''),
                        'semester' => (string) ($batch->semester ?? ''),
                        'academic_year' => (string) ($batch->academic_year ?? ''),
                        'batch_label' => (string) ($batch->batch_label ?? ''),
                        'region' => (string) ($batch->region ?? ''),
                        'disbursed_date' => $disbursedDate,
                        'disbursed_amount' => $amount,
                        'ada_no' => !empty($row['ada_no']) ? $row['ada_no'] : $adaNo,
                        'or_no' => !empty($row['or_no']) ? $row['or_no'] : $orNo,
                        'or_date' => !empty($row['or_date']) ? $row['or_date'] : ($orDate ?: null),
                        'attachment_file' => $attachmentPath,
                        'remarks' => $rowRemark,
                        'created_by' => $createdBy,
                        'disbursed_status' => 'finalized',
                    ]
                );

                $this->recalcStudentBalance($sid);
                $finalizedRows++;
            }

            $this->upsertBatchDetails($batchId, [
                'date_on_ada_details' => $disbursedDate,
                'ada_no' => $adaNo,
                'or_number' => $orNo,
                'or_date' => $orDate,
                'status_students_disbursed' => $finalizedRows,
                'created_by' => $createdBy,
            ]);

            $this->refreshBillingBatchStatus($batchId);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['grantee_csv' => 'Error: ' . $e->getMessage()]);
        }

        if (count($skippedErrors) > 0) {
            return redirect()->route('scholarship-disbursed.show', $batchId)->with('warning', "Finalized $finalizedRows rows. However, " . count($skippedErrors) . " were skipped due to incomplete profiles.");
        }

        return redirect()->route('scholarship-disbursed.show', $batchId)->with('success', "Finalized $finalizedRows rows.");
    }

    public function importForm(Request $request)
    {
        $this->bootstrapDisbursedStructures();
        $selectedBatchId = (int) $request->query('batch_id', 0);

        return view('scholarship.disbursed.import', [
            'batchOptions' => $this->getDisbursableBatches(),
            'selectedBatchId' => $selectedBatchId,
            'rows' => [],
            'summary' => ['total' => 0, 'valid' => 0, 'invalid' => 0, 'errors' => [], 'total_amount' => 0, 'imported' => 0],
            'selectedMode' => 'import',
            'importError' => '',
        ]);
    }

    public function importTemplate()
    {
        $response = new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['student_id', 'last_name', 'given_name', 'amount', 'disbursed_date', 'ada_no', 'or_no', 'fb_link', 'contact', 'address']);
            fputcsv($out, ['202203733', 'Vergara', 'Genderson', '7500.00', date('Y-m-d'), 'ADA-12345', 'OR-67890', 'https://facebook.com/example', '09123456789', 'Main St. Brgy. 1']);
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=disbursed_template.csv');
        return $response;
    }

    public function importProcess(Request $request)
    {
        $this->bootstrapDisbursedStructures();
        $validated = $request->validate([
            'billing_batch_id' => 'required|integer',
            'disbursed_csv' => 'required|file',
            'mode' => 'required|in:preview,import',
            'disbursed_date' => 'required|date',
            'ada_no' => 'required|string',
            'or_no' => 'required|string',
            'or_date' => 'required|date',
        ]);

        $batchId = (int) $validated['billing_batch_id'];
        $batch = $this->findActiveBatch($batchId);
        $csv = $request->file('disbursed_csv');
        $parsed = $this->parseDisbursedCsv($csv, $batch);
        $parsedRows = $parsed['rows'] ?? [];

        if ($validated['mode'] === 'preview') {
            return view('scholarship.disbursed.import', [
                'batchOptions' => $this->getDisbursableBatches(),
                'selectedBatchId' => $batchId,
                'rows' => $parsedRows,
                'summary' => $parsed,
                'selectedMode' => 'preview',
                'importError' => count($parsed['errors']) > 0 ? implode(', ', array_slice($parsed['errors'], 0, 5)) : '',
            ]);
        }

        $disbursedDate = $this->parseDateValue((string) $validated['disbursed_date']);
        $adaNo = trim((string) ($validated['ada_no'] ?? ''));
        $orNo = trim((string) ($validated['or_no'] ?? ''));
        $orDate = $this->parseDateValue((string) ($validated['or_date'] ?? ''));
        $createdBy = (int) (Auth::id() ?? 0);
        $attachmentPath = '';

        $imported = 0;
        $skippedErrors = [];
        DB::beginTransaction();
        try {
            foreach ($parsedRows as $row) {
                if (empty($row['is_valid'])) continue;

                $sid = (int) $row['sid'];
                $amount = (float) $row['amount'];

                $this->applyDisbursedStudentSmartUpdate($sid, $row);
                
                // Hard Gate: Check Profile Completeness
                $studentObj = DB::table('student')->where('id', $sid)->first();
                $comp = ScholarshipMonitoring::isProfileComplete($studentObj);
                if (!$comp['is_complete']) {
                    $skippedErrors[] = "Student " . ($studentObj->student_id_no ?? $sid) . " - Incomplete profile: " . implode(', ', $comp['missing_fields']);
                    continue;
                }

                // Term-Wide Duplicate Check
                $alreadyDisbursed = DB::table('disbursed_transaction')
                    ->where('stdid', $sid)
                    ->where('program', (string) ($batch->program ?? ''))
                    ->where('semester', (string) ($batch->semester ?? ''))
                    ->where('academic_year', (string) ($batch->academic_year ?? ''))
                    ->where('disbursed_status', 'finalized')
                    ->exists();

                if ($alreadyDisbursed) continue;

                $rowDate = trim((string) ($row['disbursed_date'] ?? ''));
                if ($rowDate === '') $rowDate = $disbursedDate;

                DB::table('disbursed_transaction')->updateOrInsert(
                    ['billing_batch_id' => $batchId, 'stdid' => $sid],
                    [
                        'program' => (string) ($batch->program ?? ''),
                        'semester' => (string) ($batch->semester ?? ''),
                        'academic_year' => (string) ($batch->academic_year ?? ''),
                        'batch_label' => (string) ($batch->batch_label ?? ''),
                        'region' => (string) ($batch->region ?? ''),
                        'disbursed_date' => $rowDate,
                        'disbursed_amount' => $amount,
                        'ada_no' => !empty($row['ada_no']) ? $row['ada_no'] : $adaNo,
                        'or_no' => !empty($row['or_no']) ? $row['or_no'] : $orNo,
                        'or_date' => !empty($row['or_date']) ? $row['or_date'] : ($orDate ?: null),
                        'remarks' => trim((string) ($row['remark'] ?? 'Disbursed import')),
                        'created_by' => $createdBy,
                        'disbursed_status' => 'finalized',
                    ]
                );

                $this->recalcStudentBalance($sid);
                $imported++;
            }

            $this->upsertBatchDetails($batchId, [
                'date_on_ada_details' => $disbursedDate,
                'ada_no' => $adaNo,
                'or_number' => $orNo,
                'or_date' => $orDate,
                'status_students_disbursed' => $imported,
                'created_by' => $createdBy,
            ]);

            $this->refreshBillingBatchStatus($batchId);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['import' => 'Failed to process import: ' . $e->getMessage()]);
        }

        if (count($skippedErrors) > 0) {
            return redirect()->route('scholarship-disbursed.report')->with('warning', "Successfully finalized $imported students. However, " . count($skippedErrors) . " were skipped due to incomplete profiles.");
        }

        return redirect()->route('scholarship-disbursed.report')->with('success', "Successfully finalized $imported students.");
    }

    public function fastFinalizeBatch(Request $request, $batchId)
    {
        $validated = $request->validate([
            'ada_no' => 'required|string|max:100',
            'or_no' => 'required|string|max:100',
            'or_date' => 'required|date',
            'disbursed_date' => 'required|date',
        ]);

        $batch = DB::table('billing_batch')->where('id', $batchId)->where('delete_status', '0')->first();
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
        }

        $adaNo = trim((string) $validated['ada_no']);
        $orNo = trim((string) $validated['or_no']);
        $orDate = $validated['or_date'];
        $disbursedDate = $validated['disbursed_date'];
        $createdBy = Auth::id();

        // Hard Gate: Check Profile Completeness for all students in this batch
        $pendingStudents = DB::table('disbursed_transaction as dt')
            ->join('student as s', 's.id', '=', 'dt.stdid')
            ->select('s.*')
            ->where('dt.billing_batch_id', $batchId)
            ->where('dt.disbursed_status', 'draft')
            ->get();

        $incomplete = [];
        foreach ($pendingStudents as $student) {
            $comp = ScholarshipMonitoring::isProfileComplete($student);
            if (!$comp['is_complete']) {
                $incomplete[] = [
                    'name' => $student->given_name . ' ' . $student->last_name,
                    'id_no' => $student->student_id_no,
                    'missing' => implode(', ', $comp['missing_fields'])
                ];
            }
        }

        if (count($incomplete) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Batch contains students with incomplete profiles: ' . collect($incomplete)->pluck('name')->implode(', ') . '. Please update their profiles first.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Finalize ALL pending students in this batch
            $updated = DB::table('disbursed_transaction')
                ->where('billing_batch_id', $batchId)
                ->where('disbursed_status', 'draft')
                ->update([
                    'disbursed_status' => 'finalized',
                    'ada_no' => $adaNo,
                    'or_no' => $orNo,
                    'or_date' => $orDate,
                    'disbursed_date' => $disbursedDate,
                    'created_by' => $createdBy,
                ]);

            // Ensure batch details are updated
            $this->upsertBatchDetails($batchId, [
                'date_on_ada_details' => $disbursedDate,
                'ada_no' => $adaNo,
                'or_number' => $orNo,
                'or_date' => $orDate,
                'status_students_disbursed' => DB::table('disbursed_transaction')
                    ->where('billing_batch_id', $batchId)
                    ->where('disbursed_status', 'finalized')
                    ->count(),
                'created_by' => $createdBy,
            ]);

            $this->refreshBillingBatchStatus($batchId);
            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => "Successfully finalized all $updated students in this batch."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function parseDisbursedCsv($file, $batch)
    {
        $result = ['rows' => [], 'errors' => [], 'total' => 0, 'valid' => 0, 'invalid' => 0, 'total_amount' => 0];
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $headerMap = array_map(fn($h) => strtolower(trim(preg_replace('/[^a-z0-9]+/', '_', (string)$h), '_')), (array)$headers);

        $si = array_search('student_id', $headerMap);
        if ($si === false) $si = array_search('studentid', $headerMap);
        $ni = array_search('full_name', $headerMap);
        if ($ni === false) $ni = array_search('name', $headerMap);
        $ai = array_search('amount', $headerMap);
        if ($ai === false) $ai = array_search('disbursed_amount', $headerMap);
        $di = array_search('disbursed_date', $headerMap);
        $ri = array_search('remark', $headerMap);
        if ($ri === false) $ri = array_search('remarks', $headerMap);

        // Per-row ADA/OR mapping
        $ada_col = array_search('ada_no', $headerMap);
        if ($ada_col === false) $ada_col = array_search('ada', $headerMap);
        if ($ada_col === false) $ada_col = array_search('ada_number', $headerMap);
        $or_col = array_search('or_no', $headerMap);
        if ($or_col === false) $or_col = array_search('or', $headerMap);
        if ($or_col === false) $or_col = array_search('or_number', $headerMap);
        if ($or_col === false) $or_col = array_search('ip_no', $headerMap);
        if ($or_col === false) $or_col = array_search('ip', $headerMap);
        if ($or_col === false) $or_col = array_search('ip_number', $headerMap);
        $ord_col = array_search('or_date', $headerMap);
        if ($ord_col === false) $ord_col = array_search('ip_date', $headerMap);
        $fb_col = array_search('fb_link', $headerMap);
        if ($fb_col === false) $fb_col = array_search('facebook', $headerMap);
        if ($fb_col === false) $fb_col = array_search('fb_account', $headerMap);
        if ($fb_col === false) $fb_col = array_search('facebook_account', $headerMap);
        if ($fb_col === false) $fb_col = array_search('fb', $headerMap);

        // Smart-fill columns for profile completeness
        $bd_col = array_search('birthdate', $headerMap);
        if ($bd_col === false) $bd_col = array_search('birth_date', $headerMap);
        $sch_col = array_search('school', $headerMap);
        if ($sch_col === false) $sch_col = array_search('school_name', $headerMap);
        $gn_col = array_search('guardian_name', $headerMap);
        $gc_col = array_search('guardian_contact', $headerMap);
        $addr_col = array_search('address', $headerMap);
        $con_col = array_search('contact', $headerMap);
        $crs_col = array_search('course', $headerMap);
        $yr_col = array_search('year_level', $headerMap);

        $seen = [];
        $line = 1;
        while (($csvRow = fgetcsv($handle)) !== false) {
            $line++;
            if (empty(array_filter($csvRow))) continue;

            $result['total']++;
            $row = [
                'line' => $line,
                'sid' => 0,
                'student_id' => ($si !== false) ? trim((string)$csvRow[$si]) : '',
                'full_name' => ($ni !== false) ? trim((string)$csvRow[$ni]) : '',
                'amount_raw' => ($ai !== false) ? trim((string)$csvRow[$ai]) : '',
                'disbursed_date' => ($di !== false) ? $this->parseDateValue($csvRow[$di]) : '',
                'ada_no' => ($ada_col !== false) ? trim((string)$csvRow[$ada_col]) : '',
                'or_no' => ($or_col !== false) ? trim((string)$csvRow[$or_col]) : '',
                'or_date' => ($ord_col !== false) ? $this->parseDateValue($csvRow[$ord_col]) : '',
                'birthdate' => ($bd_col !== false) ? trim((string)$csvRow[$bd_col]) : '',
                'school' => ($sch_col !== false) ? trim((string)$csvRow[$sch_col]) : '',
                'guardian_name' => ($gn_col !== false) ? trim((string)$csvRow[$gn_col]) : '',
                'guardian_contact' => ($gc_col !== false) ? trim((string)$csvRow[$gc_col]) : '',
                'address' => ($addr_col !== false) ? trim((string)$csvRow[$addr_col]) : '',
                'contact' => ($con_col !== false) ? trim((string)$csvRow[$con_col]) : '',
                'course' => ($crs_col !== false) ? trim((string)$csvRow[$crs_col]) : '',
                'year_level' => ($yr_col !== false) ? trim((string)$csvRow[$yr_col]) : '',
                'fb_link' => ($fb_col !== false) ? trim((string)$csvRow[$fb_col]) : '',
                'remark' => ($ri !== false) ? trim((string)$csvRow[$ri]) : '',
                'is_valid' => true,
                'errors' => []
            ];

            $amount = (float) preg_replace('/[^\d.]/', '', $row['amount_raw']);
            if ($amount <= 0) {
                $row['is_valid'] = false;
                $row['errors'][] = 'Invalid amount';
            } else {
                $row['amount'] = $amount;
            }

            if ($row['is_valid']) {
                $student = ScholarshipMonitoring::resolveStudentByKeys($row['student_id'], $row['full_name']);
                if (!$student) {
                    $row['is_valid'] = false;
                    $row['errors'][] = 'Student not found';
                } else {
                    $row['sid'] = $student->id;
                    if (isset($seen[$student->id])) {
                        $row['is_valid'] = false;
                        $row['errors'][] = 'Duplicate in file';
                    } else {
                        $seen[$student->id] = true;
                    }

                    $comp = ScholarshipMonitoring::isProfileComplete($student);
                    $row['is_profile_complete'] = $comp['is_complete'];
                    $row['missing_fields'] = $comp['missing_fields'];
                    $row['completion_percentage'] = $comp['completion_percentage'];
                }
            }

            if ($row['is_valid']) {
                $result['valid']++;
                $result['total_amount'] += $row['amount'];
            } else {
                $result['invalid']++;
                $result['errors'][] = "Line $line: " . implode(', ', $row['errors']);
            }
            $result['rows'][] = $row;
        }
        fclose($handle);
        return $result;
    }

    private function bootstrapDisbursedStructures()
    {
        ScholarshipMonitoring::bootstrapMonitoringStructures();
        ScholarshipMonitoring::ensureStudentMonitoringColumns();

        if (!Schema::hasTable('disbursed_batch_details')) {
            DB::statement("CREATE TABLE IF NOT EXISTS disbursed_batch_details (
                billing_batch_id INT(11) NOT NULL PRIMARY KEY,
                date_on_ada_details DATE DEFAULT NULL,
                ada_no VARCHAR(100) DEFAULT NULL,
                admin_cost DECIMAL(15,2) DEFAULT 0.00,
                or_number VARCHAR(100) DEFAULT NULL,
                or_date DATE DEFAULT NULL,
                status_students_disbursed INT(11) DEFAULT 0,
                remarks TEXT DEFAULT NULL,
                created_by INT(11) DEFAULT NULL
            )");
        }
    }

    private function getSemesterOptions()
    {
        if (Schema::hasTable('academic_semester')) {
            $options = DB::table('academic_semester')
                ->where('delete_status', '0')
                ->orderBy('id')
                ->pluck('label', 'label')
                ->toArray();
            if (!empty($options)) return $options;
        }
        return ['1st Semester' => '1st Semester', '2nd Semester' => '2nd Semester', 'Summer' => 'Summer', 'Midyear' => 'Midyear'];
    }

    private function getProgramOptions()
    {
        if (Schema::hasTable('academic_program')) {
            $options = DB::table('academic_program')
                ->where('delete_status', '0')
                ->orderBy('name')
                ->pluck('name', 'name')
                ->toArray();
            if (!empty($options)) return $options;
        }
        return $this->getDistinctBillingValues('program');
    }

    private function getDistinctBillingValues($column)
    {
        if (!Schema::hasTable('billing_batch')) return [];
        return DB::table('billing_batch')->where('delete_status', '0')->whereRaw("COALESCE(TRIM($column), '') <> ''")->distinct()->orderBy($column)->pluck($column, $column)->toArray();
    }

    private function getFundReportProgramOptions()
    {
        return $this->getProgramOptions();
    }

    private function findActiveBatch($id, $fail = true)
    {
        $batch = DB::table('billing_batch')->where('id', (int)$id)->where('delete_status', '0')->first();
        if (!$batch && $fail) abort(404);
        return $batch;
    }

    private function findActiveBatchByProgramSemester($program, $semester)
    {
        return DB::table('billing_batch')->where('program', $program)->where('semester', $semester)->where('delete_status', '0')->orderByDesc('id')->first();
    }

    private function getDisbursableBatches()
    {
        return DB::table('billing_batch')->where('delete_status', '0')->orderByDesc('id')->get();
    }

    private function parseDateValue($val)
    {
        if (trim((string)$val) === '') return '';
        try { return Carbon::parse($val)->format('Y-m-d'); } catch (\Throwable $e) { return ''; }
    }

    private function normalizeSemesterValue($val)
    {
        $v = strtolower(trim((string)$val));
        if (strpos($v, 'first') !== false || strpos($v, '1st') !== false) return '1st Semester';
        if (strpos($v, 'second') !== false || strpos($v, '2nd') !== false) return '2nd Semester';
        return ucwords($v);
    }

    private function recalcStudentBalance($sid)
    {
        // Calculate balance: (Total Billed) - (Total Disbursed)
        $totalBilled = (float) DB::table('fees_transaction')
            ->where('stdid', $sid)
            ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
            ->sum('paid');

        $totalDisbursed = (float) DB::table('disbursed_transaction')
            ->where('stdid', $sid)
            ->whereRaw("COALESCE(disbursed_status, 'draft') = 'finalized'")
            ->sum('disbursed_amount');

        $balance = $totalBilled - $totalDisbursed;

        // Update student record
        if (Schema::hasColumn('student', 'balance')) {
            DB::table('student')->where('id', $sid)->update(['balance' => $balance]);
        }
    }

    private function refreshBillingBatchStatus($id)
    {
        // Count how many students were billed vs how many were disbursed
        $billedCount = DB::table('fees_transaction')
            ->where('billing_batch_id', $id)
            ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
            ->count();

        $disbursedCount = DB::table('disbursed_transaction')
            ->where('billing_batch_id', $id)
            ->whereRaw("COALESCE(disbursed_status, 'draft') = 'finalized'")
            ->count();

        $status = 'draft';
        if ($disbursedCount >= $billedCount && $billedCount > 0) {
            $status = 'finalized';
        } elseif ($disbursedCount > 0) {
            $status = 'partially_disbursed';
        }

        DB::table('billing_batch')->where('id', $id)->update(['status' => $status]);
    }

    private function upsertBatchDetails($id, $data)
    {
        DB::table('disbursed_batch_details')->updateOrInsert(['billing_batch_id' => (int)$id], $data);
    }

    private function applyDisbursedStudentSmartUpdate($studentId, array $row)
    {
        $sid = (int) $studentId;
        if ($sid <= 0) return;

        \App\Support\ScholarshipMonitoring::applyStudentSmartUpdate($sid, [
            'address' => trim((string) ($row['address'] ?? '')),
            'contact' => trim((string) ($row['contact'] ?? '')),
            'birthdate' => trim((string) ($row['birthdate'] ?? '')),
            'course' => trim((string) ($row['course'] ?? '')),
            'year_level' => trim((string) ($row['year_level'] ?? '')),
            'school' => trim((string) ($row['school'] ?? '')),
            'guardian_name' => trim((string) ($row['guardian_name'] ?? '')),
            'guardian_contact' => trim((string) ($row['guardian_contact'] ?? '')),
            'fb_link' => trim((string) ($row['fb_link'] ?? '')),
        ]);
    }

    private function recordDisbursedInvalidRows(array $rows, $module, $batchId)
    {
        // Internal logging
    }

    private function storeDisbursedAttachment($file, $module)
    {
        if (!$file) return '';
        return $file->store('scholarship/disbursed/' . $module, 'public');
    }

    private function countDuplicateRows(array $rows)
    {
        $seen = []; $dupes = 0;
        foreach ($rows as $r) {
            $sid = (int)($r['sid'] ?? 0);
            if ($sid > 0) { if (isset($seen[$sid])) $dupes++; else $seen[$sid] = true; }
        }
        return $dupes;
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

        if (strlen($tokens[$tokenCount - 2]) === 1) {
            $lastName = $tokens[$tokenCount - 1];
            $middleInitial = strtoupper($tokens[$tokenCount - 2]);
            unset($tokens[$tokenCount - 1]);
            unset($tokens[$tokenCount - 2]);
            $givenName = implode(' ', $tokens);
        } else {
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