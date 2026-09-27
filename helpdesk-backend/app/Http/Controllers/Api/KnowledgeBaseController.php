<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use App\Models\UnansweredChatQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseController extends Controller
{
    /**
     * Tampilkan data pengetahuan dengan fitur Search, Pagination & Per Page
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        $query = KnowledgeBase::query();

        // Fitur Pencarian berdasarkan Question, Answer, atau Keywords
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%")
                  ->orWhere('keywords', 'LIKE', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate($perPage);

        return response()->json($data, 200);
    }

    /**
     * Tampilkan detail data pengetahuan berdasarkan ID
     */
    public function show(KnowledgeBase $knowledgeBase)
    {
        return response()->json([
            'data' => $knowledgeBase
        ], 200);
    }

    /**
     * Simpan data pengetahuan baru
     */
    public function store(Request $request)
    {
        // Konversi string pisah koma pada keywords menjadi array jika dikirim berupa string
        if (is_string($request->keywords)) {
            $request->merge([
                'keywords' => array_values(array_filter(array_map('trim', explode(',', $request->keywords))))
            ]);
        }

        // Konversi options jika terkirim berupa string JSON dari frontend
        if (is_string($request->options)) {
            $request->merge([
                'options' => json_decode($request->options, true) ?? []
            ]);
        }

        $validated = $request->validate([
            'question'      => 'required|string|max:255',
            'keywords'      => 'required|array|min:1',
            'keywords.*'    => 'string',
            'response_type' => 'nullable|in:text,options',
            'answer'        => 'required|string',
            'options'       => 'nullable|array',
            'options.*.label' => 'required_with:options|string',
            'options.*.value' => 'required_with:options|string',
            'is_active'     => 'boolean',
        ]);

        // Default response_type jika kosong
        $validated['response_type'] = $validated['response_type'] ?? 'text';

        $kb = KnowledgeBase::create($validated);

        return response()->json([
            'message' => 'Pengetahuan berhasil ditambahkan',
            'data'    => $kb
        ], 201);
    }

    /**
     * Perbarui data pengetahuan
     */
    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        if (is_string($request->keywords)) {
            $request->merge([
                'keywords' => array_values(array_filter(array_map('trim', explode(',', $request->keywords))))
            ]);
        }

        if (is_string($request->options)) {
            $request->merge([
                'options' => json_decode($request->options, true) ?? []
            ]);
        }

        $validated = $request->validate([
            'question'      => 'required|string|max:255',
            'keywords'      => 'required|array|min:1',
            'keywords.*'    => 'string',
            'response_type' => 'nullable|in:text,options',
            'answer'        => 'required|string',
            'options'       => 'nullable|array',
            'options.*.label' => 'required_with:options|string',
            'options.*.value' => 'required_with:options|string',
            'is_active'     => 'boolean',
        ]);

        // Jika response_type diset ke text, kosongkan opsi pilihan
        if (($validated['response_type'] ?? 'text') === 'text') {
            $validated['options'] = [];
        }

        $knowledgeBase->update($validated);

        return response()->json([
            'message' => 'Pengetahuan berhasil diperbarui',
            'data'    => $knowledgeBase
        ], 200);
    }

    /**
     * Hapus data pengetahuan
     */
    public function destroy(KnowledgeBase $knowledgeBase)
    {
        UnansweredChatQuestion::where('knowledge_base_id', $knowledgeBase->id)
            ->update(['knowledge_base_id' => null, 'resolved_at' => null]);

        $knowledgeBase->delete();

        return response()->json([
            'message' => 'Pengetahuan berhasil dihapus'
        ], 200);
    }

    public function unanswered(Request $request)
    {
        $questions = UnansweredChatQuestion::whereNull('resolved_at')
            ->when($request->filled('search'), fn ($query) => $query->where('question', 'like', '%' . $request->input('search') . '%'))
            ->latest('last_asked_at')
            ->paginate(20);

        return response()->json($questions);
    }

    public function convertUnanswered(Request $request, UnansweredChatQuestion $question)
    {
        abort_if($question->resolved_at, 422, 'Pertanyaan ini sudah ditangani.');

        $validated = $request->validate([
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required|string|max:80',
            'answer' => 'required|string|max:5000',
        ]);

        $knowledgeBase = DB::transaction(function () use ($validated, $question) {
            $knowledgeBase = KnowledgeBase::create([
                'question' => $question->question,
                'keywords' => $validated['keywords'],
                'response_type' => 'text',
                'answer' => $validated['answer'],
                'options' => [],
                'is_active' => true,
            ]);

            $question->update([
                'knowledge_base_id' => $knowledgeBase->id,
                'resolved_at' => now(),
            ]);

            return $knowledgeBase;
        });

        return response()->json([
            'message' => 'Knowledge berhasil dibuat dari pertanyaan chatbot.',
            'data' => $knowledgeBase,
        ], 201);
    }

    public function destroyUnanswered(UnansweredChatQuestion $question)
    {
        abort_if($question->resolved_at, 404);
        $question->delete();

        return response()->json(['message' => 'Pertanyaan belum terjawab berhasil dihapus.']);
    }

    /**
     * POST /api/chatbot/ask
     * Endpoint publik yang dipanggil oleh Chatbot AI di Landing Page Nuxt.
     */
    public function searchAnswer(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $userMessage = strtolower($request->input('message'));

        // Ambil hanya knowledge base yang statusnya aktif
        $activeKnowledgeBases = KnowledgeBase::where('is_active', true)->get();

        // Cari kecocokan kata kunci (keyword matching)
        foreach ($activeKnowledgeBases as $kb) {
            $keywords = is_string($kb->keywords) ? json_decode($kb->keywords, true) : $kb->keywords;

            if (is_array($keywords)) {
                foreach ($keywords as $keyword) {
                    if (!empty($keyword) && str_contains($userMessage, strtolower($keyword))) {
                        
                        // Parse options jika berupa string JSON di DB
                        $options = is_string($kb->options) ? json_decode($kb->options, true) : ($kb->options ?? []);

                        return response()->json([
                            'found'         => true,
                            'answer'        => $kb->answer,
                            'response_type' => $kb->response_type ?? 'text',
                            'options'       => $kb->response_type === 'options' ? $options : []
                        ], 200);
                    }
                }
            }
        }

        $question = trim($request->input('message'));
        $normalizedQuestion = mb_strtolower($question);
        $normalizedQuestion = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalizedQuestion) ?? $normalizedQuestion;
        $normalizedQuestion = trim(preg_replace('/\s+/u', ' ', $normalizedQuestion) ?? $normalizedQuestion);
        $questionHash = hash('sha256', $normalizedQuestion ?: mb_strtolower($question));

        $unansweredQuestion = UnansweredChatQuestion::firstOrNew(['question_hash' => $questionHash]);
        if (!$unansweredQuestion->exists) {
            $unansweredQuestion->question = $question;
            $unansweredQuestion->occurrences = 0;
        } elseif ($unansweredQuestion->resolved_at) {
            $unansweredQuestion->resolved_at = null;
            $unansweredQuestion->knowledge_base_id = null;
        }
        $unansweredQuestion->occurrences++;
        $unansweredQuestion->last_asked_at = now();
        $unansweredQuestion->save();

        // Jawaban default jika tidak ada kata kunci yang cocok
        return response()->json([
            'found'         => false,
            'answer'        => "Maaf, saya belum menemukan jawaban terkait pertanyaan Anda. Silakan pilih menu di bawah ini atau hubungi Admin Helpdesk.",
            'response_type' => 'options',
            'options'       => [
                ['label' => 'Cara buat tiket baru?', 'value' => 'buat_tiket'],
                ['label' => 'Lupa password akun', 'value' => 'lupa_password'],
                ['label' => 'Jam operasional layanan', 'value' => 'jam_operasional']
            ]
        ], 200);
    }
}