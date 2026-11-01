<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * List all employees/staff/couriers for the admin
     */
    public function index(Request $request)
    {
        $query = User::withCount([
            'assignedDeliveries as total_deliveries',
            'assignedDeliveries as active_deliveries' => function ($q) {
                $q->whereIn('delivery_status', ['assigned', 'out_for_delivery']);
            },
            'assignedDeliveries as completed_deliveries' => function ($q) {
                $q->where('delivery_status', 'delivered');
            },
        ])
        ->whereIn('role', ['admin', 'delivery']);

        // Role filter
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('role', 'asc')->latest()->paginate(25);

        return response()->json($employees);
    }

    /**
     * Create a new employee with employee ID
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,delivery,courier',
            'employee_id' => 'nullable|string|max:30|unique:users,employee_id',
            'password' => 'required|string|min:6',
            'assigned_store_ids' => 'nullable|array',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ]);

        // Auto-generate employee_id if omitted
        $employeeId = $validated['employee_id'] ?? null;
        if (empty($employeeId)) {
            $prefix = ($validated['role'] === 'delivery') ? 'RDR' : 'ADM';
            $randomNum = rand(100, 999);
            $employeeId = "{$prefix}-{$randomNum}";
            while (User::where('employee_id', $employeeId)->exists()) {
                $randomNum = rand(100, 999);
                $employeeId = "{$prefix}-{$randomNum}";
            }
        }

        // Auto-generate username if omitted
        $username = $validated['username'] ?? null;
        if (empty($username)) {
            $baseUser = Str::slug(explode('@', $validated['email'])[0], '_');
            $username = $baseUser;
            $c = 1;
            while (User::where('username', $username)->exists()) {
                $username = "{$baseUser}_{$c}";
                $c++;
            }
        }

        $employee = User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'employee_id' => strtoupper($employeeId),
            'assigned_store_ids' => $request->input('assigned_store_ids'),
            'is_active' => true,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Employee {$employee->name} ({$employee->employee_id}) created successfully!",
            'employee' => $employee,
        ], 201);
    }

    /**
     * Update employee details & employee ID
     */
    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => "nullable|string|max:50|unique:users,username,{$id}",
            'email' => "required|email|max:255|unique:users,email,{$id}",
            'role' => 'required|in:admin,delivery,courier',
            'employee_id' => "nullable|string|max:30|unique:users,employee_id,{$id}",
            'assigned_store_ids' => 'nullable|array',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'] ?? $employee->username,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'employee_id' => isset($validated['employee_id']) ? strtoupper($validated['employee_id']) : $employee->employee_id,
            'assigned_store_ids' => $request->has('assigned_store_ids') ? $request->input('assigned_store_ids') : $employee->assigned_store_ids,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $employee->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Employee {$employee->name} updated successfully!",
            'employee' => $employee->fresh(),
        ]);
    }

    /**
     * Toggle active/suspended status
     */
    public function toggleStatus(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        // Guard: prevent suspending oneself if authenticated
        $currentUser = $request->user();
        if ($currentUser && $currentUser->id === $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own administrative account.',
            ], 403);
        }

        $employee->is_active = !$employee->is_active;
        $employee->save();

        $statusWord = $employee->is_active ? 'Activated' : 'Suspended';

        return response()->json([
            'success' => true,
            'message' => "Employee {$employee->name} has been {$statusWord}.",
            'employee' => $employee,
        ]);
    }

    /**
     * Delete employee
     */
    public function destroy(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $currentUser = $request->user();
        if ($currentUser && $currentUser->id === $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Prevent deleting last admin
        if ($employee->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the sole Administrator account.',
            ], 400);
        }

        $name = $employee->name;
        $employeeId = $employee->employee_id;
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => "Employee {$name} ({$employeeId}) has been removed.",
        ]);
    }
}
