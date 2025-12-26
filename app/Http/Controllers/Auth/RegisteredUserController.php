<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        // Kita kirim data departments ke Vue agar bisa dipilih di dropdown
        return Inertia::render('Auth/Register', [
            'departments' => Department::all(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {

        // --- LOGIKA GENERATE ID OTOMATIS ---
        // Format: INT-{Tahun}-{Urutan 4 Digit} -> Contoh: INT-2025-0001
        $year = date('Y');
        $prefix = 'INT-' . $year . '-';

        // Cari user terakhir yang punya employee_id dengan prefix tahun ini
        $lastUser = User::where('employee_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser) {
            // Ambil 4 digit terakhir, ubah jadi int, tambah 1
            $lastNumber = (int) substr($lastUser->employee_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Jika belum ada, mulai dari 1
            $newNumber = 1;
        }

        // Format ulang jadi 4 digit (0001, 0012, dst)
        $newEmployeeId = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'department_id' => null,
            'password' => Hash::make($request->password),
            'employee_id' => $newEmployeeId,
            'total_points' => 0,
            'current_level' => 1,
        ]);

        // --- LOGIKA OTOMATIS ROLE ---
        $rolePeserta = Role::where('name', 'peserta')->first();
        if ($rolePeserta) {
            $user->roles()->attach($rolePeserta->id);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
