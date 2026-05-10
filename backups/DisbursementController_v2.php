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
}
