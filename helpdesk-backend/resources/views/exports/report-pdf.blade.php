<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kinerja Tiket</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #0f766e; }
        h2 { font-size: 11px; margin: 16px 0 5px; background: #e5e7eb; padding: 6px; }
        .meta { color: #64748b; margin-bottom: 12px; }
        .cards { width: 100%; margin-bottom: 8px; }
        .card { display: inline-block; width: 18%; min-height: 42px; margin-right: 1%; padding: 6px; background: #cffafe; vertical-align: top; }
        .label { display: block; font-size: 8px; color: #475569; }
        .value { display: block; margin-top: 5px; font-size: 13px; font-weight: bold; color: #0f766e; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; }
        th { background: #f1f5f9; }
        .muted { color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Laporan Kinerja Tiket</h1>
    <div class="meta">Ringkasan laporan sesuai filter yang dipilih pada halaman laporan.</div>

    <div class="cards">
        <div class="card"><span class="label">Jumlah Tiket Masuk</span><span class="value">{{ $summary['total_tiket_masuk'] ?? 0 }}</span></div>
        <div class="card"><span class="label">Jumlah Tiket Selesai</span><span class="value">{{ $summary['tiket_selesai'] ?? 0 }}</span></div>
        <div class="card"><span class="label">Jumlah Belum Selesai</span><span class="value">{{ $summary['tiket_belum_selesai'] ?? 0 }}</span></div>
        <div class="card"><span class="label">Rata-rata Rating</span><span class="value">{{ $summary['avg_rating'] ?? 0 }} / 5</span></div>
        <div class="card"><span class="label">Rata-rata Penyelesaian</span><span class="value">{{ $summary['sla_avg_hours'] ?? 0 }} jam</span></div>
    </div>

    @php
        $sections = [
            ['Peringkat Rating Unit', $rankings['departments'] ?? [], ['department_name', 'average_rating', 'rating_count']],
            ['Peringkat Rating Admin Unit', $rankings['users'] ?? [], ['user_name', 'department_name', 'average_rating', 'rating_count']],
            ['Peringkat Waktu Penyelesaian Unit', $rankings['fastest_departments'] ?? [], ['department_name', 'average_hours', 'completed_ticket_count']],
            ['Peringkat Waktu Penyelesaian Admin Unit', $rankings['users'] ?? [], ['user_name', 'department_name', 'average_hours']],
            ['Peringkat Waktu Menjawab Pesan Unit', $rankings['fastest_departments'] ?? [], ['department_name', 'average_hours']],
            ['Peringkat Waktu Menjawab Pesan Admin Unit', $rankings['users'] ?? [], ['user_name', 'department_name', 'average_hours']],
            ['Frekuensi Kategori', $categoryFrequency ?? [], ['name', 'total']],
        ];
    @endphp

    @foreach ($sections as [$title, $items, $columns])
        <h2>{{ $title }}</h2>
        <table>
            <thead><tr>@foreach ($columns as $column)<th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>@foreach ($columns as $column)<td>{{ $item[$column] ?? '-' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="muted">Belum ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
