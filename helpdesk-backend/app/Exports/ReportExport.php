<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportExport implements FromCollection
{
    public function __construct(private readonly array $report)
    {
    }

    public function collection(): Collection
    {
        $summary = $this->report['summary'] ?? [];
        $rankings = $this->report['rankings'] ?? [];
        $categories = $this->report['categoryFrequency'] ?? [];
        $rows = collect([
            ['LAPORAN KINERJA TIKET'],
            [],
            ['RINGKASAN'],
            ['Metrik', 'Nilai'],
            ['Jumlah Tiket Masuk', $summary['total_tiket_masuk'] ?? 0],
            ['Jumlah Tiket Selesai', $summary['tiket_selesai'] ?? 0],
            ['Jumlah Belum Selesai', $summary['tiket_belum_selesai'] ?? 0],
            ['Rata-rata Rating Pelayanan Helpdesk', $summary['avg_rating'] ?? 0],
            ['Rata-rata Penyelesaian Tiket (jam)', $summary['sla_avg_hours'] ?? 0],
            [],
        ]);

        $sections = [
            ['PERINGKAT RATING UNIT', $rankings['departments'] ?? [], fn ($item) => [$item['department_name'] ?? '-', $item['average_rating'] ?? 0, $item['rating_count'] ?? 0]],
            ['PERINGKAT RATING ADMIN UNIT', $rankings['users'] ?? [], fn ($item) => [$item['user_name'] ?? '-', $item['department_name'] ?? '-', $item['average_rating'] ?? 0, $item['rating_count'] ?? 0]],
            ['PERINGKAT WAKTU PENYELESAIAN UNIT', $rankings['fastest_departments'] ?? [], fn ($item) => [$item['department_name'] ?? '-', $item['average_hours'] ?? 0, $item['completed_ticket_count'] ?? 0]],
            ['PERINGKAT WAKTU PENYELESAIAN ADMIN UNIT', $rankings['users'] ?? [], fn ($item) => [$item['user_name'] ?? '-', $item['department_name'] ?? '-', $item['average_hours'] ?? 0]],
            ['PERINGKAT WAKTU MENJAWAB PESAN UNIT', $rankings['fastest_departments'] ?? [], fn ($item) => [$item['department_name'] ?? '-', $item['average_hours'] ?? 0]],
            ['PERINGKAT WAKTU MENJAWAB PESAN ADMIN UNIT', $rankings['users'] ?? [], fn ($item) => [$item['user_name'] ?? '-', $item['department_name'] ?? '-', $item['average_hours'] ?? 0]],
            ['FREKUENSI KATEGORI', $categories, fn ($item) => [$item['name'] ?? '-', $item['total'] ?? 0]],
        ];

        foreach ($sections as [$title, $items, $mapper]) {
            $rows->push([$title]);
            foreach ($items as $item) {
                $rows->push($mapper($item));
            }
            $rows->push([]);
        }

        return $rows;
    }
}
