<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisbursementController extends Controller
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

        return view('disbursement.index', compact('batches', 'totals'));
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
            ->join('students', 'billing_scholars.student_id', '=', 'students.id')
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

        return view('disbursement.show', compact('batch', 'scholars'));
    }

    public function exportCsv($id)
    {
        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) abort(404);

        $scholars = DB::table('billing_scholars')
            ->join('students', 'billing_scholars.student_id', '=', 'students.id')
            ->where('billing_scholars.billing_batch_id', $id)
            ->select('students.student_id_no', 'students.last_name', 'students.given_name', 'students.middle_initial', 'students.degree_program', 'students.year_level', 'students.tdp_tes_award_no')
            ->orderBy('students.last_name')
            ->orderBy('students.given_name')
            ->get();

        $filename = "Disbursement_Report_" . str_replace(' ', '_', $batch->program) . "_" . $batch->ay . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Student ID', 'Last Name', 'Given Name', 'M.I.', 'Program', 'Year', 'Award No', 'Amount'];

        $callback = function() use($scholars, $columns, $batch) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $totalAmount = 0;
            foreach ($scholars as $s) {
                $individualAmount = $batch->scholar_count > 0 ? $batch->amount / $batch->scholar_count : 0;
                $totalAmount += $individualAmount;

                fputcsv($file, [
                    $s->student_id_no,
                    $s->last_name,
                    $s->given_name,
                    $s->middle_initial,
                    $s->degree_program,
                    $s->year_level,
                    $s->tdp_tes_award_no,
                    number_format($individualAmount, 2)
                ]);
            }

            // Add Total Row
            fputcsv($file, ['', '', '', '', '', '', 'TOTAL DISBURSED', number_format($totalAmount, 2)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function printReport($id)
    {
        $batch = DB::table('billing_batches')
            ->leftJoin('users', 'billing_batches.created_by', '=', 'users.id')
            ->select('billing_batches.*', 'users.name as creator_name')
            ->where('billing_batches.id', $id)
            ->first();

        if (!$batch) abort(404);

        $scholars = DB::table('billing_scholars')
            ->join('students', 'billing_scholars.student_id', '=', 'students.id')
            ->where('billing_scholars.billing_batch_id', $id)
            ->select(
                'billing_scholars.*',
                'students.student_id_no',
                'students.last_name',
                'students.given_name',
                'students.middle_initial',
                'students.tdp_tes_award_no',
                'students.degree_program',
                'students.year_level'
            )
            ->get();

        return view('disbursement.report', compact('batch', 'scholars'));
    }

    public function exportAllCsv()
    {
        $scholars = DB::table('billing_scholars')
            ->join('students', 'billing_scholars.student_id', '=', 'students.id')
            ->join('billing_batches', 'billing_scholars.billing_batch_id', '=', 'billing_batches.id')
            ->select(
                'billing_batches.program',
                'billing_batches.ay',
                'students.student_id_no',
                'students.last_name',
                'students.given_name',
                'students.degree_program',
                'students.year_level',
                'billing_batches.amount',
                'billing_batches.scholar_count'
            )
            ->orderBy('billing_batches.program')
            ->orderBy('billing_batches.ay')
            ->orderBy('students.last_name')
            ->orderBy('students.given_name')
            ->get();

        $filename = "Scholarship_Masterlist_All_Programs_" . now()->format('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Program', 'AY', 'Student ID', 'Last Name', 'Given Name', 'Degree', 'Year', 'Est. Amount'];

        $callback = function() use($scholars, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $grandTotal = 0;
            foreach ($scholars as $s) {
                $individualAmount = $s->scholar_count > 0 ? $s->amount / $s->scholar_count : 0;
                $grandTotal += $individualAmount;

                fputcsv($file, [
                    $s->program,
                    $s->ay,
                    $s->student_id_no,
                    $s->last_name,
                    $s->given_name,
                    $s->degree_program,
                    $s->year_level,
                    number_format($individualAmount, 2)
                ]);
            }

            // Add Grand Total Row
            fputcsv($file, ['', '', '', '', '', '', 'GRAND TOTAL', number_format($grandTotal, 2)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function masterSummaryPdf()
    {
        $summary = DB::table('billing_batches')
            ->select(
                'program',
                DB::raw('COUNT(*) as total_batches'),
                DB::raw('SUM(scholar_count) as total_scholars'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(disbursed_count) as total_disbursed')
            )
            ->groupBy('program')
            ->get();

        return view('disbursement.master_report', compact('summary'));
    }
}
