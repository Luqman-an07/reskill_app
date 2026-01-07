<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon; // [BARU] Import Carbon untuk waktu

class UserController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Parameter Filter & Sort
        $search = $request->input('search');
        $roleId = $request->input('role_id');
        $deptId = $request->input('department_id');

        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');

        // 2. Build Query
        $query = User::select('users.*')
            ->with(['department', 'roles']);

        // 3. Apply Filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.employee_id', 'like', "%{$search}%");
            });
        }

        if ($roleId) {
            $query->whereHas('roles', fn($q) => $q->where('id', $roleId));
        }

        if ($deptId) {
            $query->where('users.department_id', $deptId);
        }

        // 4. Apply Sorting Server-Side
        switch ($sortField) {
            case 'department_name':
                $query->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                    ->orderBy('departments.department_name', $sortDirection);
                break;

            case 'email':
                $query->orderBy('users.email', $sortDirection);
                break;

            case 'is_active':
                $query->orderBy('users.is_active', $sortDirection);
                break;

            case 'role_name':
                $query->orderBy('users.name', $sortDirection);
                break;

            default:
                $query->orderBy('users.' . $sortField, $sortDirection);
                break;
        }

        // 5. Get Data (Pagination) & Inject Online Status
        $users = $query->paginate(10)
            ->withQueryString()
            ->through(function ($user) {
                // [BARU] Tambahkan atribut is_online ke object user
                $user->is_online = $user->last_activity_at && $user->last_activity_at->gt(Carbon::now()->subMinutes(5));
                return $user;
            });

        // 6. Statistik
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'mentors' => User::whereHas('roles', fn($q) => $q->where('name', 'mentor'))->count(),
            'admins' => User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'departments' => Department::all(),
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'role_id', 'department_id', 'sort', 'direction']),
        ]);
    }

    // ... Method store, update, destroy, toggleStatus TETAP SAMA (Tidak perlu diubah) ...
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8',
        ]);

        $employeeId = 'EMP-' . time();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'employee_id' => $employeeId,
            'department_id' => $validated['department_id'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $role = Role::findById($validated['role_id']);
        $user->assignRole($role);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'department_id' => 'nullable|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        $role = Role::findById($validated['role_id']);
        $user->syncRoles([$role]);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot deactivate your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User has been {$status}.");
    }
}
