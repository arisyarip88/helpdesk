<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TicketsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $tickets)
    {
    }

    public function collection(): Collection
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return ['Nomor Tiket', 'Judul', 'Pelapor', 'Departemen', 'Status', 'Prioritas', 'Dibuat Pada', 'Diselesaikan Pada'];
    }

    public function map($ticket): array
    {
        return [
            $ticket->nomor_tiket,
            $ticket->judul,
            $ticket->user?->name,
            $ticket->department?->nama,
            $ticket->status?->name,
            $ticket->prioritas,
            $ticket->created_at?->format('Y-m-d H:i:s'),
            $ticket->terselesaikan_pada?->format('Y-m-d H:i:s'),
        ];
    }
}