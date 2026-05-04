<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScholarshipReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $this->bootstrapReconciliationStructures();

        $student = trim((string) $request->query('student', ''));
        $program = trim((string) $request->query('program', ''));
        $academicYear = trim((string) $request->query('academic_year', ''));
        $semester = trim((string) $request->query('semester', ''));
        $batchLabel = trim((string) $request->query('batch_label', ''));
        $region = trim((string) $request->query('region', ''));
        $billingBatchId = trim((string) $request->query('billing_batch_id', ''));
        $mismatchOnly = $request->boolean('mismatch_only');

        $programOptions = $this->getDistinctBillingBatchValues('program');
        if ($program !== '' && !in_array($program, $programOptions, true)) {
            array_unshift($programOptions, $program);
        }

        $academicYearOptions = $this->getDistinctBillingBatchValues('academic_year');
        if ($academicYear !== '' && !in_array($academicYear, $academicYearOptions, true)) {
            array_unshift($academicYearOptions, $academicYear);
        }

        $filters = [
            'student' => $student,
            'program' => $program,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'batch_label' => $batchLabel,
            'region' => $region,
            'billing_batch_id' => $billingBatchId,
            'mismatch_only' => $mismatchOnly,
        ];

        $errorMessage = '';
        $rows = [];
        $summary = [
            'matched_rows' => 0,
            'mismatch_count' => 0,
            'fees_total' => 0,
            'balance_total' => 0,
            'billing_total' => 0,
            'disbursed_total' => 0,
            'diff_total' => 0,
        ];

        $batchProgressRows = [];
        $batchTotals = [
            'batches' => 0,
            'billed_scholars' => 0,
            'finalized_scholars' => 0,
            'pending_scholars' => 0,
            'billed_total' => 0,
            'disbursed_total' => 0,
            'variance' => 0,
        ];

        try {
            [$mainSql, $mainBindings] = $this->buildMainSql($filters);
            $rawRows = DB::select($mainSql, $mainBindings);

            foreach ($rawRows as $row) {
                $diff = (float) ($row->paid_vs_disbursed ?? 0);
                $isMismatch = abs($diff) > 0.01;
                
                $conflictReason = '';
                if ($isMismatch) {
                    if ((float)$row->total_paid > 0 && (float)$row->total_disbursed == 0) {
                        $conflictReason = 'Pending Payment';
                    } elseif ((float)$row->total_paid == 0 && (float)$row->total_disbursed > 0) {
                        $conflictReason = 'Orphaned Payment';
                    } elseif ($diff > 0) {
                        $conflictReason = 'Underpaid';
                    } else {
                        $conflictReason = 'Overpaid';
                    }
                }

                $rows[] = [
                    'id' => (int) ($row->id ?? 0),
                    'sname' => (string) ($row->sname ?? ''),
                    'contact' => (string) ($row->contact ?? ''),
                    'program' => (string) ($row->program ?? ''),
                    'academic_year' => (string) ($row->academic_year ?? ''),
                    'semester' => (string) ($row->semester ?? ''),
                    'batch_label' => (string) ($row->batch_label ?? ''),
                    'region' => (string) ($row->region ?? ''),
                    'billing_batch_id' => (int) ($row->billing_batch_id ?? 0),
                    'fees' => (float) ($row->fees ?? 0),
                    'balance' => (float) ($row->balance ?? 0),
                    'total_paid' => (float) ($row->total_paid ?? 0),
                    'total_disbursed' => (float) ($row->total_disbursed ?? 0),
                    'paid_vs_disbursed' => $diff,
                    'is_mismatch' => $isMismatch,
                    'conflict_reason' => $conflictReason,
                ];

                $summary['matched_rows']++;
                $summary['fees_total'] += (float) ($row->fees ?? 0);
                $summary['balance_total'] += (float) ($row->balance ?? 0);
                $summary['billing_total'] += (float) ($row->total_paid ?? 0);
                $summary['disbursed_total'] += (float) ($row->total_disbursed ?? 0);
                $summary['diff_total'] += $diff;
                if ($isMismatch) {
                    $summary['mismatch_count']++;
                }
            }

            [$batchSql, $batchBindings] = $this->buildBatchSql($filters);
            $rawBatchRows = DB::select($batchSql, $batchBindings);

            foreach ($rawBatchRows as $batchRow) {
                $expectedScholars = max((int) ($batchRow->scholar_count ?? 0), (int) ($batchRow->billed_scholars ?? 0));
                $finalizedScholars = (int) ($batchRow->finalized_scholars ?? 0);
                $pendingScholars = max(0, $expectedScholars - $finalizedScholars);
                $billedTotal = (float) ($batchRow->billed_total ?? 0);
                $disbursedTotal = (float) ($batchRow->disbursed_total ?? 0);
                $variance = $billedTotal - $disbursedTotal;
                $progressPct = $expectedScholars > 0 ? (($finalizedScholars / $expectedScholars) * 100) : 0;

                $statusLabel = 'Open';
                $statusClass = 'bg-gradient-secondary';
                if ($expectedScholars > 0 && $pendingScholars === 0) {
                    $statusLabel = 'Completed';
                    $statusClass = 'bg-gradient-success';
                } elseif ($finalizedScholars > 0) {
                    $statusLabel = 'In Progress';
                    $statusClass = 'bg-gradient-warning';
                }

                $batchProgressRows[] = [
                    'id' => (int) ($batchRow->id ?? 0),
                    'program' => (string) ($batchRow->program ?? ''),
                    'academic_year' => (string) ($batchRow->academic_year ?? ''),
                    'semester' => (string) ($batchRow->semester ?? ''),
                    'batch_label' => (string) ($batchRow->batch_label ?? ''),
                    'region' => (string) ($batchRow->region ?? ''),
                    'billing_date' => (string) ($batchRow->billing_date ?? ''),
                    'expected_scholars' => $expectedScholars,
                    'finalized_scholars' => $finalizedScholars,
                    'pending_scholars' => $pendingScholars,
                    'billed_total' => $billedTotal,
                    'disbursed_total' => $disbursedTotal,
                    'variance' => $variance,
                    'progress_pct' => $progressPct,
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                ];

                $batchTotals['billed_scholars'] += $expectedScholars;
                $batchTotals['finalized_scholars'] += $finalizedScholars;
                $batchTotals['pending_scholars'] += $pendingScholars;
                $batchTotals['billed_total'] += $billedTotal;
                $batchTotals['disbursed_total'] += $disbursedTotal;
                $batchTotals['variance'] += $variance;
            }

            $batchTotals['batches'] = count($batchProgressRows);
        } catch (\Throwable $e) {
            $errorMessage = 'Unable to load reconciliation data. Please verify scholarship tables and records.';
        }

        return view('scholarship.reconciliation.index', [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'batchProgressRows' => $batchProgressRows,
            'batchTotals' => $batchTotals,
            'programOptions' => $programOptions,
            'academicYearOptions' => $academicYearOptions,
            'errorMessage' => $errorMessage,
        ]);
    }

    private function buildMainSql(array $filters)
    {
        $sql = "SELECT m.stdid AS id, m.program, m.academic_year, m.semester, m.batch_label, m.region, m.billing_batch_id, s.sname, s.contact, s.fees, s.balance,
                       COALESCE(bt.total_billing, 0) AS total_paid,
                       COALESCE(dt.total_disbursed, 0) AS total_disbursed,
                       (COALESCE(bt.total_billing, 0) - COALESCE(dt.total_disbursed, 0)) AS paid_vs_disbursed
                FROM (
                    SELECT
                        ft.stdid,
                        COALESCE(NULLIF(TRIM(ft.program), ''), NULLIF(TRIM(sx.scholarship_program), ''), '(Unspecified)') AS program,
                        COALESCE(NULLIF(TRIM(ft.academic_year), ''), NULLIF(TRIM(sx.scholarship_academic_year), ''), '(Unspecified)') AS academic_year,
                        COALESCE(NULLIF(TRIM(ft.semester), ''), NULLIF(TRIM(sx.scholarship_semester), ''), '(Unspecified)') AS semester,
                        COALESCE(NULLIF(TRIM(ft.batch_label), ''), '(Unspecified)') AS batch_label,
                        COALESCE(NULLIF(TRIM(ft.region), ''), '(Unspecified)') AS region,
                        COALESCE(ft.billing_batch_id, 0) AS billing_batch_id
                    FROM fees_transaction ft
                    LEFT JOIN student sx ON sx.id = ft.stdid
                    WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                    GROUP BY ft.stdid,
                        COALESCE(NULLIF(TRIM(ft.program), ''), NULLIF(TRIM(sx.scholarship_program), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.academic_year), ''), NULLIF(TRIM(sx.scholarship_academic_year), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.semester), ''), NULLIF(TRIM(sx.scholarship_semester), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.batch_label), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.region), ''), '(Unspecified)'),
                        COALESCE(ft.billing_batch_id, 0)

                    UNION

                    SELECT
                        dt.stdid,
                        COALESCE(NULLIF(TRIM(dt.program), ''), NULLIF(TRIM(sy.scholarship_program), ''), '(Unspecified)') AS program,
                        COALESCE(NULLIF(TRIM(dt.academic_year), ''), NULLIF(TRIM(sy.scholarship_academic_year), ''), '(Unspecified)') AS academic_year,
                        COALESCE(NULLIF(TRIM(dt.semester), ''), NULLIF(TRIM(sy.scholarship_semester), ''), '(Unspecified)') AS semester,
                        COALESCE(NULLIF(TRIM(dt.batch_label), ''), '(Unspecified)') AS batch_label,
                        COALESCE(NULLIF(TRIM(dt.region), ''), '(Unspecified)') AS region,
                        COALESCE(dt.billing_batch_id, 0) AS billing_batch_id
                    FROM disbursed_transaction dt
                    LEFT JOIN student sy ON sy.id = dt.stdid
                    GROUP BY dt.stdid,
                        COALESCE(NULLIF(TRIM(dt.program), ''), NULLIF(TRIM(sy.scholarship_program), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.academic_year), ''), NULLIF(TRIM(sy.scholarship_academic_year), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.semester), ''), NULLIF(TRIM(sy.scholarship_semester), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.batch_label), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.region), ''), '(Unspecified)'),
                        COALESCE(dt.billing_batch_id, 0)
                ) m
                INNER JOIN student s ON s.id = m.stdid
                LEFT JOIN (
                    SELECT
                        ft.stdid,
                        COALESCE(NULLIF(TRIM(ft.program), ''), NULLIF(TRIM(sx.scholarship_program), ''), '(Unspecified)') AS program,
                        COALESCE(NULLIF(TRIM(ft.academic_year), ''), NULLIF(TRIM(sx.scholarship_academic_year), ''), '(Unspecified)') AS academic_year,
                        COALESCE(NULLIF(TRIM(ft.semester), ''), NULLIF(TRIM(sx.scholarship_semester), ''), '(Unspecified)') AS semester,
                        COALESCE(NULLIF(TRIM(ft.batch_label), ''), '(Unspecified)') AS batch_label,
                        COALESCE(NULLIF(TRIM(ft.region), ''), '(Unspecified)') AS region,
                        COALESCE(ft.billing_batch_id, 0) AS billing_batch_id,
                        SUM(ft.paid) AS total_billing
                    FROM fees_transaction ft
                    LEFT JOIN student sx ON sx.id = ft.stdid
                    WHERE COALESCE(ft.record_type, 'billing') = 'billing'
                    GROUP BY ft.stdid,
                        COALESCE(NULLIF(TRIM(ft.program), ''), NULLIF(TRIM(sx.scholarship_program), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.academic_year), ''), NULLIF(TRIM(sx.scholarship_academic_year), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.semester), ''), NULLIF(TRIM(sx.scholarship_semester), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.batch_label), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(ft.region), ''), '(Unspecified)'),
                        COALESCE(ft.billing_batch_id, 0)
                ) bt ON bt.stdid = m.stdid AND bt.program = m.program AND bt.academic_year = m.academic_year AND bt.semester = m.semester AND bt.batch_label = m.batch_label AND bt.region = m.region AND bt.billing_batch_id = m.billing_batch_id
                LEFT JOIN (
                    SELECT
                        dt.stdid,
                        COALESCE(NULLIF(TRIM(dt.program), ''), NULLIF(TRIM(sy.scholarship_program), ''), '(Unspecified)') AS program,
                        COALESCE(NULLIF(TRIM(dt.academic_year), ''), NULLIF(TRIM(sy.scholarship_academic_year), ''), '(Unspecified)') AS academic_year,
                        COALESCE(NULLIF(TRIM(dt.semester), ''), NULLIF(TRIM(sy.scholarship_semester), ''), '(Unspecified)') AS semester,
                        COALESCE(NULLIF(TRIM(dt.batch_label), ''), '(Unspecified)') AS batch_label,
                        COALESCE(NULLIF(TRIM(dt.region), ''), '(Unspecified)') AS region,
                        COALESCE(dt.billing_batch_id, 0) AS billing_batch_id,
                        SUM(dt.disbursed_amount) AS total_disbursed
                    FROM disbursed_transaction dt
                    LEFT JOIN student sy ON sy.id = dt.stdid
                    GROUP BY dt.stdid,
                        COALESCE(NULLIF(TRIM(dt.program), ''), NULLIF(TRIM(sy.scholarship_program), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.academic_year), ''), NULLIF(TRIM(sy.scholarship_academic_year), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.semester), ''), NULLIF(TRIM(sy.scholarship_semester), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.batch_label), ''), '(Unspecified)'),
                        COALESCE(NULLIF(TRIM(dt.region), ''), '(Unspecified)'),
                        COALESCE(dt.billing_batch_id, 0)
                ) dt ON dt.stdid = m.stdid AND dt.program = m.program AND dt.academic_year = m.academic_year AND dt.semester = m.semester AND dt.batch_label = m.batch_label AND dt.region = m.region AND dt.billing_batch_id = m.billing_batch_id
                WHERE s.delete_status = '0'";

        $bindings = [];

        if ((string) $filters['student'] !== '') {
            $like = '%' . (string) $filters['student'] . '%';
            $sql .= " AND (s.sname LIKE ? OR s.contact LIKE ? OR m.stdid = ?)";
            $bindings[] = $like;
            $bindings[] = $like;
            $bindings[] = ctype_digit((string) $filters['student']) ? (int) $filters['student'] : -1;
        }

        if ((string) $filters['program'] !== '') {
            $sql .= ' AND m.program = ?';
            $bindings[] = (string) $filters['program'];
        }

        if ((string) $filters['academic_year'] !== '') {
            $sql .= ' AND m.academic_year = ?';
            $bindings[] = (string) $filters['academic_year'];
        }

        if ((string) $filters['semester'] !== '') {
            $sql .= ' AND m.semester LIKE ?';
            $bindings[] = '%' . (string) $filters['semester'] . '%';
        }

        if ((string) $filters['batch_label'] !== '') {
            $sql .= ' AND m.batch_label LIKE ?';
            $bindings[] = '%' . (string) $filters['batch_label'] . '%';
        }

        if ((string) $filters['region'] !== '') {
            $sql .= ' AND m.region LIKE ?';
            $bindings[] = '%' . (string) $filters['region'] . '%';
        }

        if ((string) $filters['billing_batch_id'] !== '') {
            if (ctype_digit((string) $filters['billing_batch_id'])) {
                $sql .= ' AND m.billing_batch_id = ?';
                $bindings[] = (int) $filters['billing_batch_id'];
            } else {
                $sql .= ' AND 1 = 0';
            }
        }

        if (!empty($filters['mismatch_only'])) {
            $sql .= " AND ABS(COALESCE(bt.total_billing, 0) - COALESCE(dt.total_disbursed, 0)) > 0.009";
        }

        $sql .= ' ORDER BY s.sname ASC, m.program ASC, m.academic_year ASC, m.semester ASC, m.batch_label ASC, m.region ASC LIMIT 1500';

        return [$sql, $bindings];
    }

    private function buildBatchSql(array $filters)
    {
        $sql = "SELECT b.id, b.program, b.academic_year, b.semester, b.batch_label, b.region, b.billing_date,
                       b.billing_total_amount, b.scholar_count, b.status,
                       COALESCE(bt.billed_scholars, 0) AS billed_scholars,
                       COALESCE(bt.billed_total, 0) AS billed_total,
                       COALESCE(dt.finalized_scholars, 0) AS finalized_scholars,
                       COALESCE(dt.disbursed_total, 0) AS disbursed_total
                FROM billing_batch b
                LEFT JOIN (
                    SELECT billing_batch_id,
                           COUNT(DISTINCT stdid) AS billed_scholars,
                           SUM(paid) AS billed_total
                    FROM fees_transaction
                    WHERE COALESCE(record_type, 'billing') = 'billing' AND COALESCE(billing_batch_id, 0) > 0
                    GROUP BY billing_batch_id
                ) bt ON bt.billing_batch_id = b.id
                LEFT JOIN (
                    SELECT billing_batch_id,
                           COUNT(DISTINCT stdid) AS finalized_scholars,
                           SUM(disbursed_amount) AS disbursed_total
                    FROM disbursed_transaction
                    WHERE COALESCE(billing_batch_id, 0) > 0 AND COALESCE(disbursed_status, 'draft') = 'finalized'
                    GROUP BY billing_batch_id
                ) dt ON dt.billing_batch_id = b.id";

        $conditions = [];
        $bindings = [];

        if ((string) $filters['program'] !== '') {
            $conditions[] = 'b.program = ?';
            $bindings[] = (string) $filters['program'];
        }

        if ((string) $filters['academic_year'] !== '') {
            $conditions[] = 'b.academic_year = ?';
            $bindings[] = (string) $filters['academic_year'];
        }

        if ((string) $filters['semester'] !== '') {
            $conditions[] = 'b.semester LIKE ?';
            $bindings[] = '%' . (string) $filters['semester'] . '%';
        }

        if ((string) $filters['batch_label'] !== '') {
            $conditions[] = 'b.batch_label LIKE ?';
            $bindings[] = '%' . (string) $filters['batch_label'] . '%';
        }

        if ((string) $filters['region'] !== '') {
            $conditions[] = 'b.region LIKE ?';
            $bindings[] = '%' . (string) $filters['region'] . '%';
        }

        if ((string) $filters['billing_batch_id'] !== '') {
            if (ctype_digit((string) $filters['billing_batch_id'])) {
                $conditions[] = 'b.id = ?';
                $bindings[] = (int) $filters['billing_batch_id'];
            } else {
                $conditions[] = '1 = 0';
            }
        }

        if (count($conditions) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY b.id DESC LIMIT 500';

        return [$sql, $bindings];
    }

    private function getDistinctBillingBatchValues($column)
    {
        if (!Schema::hasTable('billing_batch')) {
            return [];
        }

        if (!in_array($column, ['program', 'academic_year'], true)) {
            return [];
        }

        return DB::table('billing_batch')
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

    private function buildBillingProgramBatchRef($program, $batchId)
    {
        $prefix = $this->billingProgramIdentifierPrefix($program);

        return $prefix . '-' . str_pad((string) max(1, (int) $batchId), 6, '0', STR_PAD_LEFT);
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
            $ref = $this->buildBillingProgramBatchRef((string) ($row->program ?? ''), (int) $row->id);
            DB::table('billing_batch')->where('id', (int) $row->id)->update([
                'program_batch_ref' => $ref,
            ]);
        }
    }

    private function bootstrapReconciliationStructures()
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
            $this->addColumnIfMissing('billing_batch', 'status', "VARCHAR(20) NOT NULL DEFAULT 'open'");
            $this->addColumnIfMissing('billing_batch', 'scholar_count', "INT(11) NOT NULL DEFAULT 0");
            $this->addColumnIfMissing('billing_batch', 'signed_billing_doc', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('billing_batch', 'program_batch_ref', "VARCHAR(80) DEFAULT NULL");
            $this->addColumnIfMissing('billing_batch', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
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
                disbursed_date DATE NOT NULL,
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
            $this->addColumnIfMissing('disbursed_transaction', 'program', "VARCHAR(150) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'semester', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'academic_year', "VARCHAR(30) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'batch_label', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'region', "VARCHAR(100) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('disbursed_transaction', 'billing_batch_id', 'INT(11) DEFAULT NULL');
            $this->addColumnIfMissing('disbursed_transaction', 'disbursed_status', "VARCHAR(20) NOT NULL DEFAULT 'draft'");
        }

        $this->addIndexIfMissing('billing_batch', 'uk_program_batch_ref', 'ADD UNIQUE KEY uk_program_batch_ref (program_batch_ref)');
        $this->addIndexIfMissing('billing_batch', 'idx_program_term', 'ADD KEY idx_program_term (program, academic_year, semester)');
        $this->addIndexIfMissing('billing_batch', 'idx_batch_region', 'ADD KEY idx_batch_region (batch_label, region)');
        $this->addIndexIfMissing('disbursed_transaction', 'idx_batch_link', 'ADD KEY idx_batch_link (billing_batch_id)');
        $this->addIndexIfMissing('disbursed_transaction', 'uk_batch_student', 'ADD UNIQUE KEY uk_batch_student (billing_batch_id, stdid)');

        $this->ensureBillingProgramBatchRefs();
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
            // Ignore index creation collisions from legacy schema variations.
        }
    }
}
