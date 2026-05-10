<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ScholarshipController extends Controller
{
    public function dashboard(\Illuminate\Http\Request $request)
    {
        $selectedAY = $request->query('ay');
        
        $query = DB::table('billing_batches');
        if ($selectedAY) {
            $query->where('ay', $selectedAY);
        }

        $batches = (clone $query)->get();
        $totalScholarsInBilling = $batches->sum('scholar_count');
        $totalDisbursedScholars = $batches->sum('disbursed_count');
        $granteePercentage = $totalScholarsInBilling > 0 ? round(($totalDisbursedScholars / $totalScholarsInBilling) * 100, 1) : 0;

        $totalBilledAmount = $batches->sum('amount');
        $totalDisbursedAmount = $batches->sum(function($batch) {
            return $batch->scholar_count > 0 ? ($batch->disbursed_count / $batch->scholar_count) * $batch->amount : 0;
        });
        $amountPercentage = $totalBilledAmount > 0 ? round(($totalDisbursedAmount / $totalBilledAmount) * 100, 1) : 0;

        $stats = [
            'scholars' => DB::table('students')->count(),
            'total_billing_batches' => $batches->count(),
            'grant_amount' => $totalBilledAmount,
            'grant_amount_disbursed' => $totalDisbursedAmount,
            'grantee_percentage' => $granteePercentage,
            'amount_percentage' => $amountPercentage,
            'total_disbursed_scholars' => $totalDisbursedScholars,
            'total_scholars_in_billing' => $totalScholarsInBilling,
            'students_with_scholarship' => DB::table('students')->whereNotNull('scholarship_program')->where('scholarship_program', '!=', 'N/A')->where('scholarship_program', '!=', '')->count(),
        ];

        // Data for graph: Scholars per Program
        $chartData = DB::table('billing_batches')
            ->select('program', DB::raw('SUM(scholar_count) as total'))
            ->when($selectedAY, function($q) use ($selectedAY) {
                return $q->where('ay', $selectedAY);
            })
            ->groupBy('program')
            ->get();

        $academicYears = DB::table('billing_batches')
            ->whereNotNull('ay')
            ->where('ay', '!=', '')
            ->select('ay as academic_year')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');

        // For Hover Preview: Collect data for all years
        $allBatches = DB::table('billing_batches')
            ->select('ay', 'program', 'scholar_count', 'disbursed_count', 'amount')
            ->get();

        $totalStudentsCount = DB::table('students')->count();
        $totalStudentsWithScholarship = DB::table('students')->whereNotNull('scholarship_program')->where('scholarship_program', '!=', 'N/A')->where('scholarship_program', '!=', '')->count();
        $overallScholarRate = $totalStudentsCount > 0 ? round(($totalStudentsWithScholarship / $totalStudentsCount) * 100, 1) : 0;

        $fullYearData = $allBatches->groupBy('ay')->map(function ($batches, $ay) use ($totalStudentsCount, $totalStudentsWithScholarship) {
            $totalScholars = $batches->sum('scholar_count');
            $totalPaid = $batches->sum('disbursed_count');
            $totalAmount = $batches->sum('amount');
            $totalReleased = $batches->sum(function($b) {
                return $b->scholar_count > 0 ? ($b->disbursed_count / $b->scholar_count) * $b->amount : 0;
            });

            return [
                'ay' => $ay,
                'granteePercentage' => $totalScholars > 0 ? round(($totalPaid / $totalScholars) * 100, 1) : 0,
                'amountPercentage' => $totalAmount > 0 ? round(($totalReleased / $totalAmount) * 100, 1) : 0,
                'totalScholars' => $totalScholars,
                'totalPaid' => $totalPaid,
                'totalAmount' => $totalAmount,
                'totalReleased' => $totalReleased,
                'scholarRate' => $totalStudentsCount > 0 ? round(($totalStudentsWithScholarship / $totalStudentsCount) * 100, 1) : 0,
                'chart' => $batches->groupBy('program')->map(fn($p) => $p->sum('scholar_count'))
            ];
        });

        // Add "Full History" summary for hover
        $historySummary = [
            'ay' => 'Full History',
            'granteePercentage' => $allBatches->sum('scholar_count') > 0 ? round(($allBatches->sum('disbursed_count') / $allBatches->sum('scholar_count')) * 100, 1) : 0,
            'amountPercentage' => $allBatches->sum('amount') > 0 ? round(($allBatches->sum(function($b) {
                return $b->scholar_count > 0 ? ($b->disbursed_count / $b->scholar_count) * $b->amount : 0;
            }) / $allBatches->sum('amount')) * 100, 1) : 0,
            'totalScholars' => $allBatches->sum('scholar_count'),
            'totalPaid' => $allBatches->sum('disbursed_count'),
            'totalAmount' => $allBatches->sum('amount'),
            'totalReleased' => $allBatches->sum(function($b) {
                return $b->scholar_count > 0 ? ($b->disbursed_count / $b->scholar_count) * $b->amount : 0;
            }),
            'scholarRate' => $overallScholarRate,
            'chart' => $allBatches->groupBy('program')->map(fn($p) => $p->sum('scholar_count'))
        ];

        return view('dashboard', compact('stats', 'academicYears', 'selectedAY', 'chartData', 'fullYearData', 'historySummary'));
    }
}
