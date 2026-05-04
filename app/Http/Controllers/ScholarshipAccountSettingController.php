<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ScholarshipAccountSettingController extends Controller
{
    public function index()
    {
        return view('scholarship.account-setting.index');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'oldpassword' => 'required|string',
            'newpassword' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $storedHash = (string) ($user->password ?? '');
        $oldPassword = (string) $validated['oldpassword'];

        $isLegacyMd5 = strlen($storedHash) === 32 && ctype_xdigit($storedHash);
        $validOldPassword = Hash::check($oldPassword, $storedHash)
            || ($isLegacyMd5 && hash_equals(strtolower($storedHash), md5($oldPassword)));

        if (!$validOldPassword) {
            return back()->withErrors([
                'oldpassword' => 'Wrong old password.',
            ])->withInput();
        }

        $user->password = Hash::make((string) $validated['newpassword']);
        $user->save();

        return redirect()
            ->route('scholarship-account-setting.index')
            ->with('success', 'Password changed successfully.');
    }
}
