<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class OnboardingController extends Controller
{
    public function update(Request $request)
    {
        // 1. Validasi: Pastikan ID departemen valid ada di tabel departments
        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ], [
            'department_id.required' => 'Silakan pilih departemen Anda.',
            'department_id.exists' => 'Departemen tidak valid.',
        ]);

        /** @var \App\Models\User $user */
        // 2. Update User
        $user = Auth::user();

        // Kita gunakan forceFill atau update biasa jika sudah di fillable
        $user->update([
            'department_id' => $request->department_id
        ]);

        // 3. Kembali ke halaman sebelumnya (dashboard akan reload otomatis)
        return back()->with('success', 'Data berhasil disimpan. Selamat datang!');
    }
}
