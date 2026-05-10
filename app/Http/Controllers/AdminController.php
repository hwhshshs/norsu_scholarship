<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function activityLogs(Request $request)
    {
        $query = DB::table('activity_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->select('l.*', 'u.name as user_name', 'u.email as user_email');

        if ($request->filled('module')) {
            $query->where('l.module', $request->module);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('l.action', 'like', $search)
                  ->orWhere('l.description', 'like', $search)
                  ->orWhere('u.name', 'like', $search);
            });
        }

        $logs = $query->orderByDesc('l.created_at')->paginate(50);

        return view('admin.activity-logs', compact('logs'));
    }

    public function staffIndex()
    {
        $users = User::orderBy('name')->get();
        return view('admin.staff-management', compact('users'));
    }

    public function staffStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'User account created successfully.');
    }

    public function staffUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.staff.index')->with('success', 'User account updated successfully.');
    }

    public function staffDelete(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.staff.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->route('admin.staff.index')->with('success', 'User account deleted successfully.');
    }

    public function downloadArchive($id)
    {
        $log = DB::table('activity_logs')->where('id', $id)->first();

        if (!$log || !$log->file_path || !\Illuminate\Support\Facades\Storage::exists($log->file_path)) {
            return back()->with('error', 'The archived file could not be found.');
        }

        return \Illuminate\Support\Facades\Storage::download(
            $log->file_path, 
            $log->original_filename ?? 'archived_file'
        );
    }
}
