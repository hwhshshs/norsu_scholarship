<?php

namespace App\Http\Controllers;

use App\Support\ScholarshipMonitoring;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipStudentController extends Controller
{
    public function lookupByIdNo($idNo)
    {
        $student = DB::table('student')
            ->where('student_id_no', $idNo)
            ->where('delete_status', '0')
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'name' => trim(($student->last_name ?? '') . ', ' . ($student->given_name ?? '')),
                'program' => $student->scholarship_program ?: $student->degree_program,
                'semester' => $student->scholarship_semester,
                'academic_year' => $student->scholarship_academic_year,
                'year_level' => $student->year_level,
            ]
        ]);
    }

    public function index(Request $request)
    {
        $this->bootstrapStudentStructures();

        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $financialStatus = trim((string) $request->query('financial_status', 'all'));
        if (!in_array($financialStatus, ['all', 'not_billed', 'billed', 'disbursed'], true)) {
            $financialStatus = 'all';
        }

        $query = DB::table('student');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%' . $search . '%';
                $builder->where('student_id_no', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('given_name', 'like', $like)
                    ->orWhere('middle_initial', 'like', $like)
                    ->orWhere('degree_program', 'like', $like)
                    ->orWhere('scholarship_program', 'like', $like)
                    ->orWhere('contact', 'like', $like)
                    ->orWhere('emailid', 'like', $like)
                    ->orWhere('sname', 'like', $like);
            });
        }

        if ($status === 'active') {
            $query->where('delete_status', '0');
        } elseif ($status === 'inactive') {
            $query->where('delete_status', '1');
        }

        $studentsQuery = clone $query;
        $this->applyFinancialStatusSelects($studentsQuery);

        if ($financialStatus === 'not_billed') {
            $studentsQuery->whereRaw('COALESCE(disbursed_rows_agg.disbursed_rows, 0) = 0 AND COALESCE(billing_rows_agg.billing_rows, 0) = 0');
        } elseif ($financialStatus === 'billed') {
            $studentsQuery->whereRaw('COALESCE(disbursed_rows_agg.disbursed_rows, 0) = 0 AND COALESCE(billing_rows_agg.billing_rows, 0) > 0');
        } elseif ($financialStatus === 'disbursed') {
            $studentsQuery->whereRaw('COALESCE(disbursed_rows_agg.disbursed_rows, 0) > 0');
        }

        $students = $studentsQuery
            ->orderByDesc('student.id')
            ->limit(500)
            ->get();

        $stats = [
            'total' => Schema::hasTable('student') ? (int) DB::table('student')->count() : 0,
            'active' => Schema::hasTable('student') ? (int) DB::table('student')->where('delete_status', '0')->count() : 0,
            'inactive' => Schema::hasTable('student') ? (int) DB::table('student')->where('delete_status', '1')->count() : 0,
        ];

        return view('scholarship.students.index', [
            'students' => $students,
            'search' => $search,
            'status' => in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all',
            'financialStatus' => $financialStatus,
            'stats' => $stats,
            'importSummary' => session('importSummary'),
        ]);
    }

    public function report(Request $request)
    {
        $this->bootstrapStudentStructures();

        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $program = trim((string) $request->query('program', ''));
        $academicYear = trim((string) $request->query('academic_year', ''));
        $semester = trim((string) $request->query('semester', ''));

        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $baseQuery = DB::table('student');

        if ($search !== '') {
            $baseQuery->where(function ($builder) use ($search) {
                $like = '%' . $search . '%';
                $builder->where('student_id_no', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('given_name', 'like', $like)
                    ->orWhere('middle_initial', 'like', $like)
                    ->orWhere('degree_program', 'like', $like)
                    ->orWhere('scholarship_program', 'like', $like)
                    ->orWhere('contact', 'like', $like)
                    ->orWhere('emailid', 'like', $like)
                    ->orWhere('sname', 'like', $like);
            });
        }

        if ($status === 'active') {
            $baseQuery->where('delete_status', '0');
        } elseif ($status === 'inactive') {
            $baseQuery->where('delete_status', '1');
        }

        if ($program !== '') {
            $baseQuery->whereRaw("COALESCE(NULLIF(TRIM(scholarship_program), ''), NULLIF(TRIM(degree_program), '')) = ?", [$program]);
        }

        if ($academicYear !== '') {
            $baseQuery->whereRaw("COALESCE(TRIM(scholarship_academic_year), '') = ?", [$academicYear]);
        }

        if ($semester !== '') {
            $baseQuery->where('scholarship_semester', 'like', '%' . $semester . '%');
        }

        $studentsQuery = clone $baseQuery;
        $this->applyFinancialStatusSelects($studentsQuery);

        $students = $studentsQuery
            ->orderBy('student.sname')
            ->orderBy('student.id')
            ->limit(1500)
            ->get();

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) AS row_count, COALESCE(SUM(fees), 0) AS total_fees, COALESCE(SUM(balance), 0) AS total_balance')
            ->first();

        $summary = [
            'rows' => (int) ($summaryRow->row_count ?? 0),
            'active_rows' => (int) (clone $baseQuery)->where('delete_status', '0')->count(),
            'inactive_rows' => (int) (clone $baseQuery)->where('delete_status', '1')->count(),
            'total_fees' => (float) ($summaryRow->total_fees ?? 0),
            'total_balance' => (float) ($summaryRow->total_balance ?? 0),
        ];

        $programOptions = $this->getProgramOptions();
        $academicYearOptions = $this->getAcademicYearOptions();
        $semesterOptions = $this->getSemesterOptions();

        if (Schema::hasTable('student')) {
            $studentPrograms = DB::table('student')
                ->selectRaw("DISTINCT COALESCE(NULLIF(TRIM(scholarship_program), ''), NULLIF(TRIM(degree_program), '')) AS program_name")
                ->whereRaw("COALESCE(NULLIF(TRIM(scholarship_program), ''), NULLIF(TRIM(degree_program), '')) IS NOT NULL")
                ->pluck('program_name')
                ->map(function ($value) {
                    return trim((string) $value);
                })
                ->filter(function ($value) {
                    return $value !== '';
                })
                ->values()
                ->all();

            $studentAcademicYears = DB::table('student')
                ->whereRaw("COALESCE(TRIM(scholarship_academic_year), '') <> ''")
                ->distinct()
                ->orderByDesc('scholarship_academic_year')
                ->pluck('scholarship_academic_year')
                ->map(function ($value) {
                    return trim((string) $value);
                })
                ->filter(function ($value) {
                    return $value !== '';
                })
                ->values()
                ->all();

            $studentSemesters = DB::table('student')
                ->whereRaw("COALESCE(TRIM(scholarship_semester), '') <> ''")
                ->distinct()
                ->orderBy('scholarship_semester')
                ->pluck('scholarship_semester')
                ->map(function ($value) {
                    return trim((string) $value);
                })
                ->filter(function ($value) {
                    return $value !== '';
                })
                ->values()
                ->all();

            $programOptions = array_values(array_unique(array_merge($programOptions, $studentPrograms)));
            $academicYearOptions = array_values(array_unique(array_merge($academicYearOptions, $studentAcademicYears)));
            $semesterOptions = array_values(array_unique(array_merge($semesterOptions, $studentSemesters)));

            sort($programOptions, SORT_NATURAL | SORT_FLAG_CASE);
            rsort($academicYearOptions, SORT_NATURAL | SORT_FLAG_CASE);
            sort($semesterOptions, SORT_NATURAL | SORT_FLAG_CASE);
        }

        if ($program !== '' && !in_array($program, $programOptions, true)) {
            array_unshift($programOptions, $program);
        }

        if ($academicYear !== '' && !in_array($academicYear, $academicYearOptions, true)) {
            array_unshift($academicYearOptions, $academicYear);
        }

        if ($semester !== '' && !in_array($semester, $semesterOptions, true)) {
            array_unshift($semesterOptions, $semester);
        }

        return view('scholarship.students.report', [
            'students' => $students,
            'search' => $search,
            'status' => $status,
            'program' => $program,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'summary' => $summary,
            'programOptions' => $programOptions,
            'academicYearOptions' => $academicYearOptions,
            'semesterOptions' => $semesterOptions,
        ]);
    }

    public function create()
    {
        $this->bootstrapStudentStructures();

        return view('scholarship.students.form', $this->buildFormViewData());
    }

    public function store(Request $request)
    {
        $this->bootstrapStudentStructures();

        $payload = $this->validateStudentPayload($request);

        DB::table('student')->insert(array_merge($payload, [
            'delete_status' => '0',
        ]));

        return redirect()
            ->route('scholarship-students.index')
            ->with('success', 'Student record has been added.');
    }

    public function show($student)
    {
        $this->bootstrapStudentStructures();

        $studentId = (int) $student;
        $record = DB::table('student')->where('id', $studentId)->first();
        if (!$record) {
            abort(404);
        }

        $billingLedger = [
            'total' => 0.0,
            'rows' => 0,
            'history' => [],
        ];

        if (Schema::hasTable('fees_transaction')) {
            $row = DB::table('fees_transaction')
                ->selectRaw("COALESCE(SUM(paid), 0) AS total_billing, COUNT(*) AS billing_rows")
                ->where('stdid', $studentId)
                ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
                ->first();

            if ($row) {
                $billingLedger['total'] = (float) ($row->total_billing ?? 0);
                $billingLedger['rows'] = (int) ($row->billing_rows ?? 0);
            }

            $billingLedger['history'] = DB::table('fees_transaction')
                ->leftJoin('disbursed_transaction', function ($join) {
                    $join->on('fees_transaction.stdid', '=', 'disbursed_transaction.stdid')
                        ->on('fees_transaction.billing_batch_id', '=', 'disbursed_transaction.billing_batch_id')
                        ->where('disbursed_transaction.disbursed_status', '=', 'finalized');
                })
                ->select('fees_transaction.*', 'disbursed_transaction.disbursed_status', 'disbursed_transaction.disbursed_date as finalized_date')
                ->where('fees_transaction.stdid', $studentId)
                ->whereRaw("COALESCE(fees_transaction.record_type, 'billing') = 'billing'")
                ->orderByDesc('fees_transaction.id')
                ->get();
        }

        return view('scholarship.students.show', [
            'student' => $record,
            'billingLedger' => $billingLedger,
        ]);
    }

    public function edit($student)
    {
        $this->bootstrapStudentStructures();

        $studentId = (int) $student;
        $record = DB::table('student')->where('id', $studentId)->first();
        if (!$record) {
            abort(404);
        }

        $billingLedger = [
            'total' => 0.0,
            'rows' => 0,
        ];

        if (Schema::hasTable('fees_transaction')) {
            $row = DB::table('fees_transaction')
                ->selectRaw("COALESCE(SUM(paid), 0) AS total_billing, COUNT(*) AS billing_rows")
                ->where('stdid', $studentId)
                ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
                ->first();

            if ($row) {
                $billingLedger['total'] = (float) ($row->total_billing ?? 0);
                $billingLedger['rows'] = (int) ($row->billing_rows ?? 0);
            }
        }

        return view('scholarship.students.form', $this->buildFormViewData($record, $billingLedger));
    }

    public function update(Request $request, $student)
    {
        $this->bootstrapStudentStructures();

        $studentId = (int) $student;
        $existing = DB::table('student')->where('id', $studentId)->first();
        if (!$existing) {
            abort(404);
        }

        $payload = $this->validateStudentPayload($request, $studentId);

        DB::table('student')->where('id', $studentId)->update($payload);

        return redirect()
            ->route('scholarship-students.index')
            ->with('success', 'Student record has been updated.');
    }

    public function toggleStatus(Request $request, $student)
    {
        $this->bootstrapStudentStructures();

        $targetStatus = (string) $request->input('target_status', '');
        if (!in_array($targetStatus, ['0', '1'], true)) {
            return redirect()
                ->route('scholarship-students.index')
                ->with('success', 'Unable to update student status.');
        }

        $studentId = (int) $student;
        $updated = DB::table('student')->where('id', $studentId)->update([
            'delete_status' => $targetStatus,
        ]);

        if ($updated) {
            return redirect()
                ->route('scholarship-students.index')
                ->with('success', $targetStatus === '1' ? 'Student has been set to inactive.' : 'Student has been set to active.');
        }

        return redirect()
            ->route('scholarship-students.index')
            ->with('success', 'Unable to update student status.');
    }

    public function remove(Request $request, $student)
    {
        $this->bootstrapStudentStructures();

        $studentId = (int) $student;
        $record = DB::table('student')->where('id', $studentId)->first();
        if (!$record) {
            return redirect()->route('scholarship-students.index')->with('success', 'Unable to complete the requested action.');
        }

        if ((string) ($record->delete_status ?? '0') !== '1') {
            DB::table('student')->where('id', $studentId)->update(['delete_status' => '1']);

            return redirect()->route('scholarship-students.index')->with('success', 'Student has been set to inactive.');
        }

        $feesDeleted = 0;
        $disbursedDeleted = 0;

        DB::beginTransaction();
        try {
            if (Schema::hasTable('fees_transaction')) {
                $feesDeleted = (int) DB::table('fees_transaction')->where('stdid', $studentId)->count();
                if ($feesDeleted > 0) {
                    DB::table('fees_transaction')->where('stdid', $studentId)->delete();
                }
            }

            if (Schema::hasTable('disbursed_transaction')) {
                $disbursedDeleted = (int) DB::table('disbursed_transaction')->where('stdid', $studentId)->count();
                if ($disbursedDeleted > 0) {
                    DB::table('disbursed_transaction')->where('stdid', $studentId)->delete();
                }
            }

            DB::table('student')->where('id', $studentId)->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('scholarship-students.index')->with('success', 'Unable to complete the requested action.');
        }

        if ($feesDeleted > 0 || $disbursedDeleted > 0) {
            return redirect()->route('scholarship-students.index')->with('success', 'Student permanently deleted. Cleaned linked rows: Billing = ' . $feesDeleted . ', Disbursed = ' . $disbursedDeleted . '.');
        }

        return redirect()->route('scholarship-students.index')->with('success', 'Student has been permanently deleted.');
    }

    public function downloadTemplate()
    {
        $defaultAcademicYear = $this->getDefaultAcademicYear();

        $response = new StreamedResponse(function () use ($defaultAcademicYear) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'student_id',
                'last_name',
                'first_name',
                'middle_initial',
                'degree_program',
                'year_level',
                'pwd_no',
                'ip_no',
                'email',
                'contact',
                'fb_link',
            ]);

            fputcsv($out, [
                '202203733',
                'Vergara',
                'Genderson',
                'V',
                'BS Information Technology',
                '2nd Year',
                'N/A',
                'N/A',
                'student@example.com',
                '09171234567',
                'https://facebook.com/profile',
            ]);

            fclose($out);
        });

        $filename = 'student_import_template.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);

        return $response;
    }

    public function import(Request $request)
    {
        $this->bootstrapStudentStructures();

        $request->validate([
            'students_csv' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('students_csv');
        $summary = $this->importStudentsCsvRows($file, $this->getDefaultAcademicYear());

        $skippedRows = (int) ($summary['skipped'] ?? 0);
        $filledRows = (int) ($summary['filled'] ?? 0);
        $successfulRows = (int) (($summary['added'] ?? 0) + ($summary['updated'] ?? 0));
        $failedRows = (int) ($summary['failed'] ?? 0);
        $status = 'completed';
        if ($failedRows > 0 && $successfulRows > 0) {
            $status = 'warning';
        } elseif ($failedRows > 0 && $successfulRows <= 0) {
            $status = 'failed';
        }

        ScholarshipMonitoring::logUploadHistory([
            'module_name' => 'student_import',
            'upload_type' => 'student_csv',
            'file_name' => (string) $file->getClientOriginalName(),
            'file_path' => '',
            'uploaded_by' => (int) (Auth::id() ?? 0),
            'records_processed' => $successfulRows + $failedRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'duplicates_skipped' => $this->countDuplicateErrorMessages((array) ($summary['errors'] ?? [])),
            'status' => $status,
            'summary' => 'Added: ' . (int) ($summary['added'] ?? 0) . ', Updated: ' . (int) ($summary['updated'] ?? 0) . ', Filled missing info: ' . $filledRows . ', Skipped (no new data): ' . $skippedRows . ', Failed: ' . $failedRows . '.',
        ]);

        if ($summary['failed'] === 0 && count($summary['errors']) === 0) {
            $msg = 'Student import complete. Added: ' . $summary['added'] . ', Updated: ' . $summary['updated'] . '.';
            if ($filledRows > 0) {
                $msg .= ' Filled missing info: ' . $filledRows . '.';
            }
            if ($skippedRows > 0) {
                $msg .= ' Skipped (already complete): ' . $skippedRows . '.';
            }
            return redirect()
                ->route('scholarship-students.index')
                ->with('success', $msg);
        }

        $msg = 'Student import finished with issues. Added: ' . $summary['added'] . ', Updated: ' . $summary['updated'] . ', Failed: ' . $summary['failed'] . '.';
        if ($filledRows > 0) {
            $msg .= ' Filled missing info: ' . $filledRows . '.';
        }
        if ($skippedRows > 0) {
            $msg .= ' Skipped (already complete): ' . $skippedRows . '.';
        }
        return redirect()
            ->route('scholarship-students.index')
            ->with('importSummary', $summary)
            ->with('success', $msg);
    }

    private function applyFinancialStatusSelects($query)
    {
        $query->select('student.*');

        $billingRowsExpr = '0';
        if (Schema::hasTable('fees_transaction')) {
            $billingSubquery = DB::table('fees_transaction')
                ->selectRaw('stdid, COUNT(*) AS billing_rows, SUM(CASE WHEN conflict_status = \'scholarship_conflict\' THEN 1 ELSE 0 END) AS conflict_rows')
                ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
                ->groupBy('stdid');

            $query->leftJoinSub($billingSubquery, 'billing_rows_agg', function ($join) {
                $join->on('billing_rows_agg.stdid', '=', 'student.id');
            });

            $billingRowsExpr = 'COALESCE(billing_rows_agg.billing_rows, 0)';
            $conflictRowsExpr = 'COALESCE(billing_rows_agg.conflict_rows, 0)';
            $query->addSelect(DB::raw($billingRowsExpr . ' AS billing_rows'));
            $query->addSelect(DB::raw($conflictRowsExpr . ' AS conflict_rows'));
        } else {
            $query->addSelect(DB::raw('0 AS billing_rows'));
            $query->addSelect(DB::raw('0 AS conflict_rows'));
        }

        $disbursedRowsExpr = '0';
        if (Schema::hasTable('disbursed_transaction')) {
            $disbursedSubquery = DB::table('disbursed_transaction')
                ->selectRaw('stdid, COUNT(*) AS disbursed_rows')
                ->whereRaw("COALESCE(disbursed_status, 'draft') = 'finalized'")
                ->groupBy('stdid');

            $query->leftJoinSub($disbursedSubquery, 'disbursed_rows_agg', function ($join) {
                $join->on('disbursed_rows_agg.stdid', '=', 'student.id');
            });

            $disbursedRowsExpr = 'COALESCE(disbursed_rows_agg.disbursed_rows, 0)';
            $query->addSelect(DB::raw($disbursedRowsExpr . ' AS disbursed_rows'));
        } else {
            $query->addSelect(DB::raw('0 AS disbursed_rows'));
        }

        $query->addSelect(DB::raw("CASE
            WHEN (COALESCE(billing_rows_agg.conflict_rows, 0)) > 0 THEN 'conflict'
            WHEN {$disbursedRowsExpr} > 0 THEN 'disbursed'
            WHEN {$billingRowsExpr} > 0 THEN 'billed'
            ELSE 'not_billed'
        END AS financial_status"));
    }

    private function buildFormViewData($student = null, array $billingLedger = null)
    {
        $billingLedger = $billingLedger ?: ['total' => 0.0, 'rows' => 0];

        $joinDateForm = Carbon::now()->format('Y-m-d');
        if ($student && !empty($student->joindate)) {
            try {
                $joinDateForm = Carbon::parse($student->joindate)->format('Y-m-d');
            } catch (\Throwable $e) {
                $joinDateForm = Carbon::now()->format('Y-m-d');
            }
        }

        return [
            'student' => $student,
            'joinDateForm' => $joinDateForm,
            'programOptions' => $this->getProgramOptions(),
            'academicYearOptions' => $this->getAcademicYearOptions(),
            'semesterOptions' => $this->getSemesterOptions(),
            'yearLevelOptions' => $this->getYearLevelOptions(),
            'billingLedger' => $billingLedger,
        ];
    }

    private function validateStudentPayload(Request $request, $ignoreId = null)
    {
        if ($request->has('fb_link')) {
            $request->merge(['fb_link' => trim((string) $request->fb_link)]);
        }

        // Normalize Philippine contact numbers before validation
        foreach (['contact', 'guardian_contact'] as $field) {
            if ($request->has($field)) {
                $val = trim((string) $request->input($field));
                // Remove spaces and dashes for better processing
                $val = str_replace([' ', '-', '(', ')'], '', $val);

                // 9XXXXXXXXX (10 digits) -> 09XXXXXXXXX
                if (preg_match('/^9[0-9]{9}$/', $val)) {
                    $val = '0' . $val;
                }
                // +639XXXXXXXXX (13 chars) -> 09XXXXXXXXX
                elseif (preg_match('/^\+639[0-9]{9}$/', $val)) {
                    $val = '09' . substr($val, 4);
                }
                // 639XXXXXXXXX (12 digits) -> 09XXXXXXXXX
                elseif (preg_match('/^639[0-9]{9}$/', $val)) {
                    $val = '09' . substr($val, 2);
                }
                
                $request->merge([$field => $val]);
            }
        }

        $validated = $request->validate([
            'tdp_tes_award_no' => 'nullable|string|max:50',
            'student_id_no' => [
                'required',
                'string',
                'size:9',
                'regex:/^[0-9]{9}$/',
                'unique:student,student_id_no,' . ($ignoreId ?: 'NULL')
            ],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[^0-9]+$/'],
            'given_name' => ['required', 'string', 'max:100', 'regex:/^[^0-9]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[^0-9]*$/'],
            'degree_program' => 'nullable|string|max:150',
            'scholarship_program' => 'nullable|string|max:150',
            'scholarship_semester' => 'nullable|string|max:60',
            'scholarship_academic_year' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'school_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_contact' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/'],
            'fb_link' => 'nullable|string|max:255',
            'contact' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/'],
            'pwd_no' => ['nullable', 'string', 'max:50', 'regex:/^(N\/A|\d{2}-\d{4}-\d{3}-\d{7})$/i'],
            'ip_no' => 'nullable|string|max:50',
            'year_level' => 'nullable|string|max:255',
            'joindate' => 'required|date',
            'about' => 'nullable|string',
            'emailid' => 'nullable|email|max:255',
            'fees' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric|min:0',
        ], [
            'last_name.regex' => 'The last name cannot contain numbers.',
            'given_name.regex' => 'The given name cannot contain numbers.',
            'middle_initial.regex' => 'The middle initial cannot contain numbers.',
            'pwd_no.regex' => 'The PWD ID must follow the format RR-PPMM-BBB-NNNNNNN (e.g., 13-7401-000-0000001) or N/A.',
            'student_id_no.unique' => 'This Student ID Number is already registered in the system.',
            'contact.regex' => 'The contact number must be a valid Philippine mobile number (e.g., 09123456789).',
            'guardian_contact.regex' => 'The guardian contact must be a valid Philippine mobile number (e.g., 09123456789).',
            'student_id_no.size' => 'The Student ID must be exactly 9 digits (YYYYXXXXX).',
            'student_id_no.regex' => 'The Student ID must contain only numbers.',
        ]);

        // Manual uniqueness check for FB link to ensure it works in all environments
        $fbLink = $validated['fb_link'] ?? '';
        if ($fbLink !== '' && strtoupper($fbLink) !== 'N/A') {
            $existsQuery = DB::table('student')->where('fb_link', $fbLink);
            if ($ignoreId) {
                $existsQuery->where('id', '!=', (int) $ignoreId);
            }
            if ($existsQuery->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fb_link' => ['This Facebook link is already associated with another student.']
                ]);
            }
        }

        $validated['joindate'] = $this->parseDateTimeOrFallback($validated['joindate'], Carbon::now()->format('Y-m-d H:i:s'));
        $validated['degree_program'] = trim((string) ($validated['degree_program'] ?? ''));
        $validated['scholarship_program'] = trim((string) ($validated['scholarship_program'] ?? ''));
        [$validated['degree_program'], $validated['scholarship_program']] = $this->normalizeProgramPair(
            $validated['degree_program'],
            $validated['scholarship_program']
        );
        $validated['scholarship_academic_year'] = $this->pickNonEmpty(
            trim((string) ($validated['scholarship_academic_year'] ?? '')),
            $this->getDefaultAcademicYear()
        );
        $validated['pwd_no'] = $this->pickNonEmpty(trim((string) ($validated['pwd_no'] ?? '')), 'N/A');
        $validated['ip_no'] = $this->pickNonEmpty(trim((string) ($validated['ip_no'] ?? '')), 'N/A');
        $validated['middle_initial'] = trim((string) ($validated['middle_initial'] ?? ''));
        $validated['address'] = trim((string) ($validated['address'] ?? ''));
        $validated['school_name'] = trim((string) ($validated['school_name'] ?? ''));
        $validated['school_name'] = $this->pickNonEmpty($validated['school_name'], $validated['degree_program']);
        $validated['guardian_name'] = trim((string) ($validated['guardian_name'] ?? ''));
        $validated['guardian_contact'] = trim((string) ($validated['guardian_contact'] ?? ''));
        $validated['contact'] = trim((string) ($validated['contact'] ?? ''));
        $validated['fb_link'] = trim((string) ($validated['fb_link'] ?? ''));
        $birthdate = $this->parseDateValue((string) ($validated['birthdate'] ?? ''));
        $validated['birthdate'] = $birthdate !== '' ? $birthdate : null;
        $validated['sname'] = $this->buildDisplayName(
            $validated['last_name'],
            $validated['given_name'],
            $validated['middle_initial'],
            $validated['student_id_no']
        );
        $validated['grade'] = $this->gradeFromYearLevel($validated['year_level']);

        $validated['about'] = trim((string) ($validated['about'] ?? ''));
        $validated['emailid'] = trim((string) ($validated['emailid'] ?? ''));
        $validated['fees'] = (float) ($validated['fees'] ?? 0);
        $validated['balance'] = (float) ($validated['balance'] ?? 0);

        return $validated;
    }

    private function importStudentsCsvRows(UploadedFile $file, $defaultAcademicYear)
    {
        $summary = [
            'added' => 0,
            'updated' => 0,
            'filled' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $realPath = $file->getRealPath();
        if (!is_string($realPath) || $realPath === '' || !is_file($realPath)) {
            $summary['errors'][] = 'Unable to read uploaded CSV file.';
            $summary['failed']++;

            return $summary;
        }

        $handle = fopen($realPath, 'r');
        if ($handle === false) {
            $summary['errors'][] = 'Unable to open uploaded CSV file.';
            $summary['failed']++;

            return $summary;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $summary['errors'][] = 'CSV file is empty.';
            $summary['failed']++;

            return $summary;
        }

        $headerMap = $this->normalizeHeaders($headers);
        $idIndex = $this->firstHeaderIndex($headerMap, ['id', 'student_db_id', 'student_internal_id', 'db_id']);
        $studentIdNoIndex = $this->firstHeaderIndex($headerMap, ['student_id_no', 'student_id', 'student_no', 'studentid', 'stdid', 'id_no', 'id_number']);

        if ($idIndex === null && $studentIdNoIndex === null) {
            fclose($handle);
            $summary['errors'][] = 'Missing identifier column. Include id or student_id/student_id_no in the CSV header.';
            $summary['failed']++;

            return $summary;
        }

        $nameIndex = $this->firstHeaderIndex($headerMap, ['name', 'full_name', 'student_name', 'sname', 'display_name']);
        $lastNameIndex = $this->firstHeaderIndex($headerMap, ['last_name', 'lastname', 'surname', 'last']);
        $givenNameIndex = $this->firstHeaderIndex($headerMap, ['given_name', 'first_name', 'firstname', 'given', 'first']);
        $middleInitialIndex = $this->firstHeaderIndex($headerMap, ['middle_initial', 'mi', 'middle_name', 'middle', 'initial']);
        $degreeProgramIndex = $this->firstHeaderIndex($headerMap, ['degree_program', 'course', 'program']);
        $scholarshipProgramIndex = $this->firstHeaderIndex($headerMap, ['scholarship_program']);
        $yearLevelIndex = $this->firstHeaderIndex($headerMap, ['year_level', 'year', 'grade']);
        $feesIndex = $this->firstHeaderIndex($headerMap, ['fees', 'amount', 'billing_amount', 'total_fees']);
        $contactIndex = $this->firstHeaderIndex($headerMap, ['contact', 'phone', 'mobile']);
        $emailIndex = $this->firstHeaderIndex($headerMap, ['email', 'emailid']);
        $joinDateIndex = $this->firstHeaderIndex($headerMap, ['joindate', 'join_date', 'date_joined', 'date_of_joining']);
        $scholarshipSemesterIndex = $this->firstHeaderIndex($headerMap, ['scholarship_semester', 'semester', 'sem']);
        $academicYearIndex = $this->firstHeaderIndex($headerMap, ['scholarship_academic_year', 'academic_year', 'ay']);
        $awardNoIndex = $this->firstHeaderIndex($headerMap, ['tdp_tes_award_no', 'award_no']);
        $pwdNoIndex = $this->firstHeaderIndex($headerMap, ['pwd_no']);
        $ipNoIndex = $this->firstHeaderIndex($headerMap, ['ip_no']);
        $fbLinkIndex = $this->firstHeaderIndex($headerMap, ['fb_link', 'facebook', 'facebook_link', 'fb', 'profile', 'facebook_profile', 'social', 'facebook_url', 'facebook_account', 'fb_account']);
        $aboutIndex = $this->firstHeaderIndex($headerMap, ['about', 'remark', 'notes']);
        $addressIndex = $this->firstHeaderIndex($headerMap, ['address', 'home_address']);
        $birthdateIndex = $this->firstHeaderIndex($headerMap, ['birthdate', 'date_of_birth', 'dob']);
        $schoolNameIndex = $this->firstHeaderIndex($headerMap, ['school_name', 'school', 'institution']);
        $guardianNameIndex = $this->firstHeaderIndex($headerMap, ['guardian_name', 'guardian']);
        $guardianContactIndex = $this->firstHeaderIndex($headerMap, ['guardian_contact', 'guardian_phone']);

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $csvInternalId = $this->csvValue($row, $idIndex);
            $csvStudentIdNo = $this->csvValue($row, $studentIdNoIndex);

            if ($csvInternalId === '' && $csvStudentIdNo === '') {
                $summary['failed']++;
                $summary['errors'][] = 'Line ' . $rowNumber . ': missing id or student_id value.';
                continue;
            }

            $existing = null;
            if ($csvInternalId !== '' && ctype_digit($csvInternalId)) {
                $existing = DB::table('student')->where('id', (int) $csvInternalId)->first();
            }
            if (!$existing && $csvStudentIdNo !== '') {
                $existing = DB::table('student')->where('student_id_no', $csvStudentIdNo)->first();
            }

            $fullName = $this->csvValue($row, $nameIndex);
            $lastName = $this->csvValue($row, $lastNameIndex);
            $givenName = $this->csvValue($row, $givenNameIndex);
            $middleInitial = $this->csvValue($row, $middleInitialIndex);

            if (($lastName === '' || $givenName === '') && $fullName !== '') {
                $parsed = $this->parseName($fullName);
                if ($lastName === '') {
                    $lastName = $parsed['last_name'];
                }
                if ($givenName === '') {
                    $givenName = $parsed['given_name'];
                }
                if ($middleInitial === '' && $parsed['middle_initial'] !== '') {
                    $middleInitial = $parsed['middle_initial'];
                }
            }

            if (preg_match('/[0-9]/', (string) $lastName) || preg_match('/[0-9]/', (string) $givenName)) {
                $summary['failed']++;
                $summary['errors'][] = 'Line ' . $rowNumber . ': Last name and Given name cannot contain numbers (' . $lastName . ' ' . $givenName . ').';
                continue;
            }

            $degreeProgram = $this->csvValue($row, $degreeProgramIndex);
            $scholarshipProgram = $this->csvValue($row, $scholarshipProgramIndex);
            $yearLevel = $this->normalizeYearLevel($this->csvValue($row, $yearLevelIndex));
            $feesParsed = $this->parseIntOrNull($this->csvValue($row, $feesIndex));
            $contact = $this->csvValue($row, $contactIndex);
            $email = $this->csvValue($row, $emailIndex);
            $joinDateParsed = $this->parseDateTimeOrFallback($this->csvValue($row, $joinDateIndex), '');
            $scholarshipSemester = $this->csvValue($row, $scholarshipSemesterIndex);
            $scholarshipAcademicYear = $this->csvValue($row, $academicYearIndex);
            $awardNo = $this->csvValue($row, $awardNoIndex);
            $pwdNo = $this->csvValue($row, $pwdNoIndex);
            $ipNo = $this->csvValue($row, $ipNoIndex);
            $fbLink = $this->csvValue($row, $fbLinkIndex);
            $about = $this->csvValue($row, $aboutIndex);
            $address = $this->csvValue($row, $addressIndex);
            $birthdateRaw = $this->csvValue($row, $birthdateIndex);
            $schoolName = $this->csvValue($row, $schoolNameIndex);
            $guardianName = $this->csvValue($row, $guardianNameIndex);
            $guardianContact = $this->csvValue($row, $guardianContactIndex);

            if ($pwdNo !== '' && $pwdNo !== 'N/A' && !preg_match('/^\d{2}-\d{4}-\d{3}-\d{7}$/', $pwdNo)) {
                $summary['failed']++;
                $summary['errors'][] = 'Line ' . $rowNumber . ': Invalid PWD ID format (' . $pwdNo . '). Expected RR-PPMM-BBB-NNNNNNN.';
                continue;
            }

            if ($existing) {
                $existingArr = (array) $existing;

                $studentIdNo = $this->pickNonEmpty($csvStudentIdNo, (string) ($existingArr['student_id_no'] ?? ''));
                if ($studentIdNo === '') {
                    $summary['failed']++;
                    $summary['errors'][] = 'Line ' . $rowNumber . ': cannot update without student ID number.';
                    continue;
                }

                $lastName = $this->pickNonEmpty($lastName, (string) ($existingArr['last_name'] ?? ''));
                $givenName = $this->pickNonEmpty($givenName, (string) ($existingArr['given_name'] ?? ''));
                $middleInitial = $this->pickNonEmpty($middleInitial, (string) ($existingArr['middle_initial'] ?? ''));
                $degreeProgram = $this->pickNonEmpty($degreeProgram, (string) ($existingArr['degree_program'] ?? ''));
                $scholarshipProgram = $this->pickNonEmpty($scholarshipProgram, (string) ($existingArr['scholarship_program'] ?? ''));
                [$degreeProgram, $scholarshipProgram] = $this->normalizeProgramPair($degreeProgram, $scholarshipProgram);
                $scholarshipSemester = $this->pickNonEmpty($scholarshipSemester, (string) ($existingArr['scholarship_semester'] ?? ''));
                $scholarshipAcademicYear = $this->pickNonEmpty($scholarshipAcademicYear, $this->pickNonEmpty((string) ($existingArr['scholarship_academic_year'] ?? ''), $defaultAcademicYear));
                $yearLevel = $this->pickNonEmpty($yearLevel, $this->pickNonEmpty((string) ($existingArr['year_level'] ?? ''), (string) ($existingArr['grade'] ?? '')));
                $grade = $this->gradeFromYearLevel($yearLevel);
                $fees = $feesParsed === null ? (int) ($existingArr['fees'] ?? 0) : max(0, $feesParsed);
                $joinDate = $this->pickNonEmpty($joinDateParsed, $this->pickNonEmpty((string) ($existingArr['joindate'] ?? ''), Carbon::now()->format('Y-m-d H:i:s')));
                $contact = $this->pickNonEmpty($contact, (string) ($existingArr['contact'] ?? ''));
                $email = $this->pickNonEmpty($email, (string) ($existingArr['emailid'] ?? ''));
                $awardNo = $this->pickNonEmpty($awardNo, (string) ($existingArr['tdp_tes_award_no'] ?? ''));
                $pwdNo = $this->pickNonEmpty($pwdNo, (string) ($existingArr['pwd_no'] ?? ''));
                $ipNo = $this->pickNonEmpty($ipNo, (string) ($existingArr['ip_no'] ?? ''));
                $pwdNo = $this->pickNonEmpty($pwdNo, 'N/A');
                $ipNo = $this->pickNonEmpty($ipNo, 'N/A');
                $fbLink = $this->pickNonEmpty($fbLink, (string) ($existingArr['fb_link'] ?? ''));
                $about = $this->pickNonEmpty($about, (string) ($existingArr['about'] ?? ''));
                $address = $this->pickNonEmpty($address, (string) ($existingArr['address'] ?? ''));
                $guardianName = $this->pickNonEmpty($guardianName, (string) ($existingArr['guardian_name'] ?? ''));
                $guardianContact = $this->pickNonEmpty($guardianContact, (string) ($existingArr['guardian_contact'] ?? ''));
                $schoolName = $this->pickNonEmpty($schoolName, (string) ($existingArr['school_name'] ?? ''));
                $schoolName = $this->pickNonEmpty($schoolName, $degreeProgram);
                $birthdateParsed = $this->parseDateValue($birthdateRaw);
                $existingBirthdate = trim((string) ($existingArr['birthdate'] ?? ''));
                $birthdate = ($birthdateParsed !== '' ? $birthdateParsed : ($existingBirthdate !== '' ? $existingBirthdate : null));
                $displayName = $this->buildDisplayName($lastName, $givenName, $middleInitial, $studentIdNo, $fullName);

                // Track how many fields were actually filled (were empty and now have a value)
                $fieldsFilled = 0;
                $fillableChecks = [
                    ['contact', $contact],
                    ['emailid', $email],
                    ['fb_link', $fbLink],
                    ['about', $about],
                    ['address', $address],
                    ['guardian_name', $guardianName],
                    ['guardian_contact', $guardianContact],
                    ['school_name', $schoolName],
                    ['tdp_tes_award_no', $awardNo],
                    ['year_level', $yearLevel],
                    ['scholarship_semester', $scholarshipSemester],
                    ['degree_program', $degreeProgram],
                    ['scholarship_program', $scholarshipProgram],
                ];
                foreach ($fillableChecks as [$col, $newVal]) {
                    $oldVal = trim((string) ($existingArr[$col] ?? ''));
                    $newValTrimmed = trim((string) $newVal);
                    
                    // If old was empty and new is not, it's a 'fill'
                    if (ScholarshipMonitoring::isEmptyValue($oldVal) && !ScholarshipMonitoring::isEmptyValue($newValTrimmed)) {
                        $fieldsFilled++;
                    } 
                    // If both have values but they are different, it's an 'update'
                    elseif (!ScholarshipMonitoring::isEmptyValue($newValTrimmed) && $newValTrimmed !== $oldVal) {
                        $fieldsFilled++;
                    }
                }
                // Also check birthdate separately
                if (($existingBirthdate === '' || $existingBirthdate === null) && $birthdateParsed !== '') {
                    $fieldsFilled++;
                }

                // If no fields were actually filled with new data, skip this student
                if ($fieldsFilled === 0) {
                    $summary['skipped']++;
                    continue;
                }

                try {
                    $updatePayload = [
                        'delete_status' => '0',
                        'tdp_tes_award_no' => $awardNo,
                        'student_id_no' => $studentIdNo,
                        'last_name' => $lastName,
                        'given_name' => $givenName,
                        'middle_initial' => $middleInitial,
                        'degree_program' => $degreeProgram,
                        'scholarship_program' => $scholarshipProgram,
                        'scholarship_semester' => $scholarshipSemester,
                        'scholarship_academic_year' => $scholarshipAcademicYear,
                        'pwd_no' => $pwdNo,
                        'ip_no' => $ipNo,
                        'fb_link' => $fbLink,
                        'joindate' => $joinDate,
                        'contact' => $contact,
                        'about' => $about,
                        'emailid' => $email,
                        'sname' => $displayName,
                        'year_level' => $yearLevel,
                        'grade' => $grade,
                        'fees' => $fees,
                        'address' => $address,
                        'guardian_name' => $guardianName,
                        'guardian_contact' => $guardianContact,
                        'school_name' => $schoolName,
                    ];
                    if ($birthdate !== null) {
                        $updatePayload['birthdate'] = $birthdate;
                    }
                    DB::table('student')->where('id', (int) $existingArr['id'])->update($updatePayload);
                    $summary['updated']++;
                    $summary['filled'] += $fieldsFilled;
                } catch (\Throwable $e) {
                    $summary['failed']++;
                    $summary['errors'][] = 'Line ' . $rowNumber . ': update failed.';
                }

                continue;
            }

            $studentIdNo = trim((string) $csvStudentIdNo);
            if ($studentIdNo === '') {
                $summary['failed']++;
                $summary['errors'][] = 'Line ' . $rowNumber . ': student_id is required for new students.';
                continue;
            }

            $degreeProgram = $this->pickNonEmpty($degreeProgram, '');
            $scholarshipProgram = $this->pickNonEmpty($scholarshipProgram, $degreeProgram);
            [$degreeProgram, $scholarshipProgram] = $this->normalizeProgramPair($degreeProgram, $scholarshipProgram);
            $yearLevel = $this->pickNonEmpty($yearLevel, '');
            $grade = $this->gradeFromYearLevel($yearLevel);
            $fees = $feesParsed === null ? 0 : max(0, $feesParsed);
            $balance = $fees;
            $joinDate = $this->pickNonEmpty($joinDateParsed, Carbon::now()->format('Y-m-d H:i:s'));
            $scholarshipSemester = $this->pickNonEmpty($scholarshipSemester, '');
            $scholarshipAcademicYear = $this->pickNonEmpty($scholarshipAcademicYear, $defaultAcademicYear);
            $pwdNo = $this->pickNonEmpty($pwdNo, 'N/A');
            $ipNo = $this->pickNonEmpty($ipNo, 'N/A');
            $displayName = $this->buildDisplayName($lastName, $givenName, $middleInitial, $studentIdNo, $fullName);

            $newBirthdate = $this->parseDateValue($birthdateRaw);

            try {
                $insertPayload = [
                    'tdp_tes_award_no' => $awardNo,
                    'student_id_no' => $studentIdNo,
                    'last_name' => $lastName,
                    'given_name' => $givenName,
                    'middle_initial' => $middleInitial,
                    'degree_program' => $degreeProgram,
                    'scholarship_program' => $scholarshipProgram,
                    'scholarship_semester' => $scholarshipSemester,
                    'scholarship_academic_year' => $scholarshipAcademicYear,
                    'pwd_no' => $pwdNo,
                    'ip_no' => $ipNo,
                    'fb_link' => $fbLink,
                    'joindate' => $joinDate,
                    'contact' => $contact,
                    'about' => $about,
                    'emailid' => $email,
                    'sname' => $displayName,
                    'year_level' => $yearLevel,
                    'grade' => $grade,
                    'balance' => $balance,
                    'fees' => $fees,
                    'delete_status' => '0',
                    'address' => $this->pickNonEmpty($address, ''),
                    'school_name' => $this->pickNonEmpty($schoolName, $degreeProgram),
                    'guardian_name' => $this->pickNonEmpty($guardianName, ''),
                    'guardian_contact' => $this->pickNonEmpty($guardianContact, ''),
                ];
                if ($newBirthdate !== '') {
                    $insertPayload['birthdate'] = $newBirthdate;
                }
                DB::table('student')->insert($insertPayload);

                $summary['added']++;
            } catch (\Throwable $e) {
                $summary['failed']++;
                $summary['errors'][] = 'Line ' . $rowNumber . ': insert failed.';
            }
        }

        fclose($handle);

        return $summary;
    }

    private function normalizeProgramPair($degreeProgram, $scholarshipProgram)
    {
        $degreeProgram = trim((string) $degreeProgram);
        $scholarshipProgram = trim((string) $scholarshipProgram);

        if ($degreeProgram === '' && $scholarshipProgram !== '') {
            $degreeProgram = $scholarshipProgram;
        }

        if ($scholarshipProgram === '' && $degreeProgram !== '') {
            $scholarshipProgram = $degreeProgram;
        }

        if ($degreeProgram === '' && $scholarshipProgram === '') {
            $degreeProgram = '';
            $scholarshipProgram = '';
        }

        return [$degreeProgram, $scholarshipProgram];
    }

    private function normalizeHeaderKey($header)
    {
        $header = trim((string) $header);
        if (substr($header, 0, 3) === "\xEF\xBB\xBF") {
            $header = substr($header, 3);
        }

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
            $key = $this->normalizeHeaderKey((string) $candidate);
            if (array_key_exists($key, $headerMap)) {
                return $headerMap[$key];
            }
        }

        return null;
    }

    private function csvValue(array $row, $index)
    {
        if ($index === null || !isset($row[$index])) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    private function pickNonEmpty($candidate, $fallback = '')
    {
        $candidate = trim((string) $candidate);
        if ($candidate !== '') {
            return $candidate;
        }

        return trim((string) $fallback);
    }

    private function parseIntOrNull($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '', $value);
        $value = preg_replace('/[^0-9\.-]/', '', $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }

    private function parseDateTimeOrFallback($value, $fallback)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $fallback;
        }
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
                // Last token is an initial (e.g., "Ian Christian C")
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

        // If the second to last token is a single character, it's likely a middle initial
        // Example: "Ian Christian C Maranga"
        if (strlen($tokens[$tokenCount - 2]) === 1) {
            $lastName = $tokens[$tokenCount - 1];
            $middleInitial = strtoupper($tokens[$tokenCount - 2]);
            unset($tokens[$tokenCount - 1]);
            unset($tokens[$tokenCount - 2]);
            $givenName = implode(' ', $tokens);
        } else {
            // Otherwise, assume last token is Last Name and everything else is Given Name
            // Example: "Ian Christian Maranga" -> Given: "Ian Christian", Last: "Maranga"
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

    private function normalizeYearLevel($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $normalized = strtolower(preg_replace('/\s+/', ' ', $value));
        $map = [
            '1' => '1st Year',
            '1st' => '1st Year',
            '1st year' => '1st Year',
            'first year' => '1st Year',
            '2' => '2nd Year',
            '2nd' => '2nd Year',
            '2nd year' => '2nd Year',
            'second year' => '2nd Year',
            '3' => '3rd Year',
            '3rd' => '3rd Year',
            '3rd year' => '3rd Year',
            'third year' => '3rd Year',
            '4' => '4th Year',
            '4th' => '4th Year',
            '4th year' => '4th Year',
            'fourth year' => '4th Year',
        ];

        return $map[$normalized] ?? $value;
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

    private function countDuplicateErrorMessages(array $errors)
    {
        $count = 0;
        foreach ($errors as $error) {
            if (stripos((string) $error, 'duplicate') !== false) {
                $count++;
            }
        }

        return $count;
    }

    private function buildDisplayName($lastName, $givenName, $middleInitial, $fallbackStudentId = '', $fallbackName = '')
    {
        $lastName = trim((string) $lastName);
        $givenName = trim((string) $givenName);
        $middleInitial = trim((string) $middleInitial);

        $displayName = $lastName;
        if ($givenName !== '') {
            $displayName .= ($displayName !== '' ? ', ' : '') . $givenName;
        }
        if ($middleInitial !== '') {
            $displayName .= ' ' . strtoupper(substr($middleInitial, 0, 1));
        }

        if ($displayName !== '') {
            return $displayName;
        }

        $fallbackName = trim((string) $fallbackName);
        if ($fallbackName !== '') {
            return $fallbackName;
        }

        return trim((string) $fallbackStudentId);
    }

    private function gradeFromYearLevel($yearLevel)
    {
        $yearLevel = trim((string) $yearLevel);
        if ($yearLevel === '') {
            return 0;
        }

        if (preg_match('/([1-9])/', $yearLevel, $matches)) {
            return (int) $matches[1];
        }

        return 0;
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

    private function getProgramOptions()
    {
        if (!Schema::hasTable('academic_program')) {
            return [];
        }

        return DB::table('academic_program')
            ->where('delete_status', '0')
            ->orderBy('name')
            ->pluck('name')
            ->filter(function ($name) {
                return trim((string) $name) !== '';
            })
            ->values()
            ->all();
    }

    private function getYearLevelOptions()
    {
        if (!Schema::hasTable('grade')) {
            return [];
        }

        return DB::table('grade')
            ->selectRaw("id, COALESCE(NULLIF(TRIM(year_level), ''), TRIM(grade)) AS label")
            ->where('delete_status', '0')
            ->orderByRaw("COALESCE(NULLIF(TRIM(year_level), ''), TRIM(grade)) ASC")
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                return trim((string) ($row->label ?? ''));
            })
            ->filter(function ($label) {
                return $label !== '';
            })
            ->values()
            ->all();
    }

    private function bootstrapStudentStructures()
    {
        if (!Schema::hasTable('student')) {
            return;
        }

        ScholarshipMonitoring::bootstrapMonitoringStructures();

        $this->addColumnIfMissing('student', 'scholarship_program', "VARCHAR(150) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'scholarship_semester', "VARCHAR(60) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'scholarship_academic_year', "VARCHAR(30) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'year_level', "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'address', "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'birthdate', 'DATE DEFAULT NULL');
        $this->addColumnIfMissing('student', 'school_name', "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'guardian_name', "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('student', 'guardian_contact', "VARCHAR(100) NOT NULL DEFAULT ''");

        if (Schema::hasTable('grade')) {
            $this->addColumnIfMissing('grade', 'year_level', "VARCHAR(255) NOT NULL DEFAULT ''");
            DB::statement("UPDATE grade SET year_level = grade WHERE COALESCE(year_level, '') = ''");
        }

        DB::statement("UPDATE student SET year_level = grade WHERE COALESCE(year_level, '') = ''");

        if (!Schema::hasTable('academic_year')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_year (
                id INT(11) NOT NULL AUTO_INCREMENT,
                label VARCHAR(30) NOT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $defaultYear = '2025-2026';
        $hasAcademicYear = DB::table('academic_year')->where('delete_status', '0')->exists();
        if (!$hasAcademicYear) {
            DB::table('academic_year')->insert([
                'label' => $defaultYear,
                'delete_status' => '0',
            ]);
        }

        if (!Schema::hasTable('academic_program')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_program (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_program_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

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

        DB::statement("INSERT INTO academic_program(name, delete_status)
            SELECT DISTINCT TRIM(scholarship_program), '0'
            FROM student
            WHERE COALESCE(TRIM(scholarship_program), '') <> ''
            ON DUPLICATE KEY UPDATE delete_status = '0'");

        if (Schema::hasTable('fees_transaction')) {
            DB::statement("INSERT INTO academic_program(name, delete_status)
                SELECT DISTINCT TRIM(program), '0'
                FROM fees_transaction
                WHERE COALESCE(TRIM(program), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('disbursed_transaction')) {
            DB::statement("INSERT INTO academic_program(name, delete_status)
                SELECT DISTINCT TRIM(program), '0'
                FROM disbursed_transaction
                WHERE COALESCE(TRIM(program), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        DB::statement("INSERT INTO academic_semester(label, delete_status)
            SELECT DISTINCT TRIM(scholarship_semester), '0'
            FROM student
            WHERE COALESCE(TRIM(scholarship_semester), '') <> ''
            ON DUPLICATE KEY UPDATE delete_status = '0'");

        if (Schema::hasTable('billing_batch') && Schema::hasColumn('billing_batch', 'semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(semester), '0'
                FROM billing_batch
                WHERE COALESCE(TRIM(semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

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
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
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
}
