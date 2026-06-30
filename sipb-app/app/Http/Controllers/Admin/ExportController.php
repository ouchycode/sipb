<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\FoundItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\FoundItemLifecycleService;

class ExportController extends Controller
{
    protected FoundItemLifecycleService $lifecycleService;

    public function __construct(FoundItemLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    private function sanitizeCsvField(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function maskNim(?string $nim): string
    {
        if ($nim === null || $nim === '') {
            return '';
        }

        $visible = substr($nim, -3);
        $masked = str_repeat('*', max(0, strlen($nim) - 3));

        return $masked . $visible;
    }

    public function export(): StreamedResponse
    {
        $this->lifecycleService->syncExpiredItems();

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Jenis laporan',
                'Nama barang',
                'Kategori',
                'Lokasi',
                'Tanggal laporan',
                'Status',
                'Pelapor',
                'NIM pelapor',
                'Pengambil',
                'NIM pengambil',
                'Dipublikasi',
                'Diambil',
                'Kadaluarsa',
                'Catatan admin',
                'Catatan validasi',
            ]);

            FoundItem::query()
                ->latest()
                ->chunk(200, function ($items) use ($handle): void {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->id,
                            'Temuan',
                            $this->sanitizeCsvField($item->name),
                            $item->category,
                            $item->location,
                            $item->found_at?->format('Y-m-d H:i:s'),
                            $item->status,
                            $this->sanitizeCsvField($item->finder_name),
                            $this->maskNim($item->finder_nim),
                            $this->sanitizeCsvField($item->claimant_name),
                            $this->maskNim($item->claimant_nim),
                            $item->published_at?->format('Y-m-d H:i:s'),
                            $item->claimed_at?->format('Y-m-d H:i:s'),
                            $item->expired_at?->format('Y-m-d H:i:s'),
                            $this->sanitizeCsvField($item->admin_notes),
                            $this->sanitizeCsvField($item->validation_notes),
                        ]);
                    }
                });

            fclose($handle);
        }, 'laporan-sipb.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->lifecycleService->syncExpiredItems();

        return response()->streamDownload(function (): void {
            echo '<table border="1">';
            echo '<tr><th>ID</th><th>Nama barang</th><th>Kategori</th><th>Lokasi</th><th>Tanggal laporan</th><th>Status</th><th>Pelapor</th><th>NIM pelapor</th><th>Pengambil</th><th>NIM pengambil</th><th>Dipublikasi</th><th>Diambil</th><th>Catatan admin</th><th>Catatan validasi</th></tr>';

            FoundItem::query()
                ->latest()
                ->chunk(200, function ($items): void {
                    foreach ($items as $item) {
                        $cells = [
                            $item->id,
                            $item->name,
                            $item->category,
                            $item->location,
                            $item->found_at?->format('Y-m-d H:i:s'),
                            $item->status,
                            $item->finder_name,
                            $this->maskNim($item->finder_nim),
                            $item->claimant_name,
                            $this->maskNim($item->claimant_nim),
                            $item->published_at?->format('Y-m-d H:i:s'),
                            $item->claimed_at?->format('Y-m-d H:i:s'),
                            $item->admin_notes,
                            $item->validation_notes,
                        ];

                        echo '<tr>';

                        foreach ($cells as $cell) {
                            echo '<td>'.e((string) $cell).'</td>';
                        }

                        echo '</tr>';
                    }
                });

            echo '</table>';
        }, 'laporan-sipb.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}
