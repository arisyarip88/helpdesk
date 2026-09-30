<?php

namespace App\Http\Controllers\Api;

use App\Exports\TicketsExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketStatusNotification;
use App\Models\TicketHandlingSetting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roleId = $user ? $user->role_id : $request->input('role_id');
        if ($roleId == 4) {
            $pg = 4;
        } else {
            $pg = 10;
        }
        $departmentId = $request->input('department_id');
        $search = $request->input('search');
        $statusId = $request->input('status_id');
        $prioritas = $request->input('prioritas');
        $perPage = $request->input('per_page', $pg);

        $query = Ticket::with(['user', 'category.department', 'status']);

        // Filter berdasarkan Role
        if ($roleId == 4) {
            // Role 4: Hanya tampilkan tiket milik user sendiri[cite: 5]
            $query->where('user_id', $user ? $user->id : $request->input('user_id'));
        } elseif ($roleId == 3) {
            // Role 3 selalu dibatasi ke departemen user, bukan nilai dari request.
            $query->whereHas('category', fn ($category) => $category->where('department_id', $user?->department_id))
                // ->where('status_id', '!=', 1)
                ;
        } elseif (in_array((int) $roleId, [1, 2], true) && $request->filled('department_id')) {
            $query->whereHas('category', fn ($category) => $category->where('department_id', $departmentId));
        }

        $overdueCount = 0;
        $handlingSetting = null;
        if (in_array((int) $roleId, [1, 2, 3], true)) {
            $handlingSetting = TicketHandlingSetting::firstOrCreate(
                ['id' => 1],
                ['max_hours' => 24, 'alert_mode' => 'automatic']
            );
            $overdueCount = (clone $query)
                ->setEagerLoads([])
                ->overdue($handlingSetting->max_hours)
                ->count();
        }

        // Search Filter[cite: 5]
        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_tiket', 'like', "%{$search}%")
                    ->orWhere('judul', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $statusId);
        }

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $prioritas);
        }

        if ($request->boolean('new_messages')) {
            $ticketIds = collect(explode(',', (string) $request->input('new_message_ticket_ids', '')))
                ->filter(fn ($id) => ctype_digit($id))
                ->map(fn ($id) => (int) $id)
                ->all();
            $query->whereIn('tickets.id', $ticketIds);
        }

        if ($request->boolean('overdue')) {
            $handlingSetting ??= TicketHandlingSetting::firstOrCreate(
                ['id' => 1],
                ['max_hours' => 24, 'alert_mode' => 'automatic']
            );

            $query->overdue($handlingSetting->max_hours);
        }

        $tickets = $query->latest()->paginate($perPage);

        // Rekap Count per Status[cite: 5]
        $statusCountsQuery = Ticket::query();
        if ($roleId == 4) {
            $statusCountsQuery->where('user_id', $user ? $user->id : $request->input('user_id'));
        } elseif ($roleId == 3) {
            $statusCountsQuery->whereHas('category', fn ($category) => $category->where('department_id', $user?->department_id))
                ;
        } elseif ($request->filled('department_id')) {
            $statusCountsQuery->whereHas('category', fn ($category) => $category->where('department_id', $departmentId));
        }

        $statusCounts = $statusCountsQuery->selectRaw('status_id, count(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        $statuses = Status::query()
            ->get()
            ->map(function ($status) use ($statusCounts) {
                $status->count = $statusCounts->get($status->id, 0);

                return $status;
            });

        return response()->json([
            'data' => $tickets,
            'statuses' => $statuses,
            'departments' => Department::all(),
            'categories' => Category::with('department')->orderBy('name')->get(),
            'overdue_count' => $overdueCount,
        ]);
    }

    public function chatNotifications(Request $request)
    {
        $user = $request->user();
        $roleId = (int) $user?->role_id;

        abort_unless(in_array($roleId, [1, 2, 3], true), 403);

        $latestMessageIds = TicketMessage::query()
            ->selectRaw('MAX(id)')
            ->groupBy('ticket_id');

        $messages = TicketMessage::query()
            ->whereIn('ticket_messages.id', $latestMessageIds)
            ->whereHas('ticket', function ($query) use ($roleId, $user) {
                if ($roleId === 3) {
                    $query->whereHas('category', fn ($category) =>
                        $category->where('department_id', $user->department_id)
                    );
                }
            })
            ->with([
                'ticket:id,nomor_tiket,judul',
                'user:id,name,role_id',
            ])
            ->orderByDesc('ticket_messages.created_at')
            ->orderByDesc('ticket_messages.id')
            ->get();

        return response()->json(['data' => $messages]);
    }

    public function statusNotifications(Request $request)
    {
        $user = $request->user();
        $roleId = (int) $user?->role_id;

        abort_unless(in_array($roleId, [1, 2, 3, 4], true), 403);

        $notifications = TicketStatusNotification::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->when($roleId === 3, fn ($query) => $query->whereHas(
                'ticket.category',
                fn ($category) => $category->where('department_id', $user->department_id)
            ))
            ->when($roleId === 4, fn ($query) => $query->whereHas(
                'ticket',
                fn ($ticket) => $ticket->where('user_id', $user->id)
            ))
            ->with([
                'ticket:id,nomor_tiket,judul,status_id',
                'ticket.status:id,name',
                'previousStatus:id,name',
                'actor:id,name',
            ])
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json(['data' => $notifications]);
    }

    public function markStatusNotificationRead(Request $request, TicketStatusNotification $notification)
    {
        abort_unless((int) $notification->recipient_id === (int) $request->user()?->id, 404);

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function exportPdf(Request $request)
    {
        $tickets = $this->exportQuery($request)->get();

        return Pdf::loadView('exports.tickets-pdf', compact('tickets'))
            ->download('daftar-tiket.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new TicketsExport($this->exportQuery($request)->get()),
            'daftar-tiket.xlsx'
        );
    }

    private function exportQuery(Request $request)
    {
        $user = $request->user();
        $roleId = $user?->role_id ?? $request->input('role_id');

        $query = Ticket::with(['user', 'category.department', 'status'])->latest();

        if ($roleId == 4) {
            $query->where('user_id', $user?->id ?? $request->input('user_id'));
        } elseif ($roleId == 3) {
            $query->whereHas('category', fn ($category) => $category->where('department_id', $user?->department_id))
                ->where('status_id', '!=', 1);
        } elseif ($request->filled('department_id')) {
            $departmentId = $request->input('department_id');
            $query->whereHas('category', fn ($category) => $category->where('department_id', $departmentId));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('nomor_tiket', 'like', "%{$search}%")
                    ->orWhere('judul', 'like', "%{$search}%");
            });
        }

        return $query
            ->when($request->filled('status_id'), fn ($query) => $query->where('status_id', $request->input('status_id')))
            ->when($request->filled('prioritas'), fn ($query) => $query->where('prioritas', $request->input('prioritas')));
    }

    private function handleLampiranUpload(Request $request, ?Ticket $ticket = null, array &$validated = []): ?string
    {
        if (! $request->hasFile('lampiran')) {
            return $ticket?->lampiran;
        }

        if ($ticket?->lampiran && Storage::disk('public')->exists($ticket->lampiran)) {
            Storage::disk('public')->delete($ticket->lampiran);
        }

        $validated['lampiran'] = $request->file('lampiran')->store('lampiran_tiket', 'public');

        return $validated['lampiran'];
    }

    public function store(Request $request)
    {
        $userId = $request->user() ? $request->user()->id : $request->input('user_id');

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'category_id' => 'required|integer|exists:categories,id',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'prioritas' => 'nullable|in:low,medium,high,urgent',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $lampiranPath = $this->handleLampiranUpload($request, null, $validated);
        $category = Category::findOrFail($validated['category_id']);

        $ticket = Ticket::create([
            'nomor_tiket' => 'TK-'.strtoupper(uniqid()),
            'user_id' => $userId ?? $validated['user_id'],
            'category_id' => $category->id,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'prioritas' => $validated['prioritas'] ?? 'medium',
            'status_id' => 1, // Default status: New[cite: 5]
            'lampiran' => $lampiranPath,
        ]);

        return response()->json([
            'message' => 'Tiket berhasil dibuat',
            'data' => $ticket->load(['user', 'category.department', 'status']),
        ], 201);
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user', 'category.department', 'status', 'messages.user'])->findOrFail($id);

        return response()->json(['data' => $ticket]);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $previousStatusId = (int) $ticket->status_id;

        if ($request->exists('category_id')) {
            abort_unless(in_array((int) $request->user()?->role_id, [1, 2, 4], true), 403);
        }

        $validated = $request->validate([
            'status_id' => 'sometimes|exists:statuses,id',
            'category_id' => (int) $request->user()?->role_id === 4
                ? 'required|integer|exists:categories,id'
                : 'sometimes|nullable|integer|exists:categories,id',
            'judul' => 'sometimes|string|max:255',
            'deskripsi' => 'sometimes|string',
            'comment' => 'nullable|string',
            'prioritas' => 'sometimes|in:low,medium,high,urgent',
            'lampiran' => 'sometimes|nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('lampiran')) {
            $this->handleLampiranUpload($request, $ticket, $validated);
        }

        if (isset($validated['status_id'])) {
            $statusId = (int) $validated['status_id'];
            $validated['terselesaikan_pada'] = in_array($statusId, [4, 5], true)
                ? now()
                : null;
            $validated['resolved_by'] = $statusId === 4 && (int) $request->user()?->role_id === 3
                ? $request->user()->id
                : null;
            if ($statusId !== 4) {
                $validated['rating'] = null;
            }
        }

        DB::transaction(function () use ($ticket, $validated, $previousStatusId, $request) {
            $ticket->update($validated);

            if (isset($validated['status_id']) && (int) $validated['status_id'] !== $previousStatusId) {
                $this->createStatusNotifications($ticket, $previousStatusId, $request->user());
            }
        });

        return response()->json([
            'message' => 'Tiket berhasil diperbarui',
            'data' => $ticket->load(['user', 'category.department', 'status']),
        ]);
    }

    public function rate(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        abort_unless((int) $ticket->user_id === (int) $request->user()->id, 403);
        abort_unless((int) $ticket->status_id === 4, 422, 'Rating hanya dapat diberikan untuk tiket yang sudah ditutup.');

        if ($ticket->rating !== null) {
            return response()->json(['message' => 'Rating untuk tiket ini sudah diberikan.'], 422);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
        ]);

        $ticket->update($validated);

        return response()->json([
            'message' => 'Terima kasih atas rating yang diberikan.',
            'data' => $ticket,
        ]);
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->lampiran) {
            Storage::disk('public')->delete($ticket->lampiran);
        }

        $ticket->delete();

        return response()->json(['message' => 'Tiket berhasil dihapus']);
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|distinct|exists:tickets,id',
            'action' => 'required|in:delete,change_status,change_priority,send_warning',
            'value' => 'nullable',
            'message' => 'nullable|string|max:1000',
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];
        $message = '';

        if ($action === 'delete') {
            Ticket::whereIn('id', $ids)->delete();
            $message = count($ids).' tiket berhasil dihapus.';
        } elseif ($action === 'change_status') {
            $statusId = (int) $request->validate([
                'value' => 'required|integer|exists:statuses,id',
            ])['value'];
            $updates = [
                'status_id' => $statusId,
                'terselesaikan_pada' => in_array($statusId, [4, 5], true) ? now() : null,
                'resolved_by' => $statusId === 4 && (int) $request->user()?->role_id === 3
                    ? $request->user()->id
                    : null,
                'rating' => null,
            ];
            $ticketsWithPreviousStatus = Ticket::with('category')
                ->whereIn('id', $ids)
                ->where('status_id', '!=', $statusId)
                ->get();
            DB::transaction(function () use ($ids, $updates, $ticketsWithPreviousStatus, $statusId, $request) {
                Ticket::whereIn('id', $ids)->update($updates);
                foreach ($ticketsWithPreviousStatus as $ticket) {
                    $previousStatusId = (int) $ticket->status_id;
                    $ticket->status_id = $statusId;
                    $this->createStatusNotifications($ticket, $previousStatusId, $request->user());
                }
            });
            $message = 'Status untuk '.count($ids).' tiket berhasil diperbarui.';
        } elseif ($action === 'change_priority') {
            $priority = $request->validate([
                'value' => 'required|in:low,medium,high,urgent',
            ])['value'];
            Ticket::whereIn('id', $ids)->update(['prioritas' => $priority]);
            $message = 'Prioritas untuk '.count($ids).' tiket berhasil diperbarui.';
        } elseif ($action === 'send_warning') {
            $setting = TicketHandlingSetting::firstOrCreate(
                ['id' => 1],
                ['max_hours' => 24, 'alert_mode' => 'automatic']
            );

            $tickets = Ticket::whereIn('id', $ids)
                ->whereNotIn('status_id', [4, 5])
                ->overdue($setting->max_hours)
                ->get();
            foreach ($tickets as $ticket) {
                $ticket->warnings()->create([
                    'sender_id' => $request->user()->id,
                    'message' => $validated['message'] ?? 'Tiket ini belum diselesaikan. Mohon segera ditindaklanjuti.',
                ]);
            }
            $message = 'Peringatan dikirim untuk '.$tickets->count().' tiket yang belum selesai.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->merge([
            'action' => 'change_status',
            'value' => $request->input('status_id', $request->input('value')),
        ]);

        return $this->bulkAction($request);
    }

    private function createStatusNotifications(Ticket $ticket, int $previousStatusId, ?User $actor): void
    {
        if (! $actor || ! $ticket->status_id) {
            return;
        }

        $ticket->loadMissing('category');
        $recipients = User::query()
            ->where(function ($query) use ($ticket) {
                $query->whereIn('role_id', [1, 2])
                    ->orWhere(function ($reporter) use ($ticket) {
                        $reporter->where('role_id', 4)
                            ->where('id', $ticket->user_id);
                    })
                    ->orWhere(function ($departmentUsers) use ($ticket) {
                        $departmentUsers->where('role_id', 3)
                            ->where('department_id', $ticket->category?->department_id);
                    });
            })
            ->where('id', '!=', $actor->id)
            ->pluck('id');

        $now = now();
        $notifications = $recipients->map(fn ($recipientId) => [
            'ticket_id' => $ticket->id,
            'recipient_id' => $recipientId,
            'actor_id' => $actor->id,
            'previous_status_id' => $previousStatusId ?: null,
            'status_id' => (int) $ticket->status_id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($notifications) {
            TicketStatusNotification::insert($notifications);
        }
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $roleId = $user?->role_id ?? $request->input('role_id');
        $userId = $user?->id ?? $request->input('user_id');
        $departmentId = $user?->department_id ?? $request->input('department_id');

        // Query Dasar
        $query = Ticket::query();

        // Filter berdasarkan Role / Akses Pengguna
        // Role 4: User/Pelapor biasa (Hanya melihat tiket milik sendiri)
        if ($roleId == 4) {
            $query->where('user_id', $userId);
        }
        // Role 3: Teknisi/Petugas Departemen (Melihat tiket sesuai departemennya)
        elseif ($roleId == 3) {
            $query->whereHas('category', fn ($category) => $category->where('department_id', $departmentId))
                ->where('status_id', '!=', 1);
        }

        // Hitung total dan statistik per status_id dalam 1 query database
        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as open,
            SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as resolve,
            SUM(CASE WHEN status_id = 4 THEN 1 ELSE 0 END) as complete,
            SUM(CASE WHEN status_id = 5 THEN 1 ELSE 0 END) as rejected
        ')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => (int) ($stats->total ?? 0),
                'open' => (int) ($stats->open ?? 0),
                'in_progress' => (int) ($stats->in_progress ?? 0),
                'resolve' => (int) ($stats->resolve ?? 0),
                'complete' => (int) ($stats->complete ?? 0),
                'rejected' => (int) ($stats->rejected ?? 0),
            ],
        ], 200);
    }
}
