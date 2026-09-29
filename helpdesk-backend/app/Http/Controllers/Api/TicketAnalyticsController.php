<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketAnalyticsController extends Controller
{
    // Mengambil daftar departemen untuk dropdown filter
    public function getDepartments(Request $request): JsonResponse
    {
        $departments = DB::table('departments')
            ->when($this->departmentId($request), fn ($query, $departmentId) => $query->where('kode', $departmentId)
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
            ->leftJoin('tickets', 'statuses.id', '=', 'tickets.status_id')
            ->leftJoin('categories', 'tickets.category_id', '=', 'categories.id');

        if ($deptKode && $deptKode !== 'all') {
            $query->where('categories.department_id', $deptKode);
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
            ->leftJoin('categories', 'departments.kode', '=', 'categories.department_id')
            ->leftJoin('tickets', 'categories.id', '=', 'tickets.category_id')
            ->when($this->departmentId($request), fn ($query, $departmentId) => $query->where('departments.kode', $departmentId)
            )
            ->when($this->isDepartmentRole($request), fn ($query) => $query->where('tickets.status_id', '!=', 1)
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
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->join('departments', 'categories.department_id', '=', 'departments.kode')
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.terselesaikan_pada');
        $query = $this->applyTicketPeriod($query, $request);

        if ($deptKode && $deptKode !== 'all') {
            $query->where('categories.department_id', $deptKode);
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
        $ticketQuery = fn () => $this->applyTicketPeriod(
            DB::table('tickets')->join('categories', 'tickets.category_id', '=', 'categories.id'),
            $request
        )->when($departmentId, fn ($query) => $query->where('categories.department_id', $departmentId)
        )->when($this->isDepartmentRole($request), fn ($query) => $query->where('tickets.status_id', '!=', 1)
        );
        $userQuery = DB::table('users')
            ->when($departmentId, fn ($query) => $query->where('users.department_id', $departmentId))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('users.created_at', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('users.created_at', $request->integer('month')));

        $totalUser = $userQuery->count();
        $totalDepartemen = $ticketQuery()->distinct()->count('categories.department_id');
        $totalTiketMasuk = $ticketQuery()->count();

        $tiketSelesai = $ticketQuery()->where('tickets.status_id', 4)->count();
        $tiketBelumSelesai = $ticketQuery()->whereNotIn('tickets.status_id', [4, 5])->count();
        $avgRating = $ticketQuery()
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.rating')
            ->avg('tickets.rating') ?? 0;

        // Rata-rata durasi penyelesaian semua tiket secara keseluruhan (dalam jam)
        $avgSla = $ticketQuery()
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.terselesaikan_pada')
            ->select(DB::raw('ROUND(AVG(TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.terselesaikan_pada)), 1) as avg_hours'))
            ->value('avg_hours') ?? 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_user' => $totalUser,
                'total_departemen' => $totalDepartemen,
                'total_tiket_masuk' => $totalTiketMasuk,
                'tiket_selesai' => $tiketSelesai,
                'tiket_belum_selesai' => $tiketBelumSelesai,
                'avg_rating' => round((float) $avgRating, 1),
                'sla_avg_hours' => $avgSla,
            ],
        ]);
    }

    public function getPriorityAndRecent(Request $request): JsonResponse
    {
        $departmentId = $this->departmentId($request);
        // Hitung tiket berdasarkan prioritas menggunakan Eloquent
        $priorityRaw = Ticket::query()
            ->when($departmentId, fn ($query) => $query->whereHas('category', fn ($category) => $category->where('department_id', $departmentId)))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->integer('month')))
            ->when($this->isDepartmentRole($request), fn ($query) => $query->where('status_id', '!=', 1))
            ->selectRaw('prioritas, COUNT(*) as total')
            ->groupBy('prioritas')
            ->pluck('total', 'prioritas')
            ->toArray();

        $priority = [
            'low' => $priorityRaw['low'] ?? $priorityRaw['LOW'] ?? 0,
            'medium' => $priorityRaw['medium'] ?? $priorityRaw['MEDIUM'] ?? 0,
            'high' => $priorityRaw['high'] ?? $priorityRaw['HIGH'] ?? 0,
            'urgent' => $priorityRaw['urgent'] ?? $priorityRaw['URGENT'] ?? 0,
        ];

        // Ambil 5 tiket terbaru menggunakan Eager Loading (with)
        $recentTickets = Ticket::with(['category.department', 'status'])
            ->when($departmentId, fn ($query) => $query->whereHas('category', fn ($category) => $category->where('department_id', $departmentId)))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->integer('year')))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->integer('month')))
            ->when($this->isDepartmentRole($request), fn ($query) => $query->where('status_id', '!=', 1))
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'no_tiket' => $ticket->nomor_tiket,
                    'judul' => $ticket->judul,
                    'departemen' => $ticket->department?->nama ?? '-',
                    'status' => $ticket->status?->name ?? '-',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'priority' => $priority,
                'recent_tickets' => $recentTickets,
            ],
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
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.rating')
            ->when($validated['year'] ?? null, fn ($query, $year) => $query->whereYear('tickets.created_at', $year))
            ->when($validated['month'] ?? null, fn ($query, $month) => $query->whereMonth('tickets.created_at', $month))
            ->when($departmentId, fn ($query) => $query->where('categories.department_id', $departmentId));

        $departments = $ratedClosedTickets()
            ->join('departments', 'categories.department_id', '=', 'departments.kode')
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

        $adminDurationExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => 'ROUND(AVG((julianday(tickets.terselesaikan_pada) - julianday(tickets.created_at)) * 24), 1) as average_hours',
            'pgsql' => 'ROUND((AVG(EXTRACT(EPOCH FROM (tickets.terselesaikan_pada - tickets.created_at)) / 3600))::numeric, 1) as average_hours',
            default => 'ROUND(AVG(TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.terselesaikan_pada)), 1) as average_hours',
        };
        $users = DB::table('tickets')
            ->join('users as responders', 'tickets.resolved_by', '=', 'responders.id')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->leftJoin('departments', 'responders.department_id', '=', 'departments.kode')
            ->where('responders.role_id', 3)
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.rating')
            ->when($departmentId, fn ($query) => $query->where('categories.department_id', $departmentId))
            ->when($validated['year'] ?? null, fn ($query, $year) => $query->whereYear('tickets.created_at', $year))
            ->when($validated['month'] ?? null, fn ($query, $month) => $query->whereMonth('tickets.created_at', $month))
            ->select(
                'responders.id as user_id',
                'responders.name as user_name',
                'departments.nama as department_name',
                DB::raw('ROUND(AVG(tickets.rating), 1) as average_rating'),
                DB::raw('COUNT(tickets.id) as rating_count'),
                DB::raw($adminDurationExpression)
            )
            ->groupBy('responders.id', 'responders.name', 'departments.nama')
            ->orderByDesc('average_rating')
            ->orderByDesc('rating_count')
            ->orderBy('user_name')
            ->limit(10)
            ->get();

        $resolutionDurationExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => 'ROUND(AVG((julianday(tickets.terselesaikan_pada) - julianday(tickets.created_at)) * 24), 1) as average_hours',
            'pgsql' => 'ROUND((AVG(EXTRACT(EPOCH FROM (tickets.terselesaikan_pada - tickets.created_at)) / 3600))::numeric, 1) as average_hours',
            default => 'ROUND(AVG(TIMESTAMPDIFF(SECOND, tickets.created_at, tickets.terselesaikan_pada) / 3600), 1) as average_hours',
        };

        $fastestDepartments = DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->where('tickets.status_id', 4)
            ->whereNotNull('tickets.terselesaikan_pada')
            ->when($validated['year'] ?? null, fn ($query, $year) => $query->whereYear('tickets.created_at', $year))
            ->when($validated['month'] ?? null, fn ($query, $month) => $query->whereMonth('tickets.created_at', $month))
            ->when($departmentId, fn ($query) => $query->where('categories.department_id', $departmentId))
            ->join('departments', 'categories.department_id', '=', 'departments.kode')
            ->select(
                'departments.kode as department_code',
                'departments.nama as department_name',
                DB::raw($resolutionDurationExpression),
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
                'users' => $users,
                'fastest_departments' => $fastestDepartments,
            ],
        ]);
    }

    public function getCategoryFrequency(Request $request): JsonResponse
    {
        $departmentId = $this->departmentId($request);
        $period = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
        ]);

        $frequency = DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->when($departmentId, fn ($query) => $query->where('categories.department_id', $departmentId))
            ->when($period['year'] ?? null, fn ($query, $year) => $query->whereYear('tickets.created_at', $year))
            ->when($period['month'] ?? null, fn ($query, $month) => $query->whereMonth('tickets.created_at', $month))
            ->when($this->isDepartmentRole($request), fn ($query) => $query->where('tickets.status_id', '!=', 1))
            ->select('categories.id', 'categories.name', DB::raw('COUNT(tickets.id) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return response()->json(['status' => 'success', 'data' => $frequency]);
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
