<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Category::query()->with('department');
        if (! empty($validated['search'])) {
            $query->where(function ($builder) use ($validated) {
                $builder->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhereHas('department', function ($departmentQuery) use ($validated) {
                        $departmentQuery->where('nama', 'like', '%'.$validated['search'].'%');
                    });
            });
        }

        return response()->json($query->latest()->paginate($validated['per_page'] ?? 10));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Category::with('department')->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|string|exists:departments,kode',
        ]);

        $category = Category::create($validated)->load('department');

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|string|exists:departments,kode',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category->load('department'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        Category::findOrFail($id)->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
