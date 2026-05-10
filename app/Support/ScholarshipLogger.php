<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ScholarshipLogger
{
    /**
     * Log an administrative action.
     * 
     * @param string|null $module The module name (e.g. "Students", "Billing")
     * @param string $action The action performed (e.g. "Updated Student")
     * @param string|null $description Detailed description of the change
     * @return void
     */
    public static function log($module, $action, $description = null, $filePath = null, $originalName = null)
    {
        try {
            DB::table('scholar_activity_logs')->insert([
                'user_id' => Auth::id(),
                'user_name' => Auth::user() ? Auth::user()->name : 'System',
                'user_email' => Auth::user() ? Auth::user()->email : 'system@internal',
                'module' => $module,
                'action' => $action,
                'description' => $description,
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Fails silently to ensure business logic is never interrupted
            \Illuminate\Support\Facades\Log::error("Logging Error: " . $e->getMessage());
        }
    }
}
