<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScholarshipController extends Controller
{
    public function dashboard()
    {
        $moduleGroups = $this->moduleGroups();

        return view('dashboard', [
            'scholarshipStats' => $this->buildDashboardStats($moduleGroups),
            'moduleHighlights' => $this->highlightModules($moduleGroups),
        ]);
    }

    public function system()
    {
        $moduleGroups = $this->moduleGroups();

        return view('scholarship.system', [
            'moduleGroups' => $moduleGroups,
            'totalModules' => count($this->flatModules($moduleGroups)),
        ]);
    }

    public function integrationChecklist()
    {
        $user = Auth::user();

        $legacyBridgePath = public_path('legacy/bridge_login.php');
        $legacyLogoutPath = public_path('legacy/logout.php');
        $legacyHeaderPath = public_path('legacy/php/header.php');

        $headerContents = '';
        if (is_file($legacyHeaderPath)) {
            $contents = @file_get_contents($legacyHeaderPath);
            if (is_string($contents)) {
                $headerContents = $contents;
            }
        }

        $checks = [
            [
                'name' => 'Legacy bridge script exists',
                'ok' => is_file($legacyBridgePath),
                'hint' => 'Required for auto-login launch URLs.',
            ],
            [
                'name' => 'Legacy logout script exists',
                'ok' => is_file($legacyLogoutPath),
                'hint' => 'Required for synchronized Laravel and legacy sign out.',
            ],
            [
                'name' => 'Reverse logout menu wiring',
                'ok' => strpos($headerContents, 'logout.php?next=/logout') !== false,
                'hint' => 'Legacy menu should route logout through Laravel logout endpoint.',
            ],
            [
                'name' => 'Authenticated Laravel user context',
                'ok' => $user !== null,
                'hint' => $user ? ('Logged in as ' . $user->email) : 'No authenticated user detected.',
            ],
        ];

        $quickActions = [
            [
                'label' => 'Open Student Module (Bridge Launch)',
                'url' => route('scholarship-system.launch', 'students'),
                'class' => 'bg-gradient-primary',
            ],
            [
                'label' => 'Open Scholarship Hub',
                'url' => route('scholarship-system'),
                'class' => 'btn-outline-dark',
            ],
            [
                'label' => 'Test Synchronized Logout',
                'url' => url('/logout'),
                'class' => 'btn-outline-danger',
            ],
        ];

        return view('scholarship.checklist', [
            'checks' => $checks,
            'quickActions' => $quickActions,
            'bridgeConsumeUrl' => route('scholarship-system.bridge.consume'),
            'sampleLaunchUrl' => route('scholarship-system.launch', 'students'),
        ]);
    }

    public function module($module)
    {
        $moduleGroups = $this->moduleGroups();
        $modules = $this->flatModules($moduleGroups);

        if (!isset($modules[$module])) {
            abort(404);
        }

        $moduleDetails = $modules[$module];
        $legacyUrl = url('/legacy/' . $moduleDetails['file']);
        $launchUrl = route('scholarship-system.launch', $moduleDetails['slug']);

        return view('scholarship.module', [
            'module' => $moduleDetails,
            'legacyUrl' => $legacyUrl,
            'launchUrl' => $launchUrl,
            'moduleGroups' => $moduleGroups,
        ]);
    }

    public function launch($module)
    {
        $moduleGroups = $this->moduleGroups();
        $modules = $this->flatModules($moduleGroups);

        if (!isset($modules[$module])) {
            abort(404);
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $token = bin2hex(random_bytes(32));

        Cache::put('legacy_bridge:' . $token, [
            'uid' => (int) $user->id,
            'username' => (string) $user->email,
            'name' => (string) $user->name,
        ], now()->addMinutes(2));

        $target = $modules[$module]['file'];

        return redirect()->to(url('/legacy/bridge_login.php?token=' . rawurlencode($token) . '&target=' . rawurlencode($target)));
    }

    public function consumeBridgeToken(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid bridge token.',
            ], 422);
        }

        $payload = Cache::pull('legacy_bridge:' . $token);
        if (!is_array($payload) || empty($payload['uid'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Bridge token not found or expired.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'uid' => (int) $payload['uid'],
            'username' => (string) ($payload['username'] ?? ''),
            'name' => (string) ($payload['name'] ?? ''),
        ]);
    }

    private function moduleGroups()
    {
        return [
            'Core' => [
                [
                    'slug' => 'legacy-dashboard',
                    'name' => 'Legacy Dashboard',
                    'description' => 'Main scholarship operations dashboard and counters.',
                    'file' => 'index.php',
                    'icon' => 'fa fa-dashboard',
                ],
                [
                    'slug' => 'students',
                    'name' => 'Student Management',
                    'description' => 'Add, edit, activate, deactivate, and import student records.',
                    'file' => 'student.php',
                    'icon' => 'fa fa-users',
                ],
                [
                    'slug' => 'inactive-students',
                    'name' => 'Inactive Students',
                    'description' => 'Review and manage inactive scholar accounts.',
                    'file' => 'inactivestd.php',
                    'icon' => 'fa fa-user-times',
                ],
                [
                    'slug' => 'student-report',
                    'name' => 'Student Report',
                    'description' => 'Generate filtered report views for scholars and batches.',
                    'file' => 'report.php',
                    'icon' => 'fa fa-file-pdf-o',
                ],
            ],
            'Academic Setup' => [
                [
                    'slug' => 'academic-management',
                    'name' => 'Academic Management',
                    'description' => 'Academic setup launcher for year, level, and program tables.',
                    'file' => 'academic_management.php',
                    'icon' => 'fa fa-graduation-cap',
                ],
                [
                    'slug' => 'academic-year',
                    'name' => 'Academic Year',
                    'description' => 'Create and maintain academic year records.',
                    'file' => 'academic_year.php',
                    'icon' => 'fa fa-calendar',
                ],
                [
                    'slug' => 'year-levels',
                    'name' => 'Year Level',
                    'description' => 'Maintain year level and grade references.',
                    'file' => 'grade.php',
                    'icon' => 'fa fa-list-ol',
                ],
                [
                    'slug' => 'programs',
                    'name' => 'Program Management',
                    'description' => 'Maintain scholarship program reference values.',
                    'file' => 'program.php',
                    'icon' => 'fa fa-sitemap',
                ],
            ],
            'Billing And Disbursement' => [
                [
                    'slug' => 'billing-report',
                    'name' => 'Fund Report',
                    'description' => 'Unified billing and disbursed reporting in a single view.',
                    'file' => 'fees.php',
                    'icon' => 'fa fa-money',
                ],
                [
                    'slug' => 'billing-entry',
                    'name' => 'Billing Entry',
                    'description' => 'Manual billing batch creation with CSV and document upload.',
                    'file' => 'billing_entry.php',
                    'icon' => 'fa fa-edit',
                ],
                [
                    'slug' => 'disbursed-report',
                    'name' => 'Disbursed Report',
                    'description' => 'Review finalized disbursement outputs and per-batch status.',
                    'file' => 'disbursed_report.php',
                    'icon' => 'fa fa-bank',
                ],
                [
                    'slug' => 'disbursed-import',
                    'name' => 'Bulk Disbursed Import',
                    'description' => 'Preview and bulk-finalize scholarship disbursements from CSV data.',
                    'file' => 'disbursed_import.php',
                    'icon' => 'fa fa-file-import',
                ],
                [
                    'slug' => 'disbursed-entry',
                    'name' => 'Disbursed Entry',
                    'description' => 'Finalize disbursements with OR, ADA, and attachment details.',
                    'file' => 'disbursed_entry.php',
                    'icon' => 'fa fa-check-square-o',
                ],
                [
                    'slug' => 'reconciliation',
                    'name' => 'Reconciliation',
                    'description' => 'Billing versus disbursed reconciliation with batch filters.',
                    'file' => 'reconciliation_report.php',
                    'icon' => 'fa fa-exchange',
                ],
            ],
            'Administration' => [
                [
                    'slug' => 'account-setting',
                    'name' => 'Account Setting',
                    'description' => 'Update legacy account profile and password settings.',
                    'file' => 'setting.php',
                    'icon' => 'fa fa-cogs',
                ],
                [
                    'slug' => 'legacy-login',
                    'name' => 'Legacy Login',
                    'description' => 'Open legacy login screen for session authentication.',
                    'file' => 'login.php',
                    'icon' => 'fa fa-sign-in',
                ],
            ],
        ];
    }

    private function flatModules(array $groups)
    {
        $flat = [];

        foreach ($groups as $groupName => $items) {
            foreach ($items as $item) {
                $item['group'] = $groupName;
                $flat[$item['slug']] = $item;
            }
        }

        return $flat;
    }

    private function highlightModules(array $groups)
    {
        $highlights = [
            'students',
            'academic-management',
            'billing-report',
            'reconciliation',
        ];

        $flat = $this->flatModules($groups);
        $cards = [];

        foreach ($highlights as $slug) {
            if (isset($flat[$slug])) {
                $cards[] = $flat[$slug];
            }
        }

        return $cards;
    }

    private function buildDashboardStats(array $moduleGroups)
    {
        $stats = [
            'total_students' => 0,
            'active_students' => 0,
            'inactive_students' => 0,
            'year_levels' => 0,
            'billing_batches' => 0,
            'disbursed_finalized' => 0,
            'billed_scholars' => 0,
            'disbursed_scholars' => 0,
            'total_payout' => 0,
            'module_count' => count($this->flatModules($moduleGroups)),
        ];

        try {
            if (Schema::hasTable('student')) {
                $stats['total_students'] = (int) DB::table('student')->count();
                $stats['active_students'] = (int) DB::table('student')->where('delete_status', '0')->count();
                $stats['inactive_students'] = (int) DB::table('student')->where('delete_status', '1')->count();
            }

            if (Schema::hasTable('grade')) {
                $stats['year_levels'] = (int) DB::table('grade')->count();
            }

            if (Schema::hasTable('fees_transaction')) {
                $stats['total_payout'] = (float) DB::table('fees_transaction')->sum('paid');
                $stats['billed_scholars'] = (int) DB::table('fees_transaction')
                    ->whereRaw("COALESCE(record_type, 'billing') = 'billing'")
                    ->distinct('stdid')
                    ->count('stdid');
            }

            if (Schema::hasTable('billing_batch')) {
                $stats['billing_batches'] = (int) DB::table('billing_batch')->count();
            }

            if (Schema::hasTable('disbursed_transaction')) {
                $stats['disbursed_finalized'] = (int) DB::table('disbursed_transaction')
                    ->where('disbursed_status', 'finalized')
                    ->count();

                $stats['disbursed_scholars'] = (int) DB::table('disbursed_transaction')
                    ->where('disbursed_status', 'finalized')
                    ->distinct('stdid')
                    ->count('stdid');
            }
        } catch (\Throwable $e) {
            // Keep dashboard usable even when the legacy schema is not yet present.
        }

        return $stats;
    }
}
