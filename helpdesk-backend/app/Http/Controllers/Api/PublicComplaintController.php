<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PublicComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicComplaintController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $complaints = PublicComplaint::query()
            ->with('category:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate($perPage);

        return response()->json($complaints);
    }

    public function show(PublicComplaint $complaint)
    {
        return response()->json(['data' => $complaint->load('category:id,name')]);
    }

    public function adminStore(Request $request)
    {
        $data = $this->validateComplaint($request);
        $complaint = PublicComplaint::create($data);

        return response()->json([
            'message' => 'Pengaduan berhasil ditambahkan.',
            'data' => $complaint->load('category:id,name'),
        ], 201);
    }

    public function update(Request $request, PublicComplaint $complaint)
    {
        $status = $request->validate([
            'status' => 'required|in:new,in_progress,resolved,rejected',
        ])['status'];
        $previousAttachmentPath = $complaint->attachment_path;
        $data = $this->validateComplaint($request);
        $data['status'] = $status;

        if ($request->boolean('remove_attachment') && ! $request->hasFile('attachment') && $complaint->attachment_path) {
            Storage::delete($complaint->attachment_path);
            $data['attachment_path'] = null;
            $data['attachment_name'] = null;
        }

        if ($request->hasFile('attachment')) {
            if ($previousAttachmentPath) {
                Storage::delete($previousAttachmentPath);
            }
        }

        $complaint->update($data);

        return response()->json([
            'message' => 'Pengaduan berhasil diperbarui.',
            'data' => $complaint->fresh()->load('category:id,name'),
        ]);
    }

    public function destroy(PublicComplaint $complaint)
    {
        if ($complaint->attachment_path) {
            Storage::delete($complaint->attachment_path);
        }

        $complaint->delete();

        return response()->json(['message' => 'Pengaduan berhasil dihapus.']);
    }

    public function downloadAttachment(PublicComplaint $complaint)
    {
        abort_unless($complaint->attachment_path && Storage::exists($complaint->attachment_path), 404);

        return Storage::download(
            $complaint->attachment_path,
            $complaint->attachment_name ?: basename($complaint->attachment_path)
        );
    }

    private function validateComplaint(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => 'required|email|max:150',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('public-complaints');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        return $data;
    }

    public function categories()
    {
        return response()->json(
            Category::query()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function captcha()
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $token = Str::random(40);

        Cache::put("complaint_captcha:{$token}", $a + $b, now()->addMinutes(10));

        return response()->json([
            'token' => $token,
            'question' => "{$a} + {$b} = ?",
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => 'required|email|max:150',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            'captcha_token' => 'required|string',
            'captcha_answer' => 'required|numeric',
        ]);

        // Token sekali pakai: dihapus baik jawaban benar maupun salah
        $expected = Cache::pull("complaint_captcha:{$data['captcha_token']}");
        if ($expected === null || (int) $data['captcha_answer'] !== (int) $expected) {
            return response()->json([
                'message' => 'Captcha salah atau kedaluwarsa.',
                'errors' => ['captcha_answer' => ['Captcha salah atau kedaluwarsa.']],
            ], 422);
        }

        $path = $name = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('public-complaints');
            $name = $file->getClientOriginalName();
        }

        $complaint = PublicComplaint::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'attachment_path' => $path,
            'attachment_name' => $name,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Pengaduan berhasil dikirim.',
            'id' => $complaint->id,
        ], 201);
    }
}
