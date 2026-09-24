<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $messages = $ticket->messages()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $messages
        ]);
    }

    public function store(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated / Token tidak valid.'
            ], 401);
        }

        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $message->load('user:id,name,username')
        ], 201);
    }
    public function destroy(Ticket $ticket, TicketMessage $message)
    {
        $message->delete();
        return response()->json(['message' => 'Pesan berhasil dihapus']);
    }

// Hapus Semua Pesan
    public function destroyAll(Ticket $ticket)
    {
        $ticket->messages()->delete();
        return response()->json(['message' => 'Semua pesan berhasil dihapus']);
    }
}