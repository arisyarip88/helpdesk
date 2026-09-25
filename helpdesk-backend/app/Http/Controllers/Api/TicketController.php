<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Status;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roleId = $user ? $user->role_id : $request->input('role_id');
        if($roleId==4){
            $pg=4;
        }else $pg=10;
        $departmentId = $request->input('department_id');
        $search = $request->input('search');
        $statusId = $request->input('status_id');
        $prioritas = $request->input('prioritas');
        $perPage = $request->input('per_page',$pg );

        $query = Ticket::with(['user', 'department', 'status', 'messages']);

        // Filter berdasarkan Role
        if ($roleId == 4) {
            // Role 4: Hanya tampilkan tiket milik user sendiri[cite: 5]
            $query->where('user_id', $user ? $user->id : $request->input('user_id'));
        } elseif ($roleId == 3) {
            // Role 3 selalu dibatasi ke departemen user, bukan nilai dari request.
            $query->where('department_id', $user?->department_id)
                ->where('status_id', '!=', 1);
        }
        //  elseif ($request->filled('department_id')) {
        //     $query->where('department_id', $departmentId);
        // }

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

        $tickets = $query->latest()->paginate($perPage);

        // Rekap Count per Status[cite: 5]
        $statusCountsQuery = Ticket::query();
        if ($roleId == 4) {
            $statusCountsQuery->where('user_id', $user ? $user->id : $request->input('user_id'));
        } elseif ($roleId == 3) {
            $statusCountsQuery->where('department_id', $user?->department_id)
                ->where('status_id', '!=', 1);
        } elseif ($request->filled('department_id')) {
            $statusCountsQuery->where('department_id', $departmentId);
        }

        $statusCounts = $statusCountsQuery->selectRaw('status_id, count(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        $statuses = Status::query()
            ->when($roleId == 3, fn ($query) => $query->where('id', '!=', 1))
            ->get()
            ->map(function ($status) use ($statusCounts) {
            $status->count = $statusCounts->get($status->id, 0);
            return $status;
        });

        return response()->json([
            'data'        => $tickets,
            'statuses'    => $statuses,
            'departments' => Department::all()
        ]);
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

        $query = Ticket::with(['user', 'department', 'status'])->latest();

        if ($roleId == 4) {
            $query->where('user_id', $user?->id ?? $request->input('user_id'));
        } elseif ($roleId == 3) {
            $query->where('department_id', $user?->department_id)
                ->where('status_id', '!=', 1);
        } elseif ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
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
        if (!$request->hasFile('lampiran')) {
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
            'user_id'       => 'nullable|exists:users,id',
            'department_id' => 'required|string|max:20',
            'judul'         => 'required|string|max:255',
            'deskripsi'     => 'required|string',
            'prioritas'     => 'nullable|in:low,medium,high,urgent',
            'lampiran'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $lampiranPath = $this->handleLampiranUpload($request, null, $validated);

        $ticket = Ticket::create([
            'nomor_tiket'   => 'TK-' . strtoupper(uniqid()),
            'user_id'       => $userId ?? $validated['user_id'],
            'department_id' => $validated['department_id'],
            'judul'         => $validated['judul'],
            'deskripsi'     => $validated['deskripsi'],
            'prioritas'     => $validated['prioritas'] ?? 'medium',
            'status_id'     => 1, // Default status: New[cite: 5]
            'lampiran'      => $lampiranPath,
        ]);

        return response()->json([
            'message' => 'Tiket berhasil dibuat',
            'data'    => $ticket->load(['user', 'department', 'status'])
        ], 201);
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user', 'department', 'status', 'messages.user'])->findOrFail($id);
        return response()->json(['data' => $ticket]);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'status_id' => 'sometimes|exists:statuses,id',
            'judul'     => 'sometimes|string|max:255',
            'deskripsi' => 'sometimes|string',
            'comment'   => 'nullable|string',
            'prioritas' => 'sometimes|in:low,medium,high,urgent',
            'lampiran'  => 'sometimes|nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('lampiran')) {
            $this->handleLampiranUpload($request, $ticket, $validated);
        }

        if (isset($validated['status_id']) && in_array($validated['status_id'], [4, 5])) {
            $validated['terselesaikan_pada'] = now();
        }

        $ticket->update($validated);

        return response()->json([
            'message' => 'Tiket berhasil diperbarui',
            'data'    => $ticket->load(['user', 'department', 'status'])
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
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required',
            'action' => 'required|in:delete,change_status,change_priority',
            'value' => 'nullable'
        ]);

        $ids = $request->ids;
        $action = $request->action;

        if ($action === 'delete') {
            Ticket::whereIn('id', $ids)->delete();
            $message = count($ids) . ' tiket berhasil dihapus.';
        } elseif ($action === 'change_status') {
            Ticket::whereIn('id', $ids)->update(['status_id' => $request->value]);
            $message = 'Status untuk ' . count($ids) . ' tiket berhasil diperbarui.';
        } elseif ($action === 'change_priority') {
            Ticket::whereIn('id', $ids)->update(['prioritas' => $request->value]);
            $message = 'Prioritas untuk ' . count($ids) . ' tiket berhasil diperbarui.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message
        ]);
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
            $query->where('department_id', $departmentId)
                ->where('status_id', '!=', 1);
        }

        // Hitung total dan statistik per status_id dalam 1 query database
        $stats = $query->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as open,
            SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as resolve,
            SUM(CASE WHEN status_id = 4 THEN 1 ELSE 0 END) as complete,
            SUM(CASE WHEN status_id = 5 THEN 1 ELSE 0 END) as rejected
        ")->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total'       => (int) ($stats->total ?? 0),
                'open'        => (int) ($stats->open ?? 0),
                'in_progress' => (int) ($stats->in_progress ?? 0),
                'resolve'     => (int) ($stats->resolve ?? 0),
                'complete'    => (int) ($stats->complete ?? 0),
                 'rejected'    => (int) ($stats->rejected ?? 0),
            ]
        ], 200);
    }
}