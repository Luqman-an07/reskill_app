<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    /**
     * Menampilkan daftar departemen.
     * Menggunakan pagination dan pencarian sederhana.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $departments = Department::query()
            ->withCount('users') // Menghitung jumlah peserta untuk badge di tabel
            ->when($search, function ($query, $search) {
                $query->where('department_name', 'like', "%{$search}%");
            })
            ->orderBy('department_name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Menyimpan departemen baru.
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'department_name' => 'required|string|max:255|unique:departments,department_name',
            'description' => 'nullable|string|max:500',
        ]);

        // 1. Bersihkan Input & Pecah Kata
        // Ubah ke huruf besar dan hilangkan karakter aneh selain huruf/angka/spasi
        $cleanName = preg_replace('/[^A-Z0-9 ]/', '', strtoupper($request->department_name));
        $words = explode(' ', $cleanName);
        $code = '';

        // 2. Generate Base Code (2 Karakter Awal)
        if (count($words) >= 2) {
            // Kasus: "Human Resources" -> Ambil "H" dan "R" -> "HR"
            $code = substr($words[0], 0, 1) . substr($words[1], 0, 1);
        } else {
            // Kasus: "Marketing" -> Ambil "M" dan "A" -> "MA"
            // Jika kata sangat pendek (misal "IT"), ambil seadanya
            $code = substr($words[0], 0, 2);
        }

        // Fallback: Jika hasil code kosong atau < 2 huruf (misal nama cuma "A"), padding dengan 'X'
        if (strlen($code) < 2) {
            $code = str_pad($code, 2, 'X');
        }

        // 3. Cek Duplikat & Tambah Suffix Huruf
        // Jika "HR" sudah ada, loop akan mencoba: "HRA", "HRB", "HRC" ... "HRZ", "HRAA"
        $baseCode = $code;
        $suffix = 'A';

        while (Department::where('department_code', $code)->exists()) {
            $code = $baseCode . $suffix;
            $suffix++; // Fitur unik PHP: 'A'++ menjadi 'B', 'Z'++ menjadi 'AA'
        }

        // Simpan ke Database
        Department::create([
            'department_name' => $request->department_name,
            'department_code' => $code, // Hasil akhir (Contoh: HR atau HRA)
            'description' => $request->description,
        ]);

        return back()->with('success', 'Departemen berhasil ditambahkan.');
    }

    /**
     * Memperbarui data departemen.
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'department_name' => 'required|string|max:255|unique:departments,department_name,' . $department->id,
            'description' => 'nullable|string|max:500',
        ]);

        $department->update([
            'department_name' => $request->department_name,
            'description' => $request->description,
            // Kita tidak mengubah department_code saat update agar data relasi aman
        ]);

        return back()->with('success', 'Departemen diperbarui.');
    }

    /**
     * Menghapus departemen.
     * Dilindungi: Tidak bisa hapus jika masih ada karyawan di dalamnya.
     */
    public function destroy(Department $department)
    {
        // Cek apakah ada user di departemen ini (Relasi users())
        if ($department->users()->exists()) {
            return back()->with('error', 'Gagal menghapus: Masih ada peserta aktif di departemen ini.');
        }

        $department->delete();
        return back()->with('success', 'Departemen berhasil dihapus.');
    }
}
