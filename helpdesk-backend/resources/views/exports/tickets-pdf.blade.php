<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tiket</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #444; padding: 5px; text-align: left; }
        th { background: #e5e7eb; }
    </style>
</head>
<body>
    <h1>Daftar Tiket</h1>
    <table>
        <thead>
            <tr>
                <th>Nomor Tiket</th>
                <th>Judul</th>
                <th>Pelapor</th>
                <th>Departemen</th>
                <th>Status</th>
                <th>Prioritas</th>
                <th>Dibuat Pada</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->nomor_tiket }}</td>
                    <td>{{ $ticket->judul }}</td>
                    <td>{{ $ticket->user?->name }}</td>
                    <td>{{ $ticket->department?->nama }}</td>
                    <td>{{ $ticket->status?->name }}</td>
                    <td>{{ $ticket->prioritas }}</td>
                    <td>{{ $ticket->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada tiket.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>