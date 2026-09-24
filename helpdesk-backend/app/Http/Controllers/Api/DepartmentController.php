<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Department::query();

        // Fitur Search untuk Kode, Nama, dan Ketua Departemen
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('ketua', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:departments,kode',
            'nama' => 'required|string|max:255',
            'ketua' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $department = Department::create($validated);

        return response()->json([
            'message' => 'Departemen berhasil ditambahkan',
            'data' => $department
        ], 201);
    }

    public function update(Request $request, $kode)
    {
        $department = Department::findOrFail($kode);

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'kode')->ignore($kode, 'kode')
            ],
            'nama' => 'required|string|max:255',
            'ketua' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $department->update($validated);

        return response()->json([
            'message' => 'Departemen berhasil diperbarui',
            'data' => $department
        ]);
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json(['message' => 'Departemen berhasil dihapus']);
    }
}