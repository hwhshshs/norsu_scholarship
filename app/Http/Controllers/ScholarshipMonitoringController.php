<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScholarshipMonitoringController extends Controller
{
    public function uploadHistory(Request $request)
    {
        $search = $request->query('search', '');
        $module = $request->query('module', '');

        $query = DB::table('scholar_upload_history as suh')
            ->leftJoin('users as u', 'u.id', '=', 'suh.uploaded_by');

        if ($search !== '') {
            $query->where('suh.file_name', 'LIKE', "%{$search}%");
        }

        if ($module !== '') {
            $query->where('suh.module_name', $module);
        }

        // Global Stats (Independent of pagination)
        $globalStats = DB::table('scholar_upload_history')
            ->selectRaw('COUNT(*) as total_count, SUM(successful_rows) as total_success, SUM(records_processed) as total_processed')
            ->first();

        $history = $query->select('suh.*', 'u.name as uploader_name')
            ->orderByDesc('suh.created_at')
            ->paginate(15)
            ->appends(['search' => $search, 'module' => $module]);

        return view('scholarship.monitoring.upload-history', [
            'history' => $history,
            'search' => $search,
            'selectedModule' => $module,
            'stats' => $globalStats
        ]);
    }
}
