<?php


namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class TicketAnalyticsController extends Controller
{
    // Mengambil daftar departemen untuk dropdown filter
    public function getDepartments(Request $request): JsonResponse
    {
        $departments = DB::table('departments')
            ->when($this->departmentId($request), fn ($query, $departmentId) =>
                $query->where('kode', $departmentId)
            )
            ->select('kode', 'nama')
            ->get();

        return response()->json(['status' => 'success', 'data' => $departments]);
    }

    // 1. Chart Status Tiket (Semua & Per Departemen)
    public function getStatusStats(Request $request): JsonResponse
    {
        $deptKode = $this->departmentId($request) ?? $request->query('department_id');

        $query = DB::table('statuses')
            ->leftJoin('tickets', 'statuses.id', '=', 'tickets.status_id');

        if ($deptKode && $deptKode !== 'all') {
            $query->where('tickets.department_id', $deptKode);
        }

        if ($this->isDepartmentRole($request)) {
            $query->where('tickets.status_id', '!=', 1);
        }

        $stats = $query->select('statuses.name as status_name', DB::raw('COUNT(tickets.id) as total'))
            ->groupBy('statuses.id', 'statuses.name')
            ->get();

        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    // 2. Jumlah Tiket Semua Departemen dalam Satu Chart
    public function getDepartmentTickets(Request $request): JsonResponse
    {
        $stats = DB::table('departments')
            ->leftJoin('tickets', 'departments.kode', '=', 'tickets.department_id')
            ->when($this->departmentId($request), fn ($query, $departmentId) =>
                $query->where('departments.kode', $departmentId)
            )
            ->when($this->isDepartmentRole($request), fn ($query) =>
                $query->where('tickets.status_id', '!=', 1)
            )
            ->select('departments.nama as department_name', DB::raw('COUNT(tickets.id) as total'))
            ->groupBy('departments.kode', 'departments.nama')
            ->get();

        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    // 3. Rata-Rata Waktu Penyelesaian dalam Jam (Semua & Per Departemen)
    public function getResolutionTime(Request $request): JsonResponse
    {
        $deptKode = $this->departmentId($request) ?? $request->query('department_id');

        $query = DB::table('tickets')
            ->join('departments', 'tickets.department_id', '=', 'departments.kode')
            ->whereNotNull('tickets.terselesaikan_pada');

        if ($deptKode && $deptKode !== 'all') {
            $query->where('tickets.department_id', $deptKode);
        }

        if ($this->isDepartmentRole($request)) {
            $query->where('tickets.status_id', '!=', 1);
        }

        $stats = $query->select(
                'departments.nama as department_name',
                DB::raw('ROUND(AVG(TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.terselesaikan_pada)), 1) as avg_hours')
            )
            ->groupBy('departments.kode', 'departments.nama')
            ->get();

        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    // app/Http/Controllers/Api/TicketAnalyticsController.php

public function getSummary(Request $request): JsonResponse
{
    $departmentId = $this->departmentId($request);
    $ticketQuery = fn () => DB::table('tickets')->when($departmentId, fn ($query) =>
        $query->where('department_id', $departmentId)
    )->when($this->isDepartmentRole($request), fn ($query) =>
        $query->where('status_id', '!=', 1)
    );
    $userQuery = DB::table('users')->when($departmentId, fn ($query) =>
        $query->where('department_id', $departmentId)
    );

    $totalUser = $userQuery->count();
    $totalDepartemen = DB::table('departments')->when($departmentId, fn ($query) =>
        $query->where('kode', $departmentId)
    )->count();
    $totalTiketMasuk = $ticketQuery()->count();
    
    $tiketSelesai = $ticketQuery()->whereNotNull('terselesaikan_pada')->count();

    // Rata-rata durasi penyelesaian semua tiket secara keseluruhan (dalam jam)
    $avgSla = $ticketQuery()
        ->whereNotNull('terselesaikan_pada')
        ->select(DB::raw('ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, terselesaikan_pada)), 1) as avg_hours'))
        ->value('avg_hours') ?? 0;

    return response()->json([
        'status' => 'success',
        'data' => [
            'total_user' => $totalUser,
            'total_departemen' => $totalDepartemen,
            'total_tiket_masuk' => $totalTiketMasuk,
            'tiket_selesai' => $tiketSelesai,
            'sla_avg_hours' => $avgSla,
        ]
    ]);
}

public function getPriorityAndRecent(Request $request): JsonResponse
{
    $departmentId = $this->departmentId($request);
    // Hitung tiket berdasarkan prioritas menggunakan Eloquent
    $priorityRaw = Ticket::query()
        ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
        ->when($this->isDepartmentRole($request), fn ($query) => $query->where('status_id', '!=', 1))
        ->selectRaw('prioritas, COUNT(*) as total')
        ->groupBy('prioritas')
        ->pluck('total', 'prioritas')
        ->toArray();

    $priority = [
        'low'    => $priorityRaw['low'] ?? $priorityRaw['LOW'] ?? 0,
        'medium' => $priorityRaw['medium'] ?? $priorityRaw['MEDIUM'] ?? 0,
        'high'   => $priorityRaw['high'] ?? $priorityRaw['HIGH'] ?? 0,
        'urgent' => $priorityRaw['urgent'] ?? $priorityRaw['URGENT'] ?? 0,
    ];

    // Ambil 5 tiket terbaru menggunakan Eager Loading (with)
    $recentTickets = Ticket::with(['department', 'status'])
        ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
        ->when($this->isDepartmentRole($request), fn ($query) => $query->where('status_id', '!=', 1))
        ->latest('created_at')
        ->limit(5)
        ->get()
        ->map(function ($ticket) {
            return [
                'no_tiket'   => $ticket->nomor_tiket,
                'judul'      => $ticket->judul,
                'departemen' => $ticket->department?->nama ?? '-',
                'status'     => $ticket->status?->name ?? '-',
            ];
        });

    return response()->json([
        'status' => 'success',
        'data'   => [
            'priority'       => $priority,
            'recent_tickets' => $recentTickets
        ]
    ]);
}

private function departmentId(Request $request): ?string
{
    $user = $request->user();

    return $user?->role_id == 3 ? $user->department_id : null;
}

private function isDepartmentRole(Request $request): bool
{
    return $request->user()?->role_id == 3;
}
}