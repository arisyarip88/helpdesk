<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = $request->user()?->role_id == 3
            ? $request->user()->department_id
            : null;
        $ticketQuery = fn () => Ticket::when($departmentId, fn ($query) =>
            $query->where('department_id', $departmentId)
        );

        // Total User & Departemen
        $totalUsers = User::when($departmentId, fn ($query) =>
            $query->where('department_id', $departmentId)
        )->count();
        $totalDepartments = Department::when($departmentId, fn ($query) =>
            $query->where('kode', $departmentId)
        )->count();

        // Statistik Tiket Berdasarkan Kriteria Status ID (Integer)
        $ticketStats = [
            'total'       => $ticketQuery()->count(),
            'open'        => $ticketQuery()->where('status_id', 1)->count(),
            'in_progress' => $ticketQuery()->where('status_id', 2)->count(),
            'resolved'    => $ticketQuery()->where('status_id', 3)->count(),
            'closed'      => $ticketQuery()->where('status_id', 4)->count(),
            'rejected'    => $ticketQuery()->where('status_id', 5)->count(),
        ];

        // Statistik Tiket Berdasarkan Kriteria Prioritas
        $priorityStats = [
            'low'    => $ticketQuery()->where('prioritas', 'low')->count(),
            'medium' => $ticketQuery()->where('prioritas', 'medium')->count(),
            'high'   => $ticketQuery()->where('prioritas', 'high')->count(),
            'urgent' => $ticketQuery()->where('prioritas', 'urgent')->count(),
        ];

        // Tiket Terbaru (5 Tiket Terakhir) beserta relasi status
        $latestTickets = $ticketQuery()->with(['user', 'department', 'status'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'total_users'       => $totalUsers,
            'total_departments' => $totalDepartments,
            'tickets'           => $ticketStats,
            'priorities'        => $priorityStats,
            'latest_tickets'    => $latestTickets,
        ]);
    }


  
}