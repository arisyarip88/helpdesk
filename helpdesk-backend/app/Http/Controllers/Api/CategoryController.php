<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Category::query()->with('department');
        if ((int) $request->user()->role_id === 3) {
            $query->where('department_id', $request->user()->department_id);
        }
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

    public function show(Request $request, int $id): JsonResponse
    {
        $category = Category::with('department')->findOrFail($id);
        $this->authorizeCategoryUnit($category, $request->user());

        return response()->json($category);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if((int) $user->role_id === 3 && ! $user->department_id, 403, 'Akun belum memiliki unit kerja.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => (int) $user->role_id === 3
                ? ['sometimes', 'string', Rule::in([$user->department_id])]
                : 'required|string|exists:departments,kode',
        ]);
        if ((int) $user->role_id === 3) {
            $validated['department_id'] = $user->department_id;
        }

        $category = Category::create($validated)->load('department');

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $user = $request->user();
        $this->authorizeCategoryUnit($category, $user);
        abort_if((int) $user->role_id === 3 && ! $user->department_id, 403, 'Akun belum memiliki unit kerja.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => (int) $user->role_id === 3
                ? ['sometimes', 'string', Rule::in([$user->department_id])]
                : 'required|string|exists:departments,kode',
        ]);
        if ((int) $user->role_id === 3) {
            $validated['department_id'] = $user->department_id;
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category->load('department'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $this->authorizeCategoryUnit($category, $request->user());
        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }

    private function authorizeCategoryUnit(Category $category, User $user): void
    {
        if ((int) $user->role_id === 3) {
            abort_unless(
                $user->department_id && $category->department_id === $user->department_id,
                403
            );
        }
    }
}
