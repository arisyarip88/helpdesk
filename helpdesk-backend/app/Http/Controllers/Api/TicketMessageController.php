<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $messages = $ticket->messages()
            ->with('user:id,name,username,role_id')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ]);
    }

    public function store(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated / Token tidak valid.',
            ], 401);
        }

        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $message->load('user:id,name,username'),
        ], 201);
    }

    public function destroy(Request $request, Ticket $ticket, TicketMessage $message)
    {
        $user = $request->user();
        $this->authorizeTicketMessageAction($ticket, $user);
        abort_unless((int) $message->ticket_id === (int) $ticket->id, 404);

        if (in_array((int) $user->role_id, [3, 4], true)) {
            $latestMessageId = $ticket->messages()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('id');
            abort_unless((int) $latestMessageId === (int) $message->id, 403, 'Hanya pesan terakhir yang dapat dihapus.');
        }

        $message->delete();

        return response()->json(['message' => 'Pesan berhasil dihapus']);
    }

    public function destroyAll(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        abort_unless($user && in_array((int) $user->role_id, [1, 2], true), 403);
        $this->authorizeTicketMessageAction($ticket, $user);

        $ticket->messages()->delete();

        return response()->json(['message' => 'Semua pesan berhasil dihapus']);
    }

    private function authorizeTicketMessageAction(Ticket $ticket, ?User $user): void
    {
        abort_unless($user, 401);

        $roleId = (int) $user->role_id;
        if ($roleId === 3) {
            abort_unless(
                $ticket->category()->where('department_id', $user->department_id)->exists(),
                403
            );
        } elseif ($roleId === 4) {
            abort_unless((int) $ticket->user_id === (int) $user->id, 403);
        } else {
            abort_unless(in_array($roleId, [1, 2], true), 403);
        }
    }
}
