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

        $batches = $query->orderByDesc('created_at')->paginate(20);

        return view('disbursement.index', compact('batches'));
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
            ->where('billing_batch_id', $id)
            ->get();

        return view('disbursement.show', compact('batch', 'scholars'));
    }
}
