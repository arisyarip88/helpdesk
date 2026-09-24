<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        // Tambahkan relasi status pada eager loading
        $query = User::with(['role', 'department']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tlp', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'status'      => 'success',
            'data'        => $query->latest()->paginate($perPage),
            'roles'       => Role::all(['id', 'name', 'display_name']),
            // Ambil primary key 'kode' dan nama dari tabel departments
            'departments' => Department::all(['kode', 'nama'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id'       => 'required|exists:roles,id',
            
            // Validasi exists menunjuk ke kolom 'kode' pada tabel departments
            'department_id' => 'required|exists:departments,kode',
            'name'          => 'required|string|max:255',
            'username'      => 'required|string|max:50|unique:users,username',
            'email'         => 'required|string|email|max:255|unique:users,email',
            'tlp'           => 'nullable|string|max:20',
            'password'      => 'required|string|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->load(['role', 'status', 'department']);

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil ditambahkan',
            'data'    => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_id'       => 'required|exists:roles,id',
            
            // Validasi exists menunjuk ke kolom 'kode' pada tabel departments
            'department_id' => 'required|exists:departments,kode',
            'name'          => 'required|string|max:255',
            'username'      => 'required|string|max:50|unique:users,username,' . $id,
            'email'         => 'required|string|email|max:255|unique:users,email,' . $id,
            'tlp'           => 'nullable|string|max:20',
            'password'      => 'nullable|string|min:8',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->load(['role', 'status', 'department']);

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil diperbarui',
            'data'    => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil dihapus'
        ]);
    }

    //update byID
    public function updateID(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Input sesuai payload Frontend
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'username'      => 'required|string|max:50|unique:users,username,' . $user->id,
             'email'         => 'required|string|email|max:255',
            'tlp'           => 'nullable|string|max:20',
            'role_id'       => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,kode', // Primary Key departemen berupa varchar 'kode'

            // Validasi Password (Hanya diperiksa jika field password diisi)
            'current_password' => 'required_with:password|current_password',
            'password'         => 'nullable|string|min:8|confirmed',
        ], [
            // Custom pesan error
            'username.unique'                   => 'Username sudah digunakan oleh pengguna lain.',
            'email.unique'                      => 'Email sudah digunakan oleh pengguna lain.',
            'current_password.current_password' => 'Password saat ini tidak cocok.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
            'role_id.exists'                    => 'Role yang dipilih tidak valid.',
            'department_id.exists'              => 'Departemen yang dipilih tidak valid.',
        ]);

        // 2. Logic Pengubahan Password
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Hapus atribut password agar tidak menimpa hash yang ada di DB dengan null
            unset($validated['password']);
        }

        // Hapus current_password dari array validated agar tidak masuk ke querystring update
        unset($validated['current_password']);

        // 3. Simpan Perubahan ke Database
        $user->update($validated);

        // 4. Return Response beserta Relasi Terbaru
        return response()->json([
            'message' => 'Profil berhasil diperbarui!',
            'data'    => $user->load(['role', 'department'])
        ], 200);
    }

   
}
