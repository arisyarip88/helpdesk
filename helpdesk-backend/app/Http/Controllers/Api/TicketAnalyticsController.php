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

        $query = $this->applyTicketPeriod($query, $request);

        if ($this->isDepartmentRole($request)) {
            $query->where('tickets.status_id', '!=', 1);
        }

        $stats = $query->select('statuses.id as status_id', 'statuses.name as status_name', DB::raw('COUNT(tickets.id) as total'))
            ->groupBy('statuses.id', 'statuses.name')
            ->get();

        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    // 2. Jumlah Tiket Semua Departemen dalam Satu Chart
    public function getDepartmentTickets(Request $request): JsonResponse
    {
        $query = DB::table('departments')
            ->leftJoin('tickets', 'departments.kode', '=', 'tickets.department_id')
            ->when($this->departmentId($request), fn ($query, $departmentId) =>
                $query->where('departments.kode', $departmentId)
            )
            ->when($this->isDepartmentRole($request), fn ($query) =>
                $query->where('tickets.status_id', '!=', 1)
            );
        $query = $this->applyTicketPeriod($query, $request);

        $stats = $query
            ->select('departments.kode as department_code', 'departments.nama as department_name', DB::raw('COUNT(tickets.id) as total'))
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
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.terselesaikan_pada');
        $query = $this->applyTicketPeriod($query, $request);

        if ($deptKode && $deptKode !== 'all') {
            $query->where('tickets.department_id', $deptKode);
        }

        if ($this->isDepartmentRole($request)) {
            $query->where('tickets.status_id', '!=', 1);
        }

        $stats = $query->select(
                'departments.kode as department_code',
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
    $ticketQuery = fn () => $this->applyTicketPeriod(DB::table('tickets'), $request)->when($departmentId, fn ($query) =>
        $query->where('department_id', $departmentId)
    )->when($this->isDepartmentRole($request), fn ($query) =>
        $query->where('status_id', '!=', 1)
    );
    $userQuery = DB::table('users')
        ->when($departmentId, fn ($query) => $query->where('users.department_id', $departmentId))
        ->when($request->filled('year'), fn ($query) => $query->whereYear('users.created_at', $request->integer('year')))
        ->when($request->filled('month'), fn ($query) => $query->whereMonth('users.created_at', $request->integer('month')));

    $totalUser = $userQuery->count();
    $totalDepartemen = $ticketQuery()->distinct()->count('department_id');
    $totalTiketMasuk = $ticketQuery()->count();
    
    $tiketSelesai = $ticketQuery()->where('status_id', 4)->count();

    // Rata-rata durasi penyelesaian semua tiket secara keseluruhan (dalam jam)
    $avgSla = $ticketQuery()
        ->where('status_id', 4)
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
        ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->integer('year')))
        ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->integer('month')))
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
        ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->integer('year')))
        ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->integer('month')))
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

public function getRatingRanking(Request $request): JsonResponse
{
    $validated = $request->validate([
        'month' => 'nullable|integer|between:1,12',
        'year' => 'nullable|integer|between:2000,2100',
    ]);
    $departmentId = $this->departmentId($request);
    $ratedClosedTickets = fn () => DB::table('tickets')
        ->where('tickets.status_id', 4)
        ->whereNotNull('tickets.rating')
        ->when($validated['year'] ?? null, fn ($query, $year) => $query->whereYear('tickets.created_at', $year))
        ->when($validated['month'] ?? null, fn ($query, $month) => $query->whereMonth('tickets.created_at', $month))
        ->when($departmentId, fn ($query) => $query->where('tickets.department_id', $departmentId));

    $departments = $ratedClosedTickets()
        ->join('departments', 'tickets.department_id', '=', 'departments.kode')
        ->select(
            'departments.kode as department_code',
            'departments.nama as department_name',
            DB::raw('ROUND(AVG(tickets.rating), 1) as average_rating'),
            DB::raw('COUNT(tickets.id) as rating_count')
        )
        ->groupBy('departments.kode', 'departments.nama')
        ->orderByDesc('average_rating')
        ->orderByDesc('rating_count')
        ->orderBy('department_name')
        ->limit(10)
        ->get();

    $fastestDepartments = DB::table('tickets')
        ->where('tickets.status_id', 4)
        ->whereNotNull('tickets.terselesaikan_pada')
        ->when($validated['year'] ?? null, fn ($query, $year) => $query->whereYear('tickets.created_at', $year))
        ->when($validated['month'] ?? null, fn ($query, $month) => $query->whereMonth('tickets.created_at', $month))
        ->when($departmentId, fn ($query) => $query->where('tickets.department_id', $departmentId))
        ->join('departments', 'tickets.department_id', '=', 'departments.kode')
        ->select(
            'departments.kode as department_code',
            'departments.nama as department_name',
            DB::raw('ROUND(AVG(TIMESTAMPDIFF(SECOND, tickets.created_at, tickets.terselesaikan_pada) / 3600), 1) as average_hours'),
            DB::raw('COUNT(tickets.id) as completed_ticket_count')
        )
        ->groupBy('departments.kode', 'departments.nama')
        ->orderBy('average_hours')
        ->orderByDesc('completed_ticket_count')
        ->orderBy('department_name')
        ->limit(10)
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => [
            'departments' => $departments,
            'fastest_departments' => $fastestDepartments,
        ],
    ]);
}

private function applyTicketPeriod($query, Request $request)
{
    $period = $request->validate([
        'month' => 'nullable|integer|between:1,12',
        'year' => 'nullable|integer|between:2000,2100',
    ]);

    return $query
        ->when($period['year'] ?? null, fn ($builder, $year) => $builder->whereYear('tickets.created_at', $year))
        ->when($period['month'] ?? null, fn ($builder, $month) => $builder->whereMonth('tickets.created_at', $month));
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