<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundReportController extends Controller
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

        $summary = $query->select(
            'program',
            'semester',
            'ay',
            DB::raw('COUNT(*) as total_batches'),
            DB::raw('SUM(scholar_count) as total_scholars'),
            DB::raw('SUM(amount) as total_billing_amount'),
            DB::raw('SUM(disbursed_count) as total_disbursed_scholars')
        )
        ->groupBy('program', 'semester', 'ay')
        ->orderBy('ay', 'desc')
        ->orderBy('semester', 'desc')
        ->orderBy('program')
        ->get();

        // Get distinct values for dropdowns
        $programs = DB::table('billing_batches')->select('program')->distinct()->pluck('program');
        $semesters = DB::table('billing_batches')->select('semester')->distinct()->pluck('semester');
        $ays = DB::table('billing_batches')->select('ay')->distinct()->pluck('ay');

        return view('fund-report.index', compact('summary', 'programs', 'semesters', 'ays'));
    }
}
