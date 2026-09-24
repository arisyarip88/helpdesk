<?php

namespace App\Http\Controllers\Api;

use App\Exports\TicketsExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function departments()
    {
        $user = request()->user();
        $query = Department::query()->orderBy('nama');

        if ($user?->role_id == 3) {
            $query->where('kode', $user->department_id);
        }

        return response()->json($query->get(['kode', 'nama']));
    }

    public function tickets(Request $request)
    {
        $this->validatePeriod($request);

        return response()->json(
            $this->reportQuery($request)->paginate($request->integer('per_page', 10))
        );
    }

    public function export(Request $request, string $type)
    {
        $this->validatePeriod($request);
        $tickets = $this->reportQuery($request)->get();

        if ($type === 'excel') {
            return Excel::download(new TicketsExport($tickets), 'laporan-tiket.xlsx');
        }

        if ($type === 'pdf') {
            return Pdf::loadView('exports.tickets-pdf', compact('tickets'))
                ->download('laporan-tiket.pdf');
        }

        abort(404, 'Format export tidak tersedia.');
    }

    private function reportQuery(Request $request)
    {
        $query = Ticket::with(['user', 'department', 'status']);

        if ($request->user()?->role_id == 3) {
            $query->where('department_id', $request->user()->department_id);
        }

        return $query
            ->when($request->filled('department_id'), fn ($query) =>
                $query->where('department_id', $request->input('department_id'))
            )
            ->when($request->filled('status_id'), fn ($query) =>
                $query->where('status_id', $request->input('status_id'))
            )
            ->when($request->filled('status'), fn ($query) =>
                $query->whereHas('status', function ($status) use ($request) {
                    $value = $request->input('status');

                    if (is_numeric($value)) {
                        $status->where('id', $value);
                    } else {
                        $status->whereRaw("LOWER(REPLACE(name, ' ', '_')) = ?", [strtolower($value)]);
                    }
                })
            )
            ->when($request->filled('prioritas'), fn ($query) =>
                $query->where('prioritas', $request->input('prioritas'))
            )
            ->when($request->filled('month'), fn ($query) =>
                $query->whereBetween('created_at', [
                    Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth(),
                    Carbon::createFromFormat('Y-m', $request->input('month'))->endOfMonth(),
                ])
            )
            ->when($request->filled('start_date'), fn ($query) =>
                $query->whereDate('created_at', '>=', $request->input('start_date'))
            )
            ->when($request->filled('end_date'), fn ($query) =>
                $query->whereDate('created_at', '<=', $request->input('end_date'))
            )
            ->latest('created_at');
    }

    private function validatePeriod(Request $request): void
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'department_id' => ['nullable'],
            'status' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer'],
            'prioritas' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($request->filled('month') && ($request->filled('start_date') || $request->filled('end_date'))) {
            throw ValidationException::withMessages([
                'month' => 'Gunakan bulan atau rentang tanggal, bukan keduanya sekaligus.',
            ]);
        }
    }
}
