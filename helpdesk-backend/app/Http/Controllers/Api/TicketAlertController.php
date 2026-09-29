<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketHandlingSetting;
use App\Models\TicketWarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketAlertController extends Controller
{
    public function settings(): JsonResponse
    {
        $setting = TicketHandlingSetting::firstOrCreate(
            ['id' => 1],
            ['max_hours' => 24, 'alert_mode' => 'automatic']
        );

        return response()->json(['data' => $setting]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'max_hours' => 'required|integer|min:1|max:720',
            'alert_mode' => 'required|in:automatic,manual',
        ]);

        $setting = TicketHandlingSetting::updateOrCreate(['id' => 1], $validated);

        return response()->json([
            'message' => 'Batas waktu penanganan berhasil diperbarui.',
            'data' => $setting,
        ]);
    }

    public function sendWarning(Request $request, Ticket $ticket): JsonResponse
    {
        $setting = TicketHandlingSetting::firstOrCreate(
            ['id' => 1],
            ['max_hours' => 24, 'alert_mode' => 'automatic']
        );

        abort_unless($setting->alert_mode === 'manual', 422, 'Peringatan manual hanya tersedia pada mode manual.');
        abort_unless(! in_array((int) $ticket->status_id, [4, 5], true), 422, 'Peringatan hanya dapat dikirim untuk tiket yang belum selesai.');
        abort_unless(
            Ticket::query()->whereKey($ticket->id)->overdue($setting->max_hours)->exists(),
            422,
            'Peringatan hanya dapat dikirim setelah tiket melewati batas waktu penanganan.'
        );

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $warning = $ticket->warnings()->create([
            'sender_id' => $request->user()->id,
            'message' => $validated['message'] ?? 'Tiket ini belum diselesaikan. Mohon segera ditindaklanjuti.',
        ]);

        return response()->json([
            'message' => 'Peringatan berhasil dikirim.',
            'data' => $warning,
        ], 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;
        $setting = TicketHandlingSetting::firstOrCreate(
            ['id' => 1],
            ['max_hours' => 24, 'alert_mode' => 'automatic']
        );

        $warnings = TicketWarning::query()
            ->with('ticket.status')
            ->whereNull('read_at')
            ->whereHas('ticket', function ($query) use ($departmentId) {
                $query->whereHas('category', fn ($category) => $category->where('department_id', $departmentId))
                    ->whereNotIn('status_id', [4, 5]);
            })
            ->whereHas('ticket', fn ($query) => $query->overdue($setting->max_hours))
            ->latest()
            ->get()
            ->map(fn (TicketWarning $warning): array => [
                'id' => 'warning-'.$warning->id,
                'warning_id' => $warning->id,
                'ticket_id' => $warning->ticket->id,
                'ticket_number' => $warning->ticket->nomor_tiket,
                'title' => $warning->ticket->judul,
                'message' => $warning->message,
                'status_id' => (int) $warning->ticket->status_id,
                'is_overdue' => false,
                'created_at' => $warning->created_at,
            ]);

        $overdueTickets = collect();
        if ($setting->alert_mode === 'automatic') {
            $overdueTickets = Ticket::query()
                ->whereHas('category', fn ($category) => $category->where('department_id', $departmentId))
                ->whereNotIn('status_id', [4, 5])
                ->overdue($setting->max_hours)
                ->latest()
                ->get()
                ->map(fn (Ticket $ticket): array => [
                    'id' => 'overdue-'.$ticket->id,
                    'warning_id' => null,
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->nomor_tiket,
                    'title' => $ticket->judul,
                    'message' => $ticket->terselesaikan_pada
                        ? 'Tiket diselesaikan melewati batas penanganan maksimal '.$setting->max_hours.' jam.'
                        : 'Tiket belum selesai dan telah melewati batas penanganan maksimal '.$setting->max_hours.' jam.',
                    'status_id' => (int) $ticket->status_id,
                    'is_overdue' => true,
                    'created_at' => $ticket->created_at,
                ]);
        }

        return response()->json([
            'data' => $warnings->concat($overdueTickets)->sortByDesc('created_at')->values(),
            'max_hours' => $setting->max_hours,
            'alert_mode' => $setting->alert_mode,
        ]);
    }

    public function markRead(Request $request, TicketWarning $warning): JsonResponse
    {
        abort_unless(
            $warning->ticket()
                ->whereHas('category', fn ($category) => $category->where('department_id', $request->user()->department_id))
                ->exists(),
            404
        );

        $warning->update(['read_at' => now()]);

        return response()->json(['message' => 'Peringatan ditandai sudah dibaca.']);
    }
}
