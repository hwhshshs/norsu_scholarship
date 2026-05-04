<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScholarshipAcademicController extends Controller
{
    public function index()
    {
        $this->bootstrapAcademicStructures();

        $stats = [
            'years_total' => Schema::hasTable('academic_year') ? (int) DB::table('academic_year')->count() : 0,
            'years_active' => Schema::hasTable('academic_year') ? (int) DB::table('academic_year')->where('delete_status', '0')->count() : 0,
            'semesters_total' => Schema::hasTable('academic_semester') ? (int) DB::table('academic_semester')->count() : 0,
            'semesters_active' => Schema::hasTable('academic_semester') ? (int) DB::table('academic_semester')->where('delete_status', '0')->count() : 0,
            'year_levels_total' => Schema::hasTable('grade') ? (int) DB::table('grade')->count() : 0,
            'year_levels_active' => Schema::hasTable('grade') ? (int) DB::table('grade')->where('delete_status', '0')->count() : 0,
            'programs_total' => Schema::hasTable('academic_program') ? (int) DB::table('academic_program')->count() : 0,
            'programs_active' => Schema::hasTable('academic_program') ? (int) DB::table('academic_program')->where('delete_status', '0')->count() : 0,
        ];

        return view('scholarship.academic.index', [
            'stats' => $stats,
        ]);
    }

    public function yearsIndex(Request $request)
    {
        $this->bootstrapAcademicStructures();

        [$search, $status] = $this->extractFilters($request);

        $query = DB::table('academic_year')
            ->select('id', 'label', 'delete_status', 'created_at');

        if ($search !== '') {
            $query->where('label', 'like', '%' . $search . '%');
        }

        $this->applyStatusFilter($query, $status);

        $rows = $query->orderByDesc('id')->limit(500)->get();

        return view('scholarship.academic.years.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function yearsCreate()
    {
        $this->bootstrapAcademicStructures();

        return view('scholarship.academic.years.form', [
            'row' => null,
        ]);
    }

    public function yearsStore(Request $request)
    {
        $this->bootstrapAcademicStructures();

        $validated = $request->validate([
            'label' => 'required|string|max:30',
        ]);

        $label = trim((string) $validated['label']);
        $existing = DB::table('academic_year')->where('label', $label)->first();

        if ($existing) {
            if ((string) ($existing->delete_status ?? '0') === '1') {
                DB::table('academic_year')->where('id', (int) $existing->id)->update([
                    'delete_status' => '0',
                ]);

                $this->backfillAcademicYear($label);

                return redirect()
                    ->route('scholarship-academic.years.index')
                    ->with('success', 'Academic year restored and activated.');
            }

            return back()
                ->withErrors(['label' => 'Academic year already exists.'])
                ->withInput();
        }

        DB::table('academic_year')->insert([
            'label' => $label,
            'delete_status' => '0',
        ]);

        $this->backfillAcademicYear($label);

        return redirect()
            ->route('scholarship-academic.years.index')
            ->with('success', 'Academic year added successfully.');
    }

    public function yearsEdit($year)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $year;
        $row = DB::table('academic_year')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        return view('scholarship.academic.years.form', [
            'row' => $row,
        ]);
    }

    public function yearsUpdate(Request $request, $year)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $year;
        $row = DB::table('academic_year')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:30',
        ]);

        $newLabel = trim((string) $validated['label']);
        $duplicate = DB::table('academic_year')
            ->where('label', $newLabel)
            ->where('id', '<>', $id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['label' => 'Academic year already exists.'])
                ->withInput();
        }

        $oldLabel = trim((string) ($row->label ?? ''));

        DB::beginTransaction();
        try {
            DB::table('academic_year')->where('id', $id)->update([
                'label' => $newLabel,
            ]);

            $this->propagateAcademicYearRename($oldLabel, $newLabel);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('scholarship-academic.years.index')
                ->with('success', 'Unable to update academic year.');
        }

        return redirect()
            ->route('scholarship-academic.years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    public function yearsToggleStatus(Request $request, $year)
    {
        $this->bootstrapAcademicStructures();

        $targetStatus = (string) $request->input('target_status', '');
        if (!in_array($targetStatus, ['0', '1'], true)) {
            return redirect()
                ->route('scholarship-academic.years.index')
                ->with('success', 'Unable to update academic year status.');
        }

        $id = (int) $year;
        $updated = DB::table('academic_year')->where('id', $id)->update([
            'delete_status' => $targetStatus,
        ]);

        if (!$updated) {
            return redirect()
                ->route('scholarship-academic.years.index')
                ->with('success', 'Unable to update academic year status.');
        }

        return redirect()
            ->route('scholarship-academic.years.index')
            ->with('success', $targetStatus === '1' ? 'Academic year set to inactive.' : 'Academic year set to active.');
    }

    public function yearsRemove($year)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $year;
        $row = DB::table('academic_year')->where('id', $id)->first();
        if (!$row) {
            return redirect()
                ->route('scholarship-academic.years.index')
                ->with('success', 'Unable to complete the requested action.');
        }

        if ((string) ($row->delete_status ?? '0') !== '1') {
            DB::table('academic_year')->where('id', $id)->update([
                'delete_status' => '1',
            ]);

            return redirect()
                ->route('scholarship-academic.years.index')
                ->with('success', 'Academic year set to inactive.');
        }

        $label = trim((string) ($row->label ?? ''));
        $dependencies = $this->academicYearDependencies($label);
        $linked = $dependencies['students'] + $dependencies['billing'] + $dependencies['disbursed'];
        if ($linked > 0) {
            return redirect()
                ->route('scholarship-academic.years.index')
                ->with('success', 'Academic year cannot be permanently deleted. Linked rows: Students = ' . $dependencies['students'] . ', Billing = ' . $dependencies['billing'] . ', Disbursed = ' . $dependencies['disbursed'] . '.');
        }

        DB::table('academic_year')->where('id', $id)->delete();

        return redirect()
            ->route('scholarship-academic.years.index')
            ->with('success', 'Academic year permanently deleted.');
    }

    public function semestersIndex(Request $request)
    {
        $this->bootstrapAcademicStructures();

        [$search, $status] = $this->extractFilters($request);

        $query = DB::table('academic_semester')
            ->select('id', 'label', 'delete_status', 'created_at');

        if ($search !== '') {
            $query->where('label', 'like', '%' . $search . '%');
        }

        $this->applyStatusFilter($query, $status);

        $rows = $query->orderBy('id')->limit(500)->get();

        return view('scholarship.academic.semesters.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function semestersCreate()
    {
        $this->bootstrapAcademicStructures();

        return view('scholarship.academic.semesters.form', [
            'row' => null,
        ]);
    }

    public function semestersStore(Request $request)
    {
        $this->bootstrapAcademicStructures();

        $validated = $request->validate([
            'label' => 'required|string|max:60',
        ]);

        $label = $this->normalizeSemesterLabel((string) $validated['label']);
        if ($label === '') {
            return back()
                ->withErrors(['label' => 'Semester label is required.'])
                ->withInput();
        }

        $existing = DB::table('academic_semester')
            ->whereRaw('LOWER(TRIM(label)) = ?', [strtolower($label)])
            ->first();

        if ($existing) {
            if ((string) ($existing->delete_status ?? '0') === '1') {
                DB::table('academic_semester')->where('id', (int) $existing->id)->update([
                    'label' => $label,
                    'delete_status' => '0',
                ]);

                $this->backfillSemester($label);

                return redirect()
                    ->route('scholarship-academic.semesters.index')
                    ->with('success', 'Semester restored and activated.');
            }

            return back()
                ->withErrors(['label' => 'Semester already exists.'])
                ->withInput();
        }

        DB::table('academic_semester')->insert([
            'label' => $label,
            'delete_status' => '0',
        ]);

        $this->backfillSemester($label);

        return redirect()
            ->route('scholarship-academic.semesters.index')
            ->with('success', 'Semester added successfully.');
    }

    public function semestersEdit($semester)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $semester;
        $row = DB::table('academic_semester')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        return view('scholarship.academic.semesters.form', [
            'row' => $row,
        ]);
    }

    public function semestersUpdate(Request $request, $semester)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $semester;
        $row = DB::table('academic_semester')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:60',
        ]);

        $newLabel = $this->normalizeSemesterLabel((string) $validated['label']);
        if ($newLabel === '') {
            return back()
                ->withErrors(['label' => 'Semester label is required.'])
                ->withInput();
        }

        $duplicate = DB::table('academic_semester')
            ->whereRaw('LOWER(TRIM(label)) = ?', [strtolower($newLabel)])
            ->where('id', '<>', $id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['label' => 'Semester already exists.'])
                ->withInput();
        }

        $oldLabel = trim((string) ($row->label ?? ''));

        DB::beginTransaction();
        try {
            DB::table('academic_semester')->where('id', $id)->update([
                'label' => $newLabel,
            ]);

            $this->propagateSemesterRename($oldLabel, $newLabel);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('scholarship-academic.semesters.index')
                ->with('success', 'Unable to update semester.');
        }

        return redirect()
            ->route('scholarship-academic.semesters.index')
            ->with('success', 'Semester updated successfully.');
    }

    public function semestersToggleStatus(Request $request, $semester)
    {
        $this->bootstrapAcademicStructures();

        $targetStatus = (string) $request->input('target_status', '');
        if (!in_array($targetStatus, ['0', '1'], true)) {
            return redirect()
                ->route('scholarship-academic.semesters.index')
                ->with('success', 'Unable to update semester status.');
        }

        $id = (int) $semester;
        $updated = DB::table('academic_semester')->where('id', $id)->update([
            'delete_status' => $targetStatus,
        ]);

        if (!$updated) {
            return redirect()
                ->route('scholarship-academic.semesters.index')
                ->with('success', 'Unable to update semester status.');
        }

        return redirect()
            ->route('scholarship-academic.semesters.index')
            ->with('success', $targetStatus === '1' ? 'Semester set to inactive.' : 'Semester set to active.');
    }

    public function semestersRemove($semester)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $semester;
        $row = DB::table('academic_semester')->where('id', $id)->first();
        if (!$row) {
            return redirect()
                ->route('scholarship-academic.semesters.index')
                ->with('success', 'Unable to complete the requested action.');
        }

        if ((string) ($row->delete_status ?? '0') !== '1') {
            DB::table('academic_semester')->where('id', $id)->update([
                'delete_status' => '1',
            ]);

            return redirect()
                ->route('scholarship-academic.semesters.index')
                ->with('success', 'Semester set to inactive.');
        }

        $label = trim((string) ($row->label ?? ''));
        $dependencies = $this->semesterDependencies($label);
        $linked = $dependencies['students'] + $dependencies['billing_batches'] + $dependencies['billing_rows'] + $dependencies['disbursed'];
        if ($linked > 0) {
            return redirect()
                ->route('scholarship-academic.semesters.index')
                ->with('success', 'Semester cannot be permanently deleted. Linked rows: Students = ' . $dependencies['students'] . ', Billing Batches = ' . $dependencies['billing_batches'] . ', Billing Rows = ' . $dependencies['billing_rows'] . ', Disbursed = ' . $dependencies['disbursed'] . '.');
        }

        DB::table('academic_semester')->where('id', $id)->delete();

        return redirect()
            ->route('scholarship-academic.semesters.index')
            ->with('success', 'Semester permanently deleted.');
    }

    public function yearLevelsIndex(Request $request)
    {
        $this->bootstrapAcademicStructures();

        [$search, $status] = $this->extractFilters($request);

        $query = DB::table('grade')
            ->select('id', 'grade', 'year_level', 'detail', 'delete_status');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%' . $search . '%';
                $builder->where('year_level', 'like', $like)
                    ->orWhere('grade', 'like', $like)
                    ->orWhere('detail', 'like', $like);
            });
        }

        $this->applyStatusFilter($query, $status);

        $rows = $query->orderByDesc('id')->limit(500)->get();

        return view('scholarship.academic.year-levels.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function yearLevelsCreate()
    {
        $this->bootstrapAcademicStructures();

        return view('scholarship.academic.year-levels.form', [
            'row' => null,
        ]);
    }

    public function yearLevelsStore(Request $request)
    {
        $this->bootstrapAcademicStructures();

        $validated = $request->validate([
            'year_level' => 'required|string|max:255',
            'detail' => 'nullable|string|max:2000',
        ]);

        DB::table('grade')->insert([
            'grade' => '',
            'year_level' => trim((string) $validated['year_level']),
            'detail' => trim((string) ($validated['detail'] ?? '')),
            'delete_status' => '0',
        ]);

        return redirect()
            ->route('scholarship-academic.year-levels.index')
            ->with('success', 'Year level added successfully.');
    }

    public function yearLevelsEdit($level)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $level;
        $row = DB::table('grade')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        return view('scholarship.academic.year-levels.form', [
            'row' => $row,
        ]);
    }

    public function yearLevelsUpdate(Request $request, $level)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $level;
        $row = DB::table('grade')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        $validated = $request->validate([
            'year_level' => 'required|string|max:255',
            'detail' => 'nullable|string|max:2000',
        ]);

        DB::table('grade')->where('id', $id)->update([
            'year_level' => trim((string) $validated['year_level']),
            'detail' => trim((string) ($validated['detail'] ?? '')),
        ]);

        return redirect()
            ->route('scholarship-academic.year-levels.index')
            ->with('success', 'Year level updated successfully.');
    }

    public function yearLevelsToggleStatus(Request $request, $level)
    {
        $this->bootstrapAcademicStructures();

        $targetStatus = (string) $request->input('target_status', '');
        if (!in_array($targetStatus, ['0', '1'], true)) {
            return redirect()
                ->route('scholarship-academic.year-levels.index')
                ->with('success', 'Unable to update year level status.');
        }

        $id = (int) $level;
        $updated = DB::table('grade')->where('id', $id)->update([
            'delete_status' => $targetStatus,
        ]);

        if (!$updated) {
            return redirect()
                ->route('scholarship-academic.year-levels.index')
                ->with('success', 'Unable to update year level status.');
        }

        return redirect()
            ->route('scholarship-academic.year-levels.index')
            ->with('success', $targetStatus === '1' ? 'Year level set to inactive.' : 'Year level set to active.');
    }

    public function yearLevelsRemove($level)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $level;
        $row = DB::table('grade')->where('id', $id)->first();
        if (!$row) {
            return redirect()
                ->route('scholarship-academic.year-levels.index')
                ->with('success', 'Unable to complete the requested action.');
        }

        if ((string) ($row->delete_status ?? '0') !== '1') {
            DB::table('grade')->where('id', $id)->update([
                'delete_status' => '1',
            ]);

            return redirect()
                ->route('scholarship-academic.year-levels.index')
                ->with('success', 'Year level set to inactive.');
        }

        $label = $this->gradeLabel((array) $row);
        $studentMatches = 0;
        if (Schema::hasTable('student') && Schema::hasColumn('student', 'year_level') && $label !== '') {
            $studentMatches = (int) DB::table('student')->where('year_level', $label)->count();
        }

        if ($studentMatches > 0) {
            return redirect()
                ->route('scholarship-academic.year-levels.index')
                ->with('success', 'Year level cannot be permanently deleted. Linked students: ' . $studentMatches . '.');
        }

        DB::table('grade')->where('id', $id)->delete();

        return redirect()
            ->route('scholarship-academic.year-levels.index')
            ->with('success', 'Year level permanently deleted.');
    }

    public function programsIndex(Request $request)
    {
        $this->bootstrapAcademicStructures();

        [$search, $status] = $this->extractFilters($request);

        $query = DB::table('academic_program')
            ->select('id', 'name', 'delete_status', 'created_at');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $this->applyStatusFilter($query, $status);

        $rows = $query->orderBy('name')->limit(500)->get();

        return view('scholarship.academic.programs.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function programsCreate()
    {
        $this->bootstrapAcademicStructures();

        return view('scholarship.academic.programs.form', [
            'row' => null,
        ]);
    }

    public function programsStore(Request $request)
    {
        $this->bootstrapAcademicStructures();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $name = trim((string) $validated['name']);
        $existing = DB::table('academic_program')->where('name', $name)->first();

        if ($existing) {
            if ((string) ($existing->delete_status ?? '0') === '1') {
                DB::table('academic_program')->where('id', (int) $existing->id)->update([
                    'delete_status' => '0',
                ]);

                return redirect()
                    ->route('scholarship-academic.programs.index')
                    ->with('success', 'Program restored and activated.');
            }

            return back()
                ->withErrors(['name' => 'Program already exists.'])
                ->withInput();
        }

        DB::table('academic_program')->insert([
            'name' => $name,
            'delete_status' => '0',
        ]);

        return redirect()
            ->route('scholarship-academic.programs.index')
            ->with('success', 'Program added successfully.');
    }

    public function programsEdit($program)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $program;
        $row = DB::table('academic_program')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        return view('scholarship.academic.programs.form', [
            'row' => $row,
        ]);
    }

    public function programsUpdate(Request $request, $program)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $program;
        $row = DB::table('academic_program')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $newName = trim((string) $validated['name']);
        $duplicate = DB::table('academic_program')
            ->where('name', $newName)
            ->where('id', '<>', $id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['name' => 'Program already exists.'])
                ->withInput();
        }

        $oldName = trim((string) ($row->name ?? ''));

        DB::beginTransaction();
        try {
            DB::table('academic_program')->where('id', $id)->update([
                'name' => $newName,
            ]);

            $this->propagateProgramRename($oldName, $newName);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('scholarship-academic.programs.index')
                ->with('success', 'Unable to update program.');
        }

        return redirect()
            ->route('scholarship-academic.programs.index')
            ->with('success', 'Program updated successfully.');
    }

    public function programsToggleStatus(Request $request, $program)
    {
        $this->bootstrapAcademicStructures();

        $targetStatus = (string) $request->input('target_status', '');
        if (!in_array($targetStatus, ['0', '1'], true)) {
            return redirect()
                ->route('scholarship-academic.programs.index')
                ->with('success', 'Unable to update program status.');
        }

        $id = (int) $program;
        $updated = DB::table('academic_program')->where('id', $id)->update([
            'delete_status' => $targetStatus,
        ]);

        if (!$updated) {
            return redirect()
                ->route('scholarship-academic.programs.index')
                ->with('success', 'Unable to update program status.');
        }

        return redirect()
            ->route('scholarship-academic.programs.index')
            ->with('success', $targetStatus === '1' ? 'Program set to inactive.' : 'Program set to active.');
    }

    public function programsRemove($program)
    {
        $this->bootstrapAcademicStructures();

        $id = (int) $program;
        $row = DB::table('academic_program')->where('id', $id)->first();
        if (!$row) {
            return redirect()
                ->route('scholarship-academic.programs.index')
                ->with('success', 'Unable to complete the requested action.');
        }

        if ((string) ($row->delete_status ?? '0') !== '1') {
            DB::table('academic_program')->where('id', $id)->update([
                'delete_status' => '1',
            ]);

            return redirect()
                ->route('scholarship-academic.programs.index')
                ->with('success', 'Program set to inactive.');
        }

        $name = trim((string) ($row->name ?? ''));
        $dependencies = $this->programDependencies($name);
        $linked = $dependencies['students'] + $dependencies['billing'] + $dependencies['disbursed'];
        if ($linked > 0) {
            return redirect()
                ->route('scholarship-academic.programs.index')
                ->with('success', 'Program cannot be permanently deleted. Linked rows: Students = ' . $dependencies['students'] . ', Billing = ' . $dependencies['billing'] . ', Disbursed = ' . $dependencies['disbursed'] . '.');
        }

        DB::table('academic_program')->where('id', $id)->delete();

        return redirect()
            ->route('scholarship-academic.programs.index')
            ->with('success', 'Program permanently deleted.');
    }

    private function extractFilters(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        return [$search, $status];
    }

    private function applyStatusFilter($query, $status)
    {
        if ($status === 'active') {
            $query->where('delete_status', '0');
        } elseif ($status === 'inactive') {
            $query->where('delete_status', '1');
        }
    }

    private function backfillAcademicYear($label)
    {
        if ($label === '') {
            return;
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_academic_year')) {
            DB::table('student')
                ->where(function ($query) {
                    $query->whereNull('scholarship_academic_year')
                        ->orWhere('scholarship_academic_year', '');
                })
                ->update(['scholarship_academic_year' => $label]);
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'academic_year')) {
            DB::table('fees_transaction')
                ->where(function ($query) {
                    $query->whereNull('academic_year')
                        ->orWhere('academic_year', '');
                })
                ->update(['academic_year' => $label]);
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'academic_year')) {
            DB::table('disbursed_transaction')
                ->where(function ($query) {
                    $query->whereNull('academic_year')
                        ->orWhere('academic_year', '');
                })
                ->update(['academic_year' => $label]);
        }
    }

    private function propagateAcademicYearRename($oldLabel, $newLabel)
    {
        if ($oldLabel === '' || $newLabel === '' || $oldLabel === $newLabel) {
            return;
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_academic_year')) {
            DB::table('student')
                ->where('scholarship_academic_year', $oldLabel)
                ->update(['scholarship_academic_year' => $newLabel]);
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'academic_year')) {
            DB::table('fees_transaction')
                ->where('academic_year', $oldLabel)
                ->update(['academic_year' => $newLabel]);
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'academic_year')) {
            DB::table('disbursed_transaction')
                ->where('academic_year', $oldLabel)
                ->update(['academic_year' => $newLabel]);
        }
    }

    private function academicYearDependencies($label)
    {
        if ($label === '') {
            return ['students' => 0, 'billing' => 0, 'disbursed' => 0];
        }

        $students = 0;
        $billing = 0;
        $disbursed = 0;

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_academic_year')) {
            $students = (int) DB::table('student')
                ->where('scholarship_academic_year', $label)
                ->count();
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'academic_year')) {
            $billing = (int) DB::table('fees_transaction')
                ->where('academic_year', $label)
                ->count();
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'academic_year')) {
            $disbursed = (int) DB::table('disbursed_transaction')
                ->where('academic_year', $label)
                ->count();
        }

        return [
            'students' => $students,
            'billing' => $billing,
            'disbursed' => $disbursed,
        ];
    }

    private function normalizeSemesterLabel($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $lower = strtolower(preg_replace('/\s+/', ' ', $value));
        $lower = str_replace('.', '', $lower);

        $map = [
            '1st' => '1st Semester',
            '1st sem' => '1st Semester',
            '1st semester' => '1st Semester',
            'first sem' => '1st Semester',
            'first semester' => '1st Semester',
            'sem 1' => '1st Semester',
            'semester 1' => '1st Semester',
            '2nd' => '2nd Semester',
            '2nd sem' => '2nd Semester',
            '2nd semester' => '2nd Semester',
            'second sem' => '2nd Semester',
            'second semester' => '2nd Semester',
            'sem 2' => '2nd Semester',
            'semester 2' => '2nd Semester',
        ];

        if (array_key_exists($lower, $map)) {
            return $map[$lower];
        }

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function backfillSemester($label)
    {
        if ($label === '') {
            return;
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_semester')) {
            DB::table('student')
                ->where(function ($query) {
                    $query->whereNull('scholarship_semester')
                        ->orWhere('scholarship_semester', '');
                })
                ->update(['scholarship_semester' => $label]);
        }

        if (Schema::hasTable('billing_batch') && Schema::hasColumn('billing_batch', 'semester')) {
            DB::table('billing_batch')
                ->where(function ($query) {
                    $query->whereNull('semester')
                        ->orWhere('semester', '');
                })
                ->update(['semester' => $label]);
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'semester')) {
            DB::table('fees_transaction')
                ->where(function ($query) {
                    $query->whereNull('semester')
                        ->orWhere('semester', '');
                })
                ->update(['semester' => $label]);
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'semester')) {
            DB::table('disbursed_transaction')
                ->where(function ($query) {
                    $query->whereNull('semester')
                        ->orWhere('semester', '');
                })
                ->update(['semester' => $label]);
        }
    }

    private function propagateSemesterRename($oldLabel, $newLabel)
    {
        if ($oldLabel === '' || $newLabel === '' || $oldLabel === $newLabel) {
            return;
        }

        $oldCompare = strtolower(trim((string) $oldLabel));

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_semester')) {
            DB::table('student')
                ->whereRaw('LOWER(TRIM(scholarship_semester)) = ?', [$oldCompare])
                ->update(['scholarship_semester' => $newLabel]);
        }

        if (Schema::hasTable('billing_batch') && Schema::hasColumn('billing_batch', 'semester')) {
            DB::table('billing_batch')
                ->whereRaw('LOWER(TRIM(semester)) = ?', [$oldCompare])
                ->update(['semester' => $newLabel]);
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'semester')) {
            DB::table('fees_transaction')
                ->whereRaw('LOWER(TRIM(semester)) = ?', [$oldCompare])
                ->update(['semester' => $newLabel]);
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'semester')) {
            DB::table('disbursed_transaction')
                ->whereRaw('LOWER(TRIM(semester)) = ?', [$oldCompare])
                ->update(['semester' => $newLabel]);
        }
    }

    private function semesterDependencies($label)
    {
        if ($label === '') {
            return ['students' => 0, 'billing_batches' => 0, 'billing_rows' => 0, 'disbursed' => 0];
        }

        $compare = strtolower(trim((string) $label));
        $students = 0;
        $billingBatches = 0;
        $billingRows = 0;
        $disbursed = 0;

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_semester')) {
            $students = (int) DB::table('student')
                ->whereRaw('LOWER(TRIM(scholarship_semester)) = ?', [$compare])
                ->count();
        }

        if (Schema::hasTable('billing_batch') && Schema::hasColumn('billing_batch', 'semester')) {
            $billingBatches = (int) DB::table('billing_batch')
                ->whereRaw('LOWER(TRIM(semester)) = ?', [$compare])
                ->count();
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'semester')) {
            $billingRows = (int) DB::table('fees_transaction')
                ->whereRaw('LOWER(TRIM(semester)) = ?', [$compare])
                ->count();
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'semester')) {
            $disbursed = (int) DB::table('disbursed_transaction')
                ->whereRaw('LOWER(TRIM(semester)) = ?', [$compare])
                ->count();
        }

        return [
            'students' => $students,
            'billing_batches' => $billingBatches,
            'billing_rows' => $billingRows,
            'disbursed' => $disbursed,
        ];
    }

    private function gradeLabel(array $row)
    {
        $yearLevel = trim((string) ($row['year_level'] ?? ''));
        if ($yearLevel !== '') {
            return $yearLevel;
        }

        return trim((string) ($row['grade'] ?? ''));
    }

    private function propagateProgramRename($oldName, $newName)
    {
        if ($oldName === '' || $newName === '' || $oldName === $newName) {
            return;
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_program')) {
            DB::table('student')
                ->where('scholarship_program', $oldName)
                ->update(['scholarship_program' => $newName]);
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'program')) {
            DB::table('fees_transaction')
                ->where('program', $oldName)
                ->update(['program' => $newName]);
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'program')) {
            DB::table('disbursed_transaction')
                ->where('program', $oldName)
                ->update(['program' => $newName]);
        }
    }

    private function programDependencies($name)
    {
        if ($name === '') {
            return ['students' => 0, 'billing' => 0, 'disbursed' => 0];
        }

        $students = 0;
        $billing = 0;
        $disbursed = 0;

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_program')) {
            $students = (int) DB::table('student')
                ->where('scholarship_program', $name)
                ->count();
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'program')) {
            $billing = (int) DB::table('fees_transaction')
                ->where('program', $name)
                ->count();
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'program')) {
            $disbursed = (int) DB::table('disbursed_transaction')
                ->where('program', $name)
                ->count();
        }

        return [
            'students' => $students,
            'billing' => $billing,
            'disbursed' => $disbursed,
        ];
    }

    private function bootstrapAcademicStructures()
    {
        if (!Schema::hasTable('academic_year')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_year (
                id INT(11) NOT NULL AUTO_INCREMENT,
                label VARCHAR(30) NOT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('academic_year', 'label', "VARCHAR(30) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('academic_year', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
        }

        if (!Schema::hasTable('academic_semester')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_semester (
                id INT(11) NOT NULL AUTO_INCREMENT,
                label VARCHAR(60) NOT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_semester_label (label)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('academic_semester', 'label', "VARCHAR(60) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('academic_semester', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
            $this->addIndexIfMissing('academic_semester', 'uk_semester_label', 'ADD UNIQUE KEY uk_semester_label (label)');
        }

        if (!Schema::hasTable('grade')) {
            DB::statement("CREATE TABLE IF NOT EXISTS grade (
                id INT(11) NOT NULL AUTO_INCREMENT,
                grade VARCHAR(255) NOT NULL DEFAULT '',
                year_level VARCHAR(255) NOT NULL DEFAULT '',
                detail TEXT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('grade', 'grade', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('grade', 'year_level', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('grade', 'detail', "TEXT NULL");
            $this->addColumnIfMissing('grade', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
            DB::statement("UPDATE grade SET year_level = grade WHERE COALESCE(year_level, '') = ''");
        }

        if (!Schema::hasTable('academic_program')) {
            DB::statement("CREATE TABLE IF NOT EXISTS academic_program (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                delete_status ENUM('0','1') NOT NULL DEFAULT '0',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_program_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $this->addColumnIfMissing('academic_program', 'name', "VARCHAR(150) NOT NULL DEFAULT ''");
            $this->addColumnIfMissing('academic_program', 'delete_status', "ENUM('0','1') NOT NULL DEFAULT '0'");
            $this->addIndexIfMissing('academic_program', 'uk_program_name', 'ADD UNIQUE KEY uk_program_name (name)');
        }

        $hasAcademicYear = DB::table('academic_year')->where('delete_status', '0')->exists();
        if (!$hasAcademicYear) {
            DB::table('academic_year')->insert([
                'label' => '2025-2026',
                'delete_status' => '0',
            ]);
        }

        $hasSemester = DB::table('academic_semester')->where('delete_status', '0')->exists();
        if (!$hasSemester) {
            DB::table('academic_semester')->insert([
                ['label' => '1st Semester', 'delete_status' => '0'],
                ['label' => '2nd Semester', 'delete_status' => '0'],
            ]);
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(scholarship_semester), '0'
                FROM student
                WHERE COALESCE(TRIM(scholarship_semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('billing_batch') && Schema::hasColumn('billing_batch', 'semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(semester), '0'
                FROM billing_batch
                WHERE COALESCE(TRIM(semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(semester), '0'
                FROM fees_transaction
                WHERE COALESCE(TRIM(semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'semester')) {
            DB::statement("INSERT INTO academic_semester(label, delete_status)
                SELECT DISTINCT TRIM(semester), '0'
                FROM disbursed_transaction
                WHERE COALESCE(TRIM(semester), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('student') && Schema::hasColumn('student', 'scholarship_program')) {
            DB::statement("INSERT INTO academic_program(name, delete_status)
                SELECT DISTINCT TRIM(scholarship_program), '0'
                FROM student
                WHERE COALESCE(TRIM(scholarship_program), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('fees_transaction') && Schema::hasColumn('fees_transaction', 'program')) {
            DB::statement("INSERT INTO academic_program(name, delete_status)
                SELECT DISTINCT TRIM(program), '0'
                FROM fees_transaction
                WHERE COALESCE(TRIM(program), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }

        if (Schema::hasTable('disbursed_transaction') && Schema::hasColumn('disbursed_transaction', 'program')) {
            DB::statement("INSERT INTO academic_program(name, delete_status)
                SELECT DISTINCT TRIM(program), '0'
                FROM disbursed_transaction
                WHERE COALESCE(TRIM(program), '') <> ''
                ON DUPLICATE KEY UPDATE delete_status = '0'");
        }
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
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
            // Ignore duplicate key creation errors from existing legacy schema.
        }
    }
}
