<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
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

        return view('billing.index', compact('batches'));
    }

    public function create()
    {
        return view('billing.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program' => 'required|string|max:100',
            'semester' => 'required|string|max:50',
            'batch' => 'nullable|string|max:50',
            'ay' => 'required|string|max:20',
            'region' => 'nullable|string|max:100',
            'billing_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'ada_date' => 'nullable|date',
            'ada_no' => 'nullable|string|max:100',
            'admin_cost' => 'nullable|numeric|min:0',
            'or_number' => 'nullable|string|max:100',
            'or_date' => 'nullable|date',
            'disbursed_count' => 'nullable|integer|min:0',
            'scholar_file' => 'nullable|file|mimes:csv,txt|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('scholar_file')) {
            $filePath = $request->file('scholar_file')->store('scholar_files');
        }

        DB::beginTransaction();
        try {
            $batchId = DB::table('billing_batches')->insertGetId([
                'program' => $validated['program'],
                'semester' => $validated['semester'],
                'batch' => $validated['batch'],
                'ay' => $validated['ay'],
                'region' => $validated['region'],
                'billing_date' => $validated['billing_date'],
                'amount' => $validated['amount'] ?: 0,
                'ada_date' => $validated['ada_date'],
                'ada_no' => $validated['ada_no'],
                'admin_cost' => $validated['admin_cost'] ?: 0,
                'or_number' => $validated['or_number'],
                'or_date' => $validated['or_date'],
                'disbursed_count' => $validated['disbursed_count'] ?: 0,
                'scholar_file' => $filePath,
                'scholar_count' => 0, // Will be updated if CSV uploaded
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $scholarCount = 0;

            if ($filePath) {
                $handle = fopen(storage_path('app/' . $filePath), "r");
                $header = fgetcsv($handle, 1000, ",");
                
                // Expecting simple format: Name, ID No
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 1) continue;
                    
                    $name = trim($data[0]);
                    $idNo = isset($data[1]) ? trim($data[1]) : null;
                    
                    if (empty($name)) continue;

                    $studentId = null;
                    if ($idNo) {
                        $student = DB::table('students')->where('student_id_no', $idNo)->first();
                        if ($student) {
                            $studentId = $student->id;
                        }
                    }

                    DB::table('billing_scholars')->insert([
                        'billing_batch_id' => $batchId,
                        'student_id' => $studentId,
                        'student_name' => $name,
                        'student_id_no' => $idNo,
                        'created_at' => now(),
                    ]);
                    $scholarCount++;
                }
                fclose($handle);

                DB::table('billing_batches')->where('id', $batchId)->update(['scholar_count' => $scholarCount]);
            }

            DB::commit();

            $this->logActivity('Billing', 'Created batch', "Created batch for {$validated['program']} AY {$validated['ay']} with $scholarCount scholars.");

            return redirect()->route('billing.index')->with('success', 'Billing batch created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing create error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'An error occurred while saving the billing batch.');
        }
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

        return view('billing.show', compact('batch', 'scholars'));
    }

    public function edit($id)
    {
        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) abort(404);

        return view('billing.edit', compact('batch'));
    }

    public function update(Request $request, $id)
    {
        $batch = DB::table('billing_batches')->where('id', $id)->first();
        if (!$batch) abort(404);

        $validated = $request->validate([
            'program' => 'required|string|max:100',
            'semester' => 'required|string|max:50',
            'batch' => 'nullable|string|max:50',
            'ay' => 'required|string|max:20',
            'region' => 'nullable|string|max:100',
            'billing_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'ada_date' => 'nullable|date',
            'ada_no' => 'nullable|string|max:100',
            'admin_cost' => 'nullable|numeric|min:0',
            'or_number' => 'nullable|string|max:100',
            'or_date' => 'nullable|date',
            'disbursed_count' => 'nullable|integer|min:0',
            'scholar_file' => 'nullable|file|mimes:csv,txt|max:2048',
        ]);

        $updateData = [
            'program' => $validated['program'],
            'semester' => $validated['semester'],
            'batch' => $validated['batch'],
            'ay' => $validated['ay'],
            'region' => $validated['region'],
            'billing_date' => $validated['billing_date'],
            'amount' => $validated['amount'] ?: 0,
            'ada_date' => $validated['ada_date'],
            'ada_no' => $validated['ada_no'],
            'admin_cost' => $validated['admin_cost'] ?: 0,
            'or_number' => $validated['or_number'],
            'or_date' => $validated['or_date'],
            'disbursed_count' => $validated['disbursed_count'] ?: 0,
            'updated_at' => now(),
        ];

        DB::beginTransaction();
        try {
            if ($request->hasFile('scholar_file')) {
                $filePath = $request->file('scholar_file')->store('scholar_files');
                $updateData['scholar_file'] = $filePath;

                // Delete old scholars and re-import
                DB::table('billing_scholars')->where('billing_batch_id', $id)->delete();

                $scholarCount = 0;
                $handle = fopen(storage_path('app/' . $filePath), "r");
                $header = fgetcsv($handle, 1000, ",");
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 1) continue;
                    
                    $name = trim($data[0]);
                    $idNo = isset($data[1]) ? trim($data[1]) : null;
                    
                    if (empty($name)) continue;

                    $studentId = null;
                    if ($idNo) {
                        $student = DB::table('students')->where('student_id_no', $idNo)->first();
                        if ($student) {
                            $studentId = $student->id;
                        }
                    }

                    DB::table('billing_scholars')->insert([
                        'billing_batch_id' => $id,
                        'student_id' => $studentId,
                        'student_name' => $name,
                        'student_id_no' => $idNo,
                        'created_at' => now(),
                    ]);
                    $scholarCount++;
                }
                fclose($handle);
                $updateData['scholar_count'] = $scholarCount;
            }

            DB::table('billing_batches')->where('id', $id)->update($updateData);
            
            DB::commit();

            $this->logActivity('Billing', 'Updated batch', "Updated batch ID: $id.");

            return redirect()->route('billing.index')->with('success', 'Billing batch updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'An error occurred while updating the billing batch.');
        }
    }

    private function logActivity($module, $action, $description)
    {
        DB::table('activity_logs')->insert([
            'user_id' => auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
