<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class BaseExporter
{
    abstract public function headers(): array;

    /** @return array<int, array<int, mixed>> */
    abstract public function rows(array $filters): array;

    abstract public function filename(): string;

    public function download(array $filters): StreamedResponse
    {
        $soloFormato = !empty($filters['solo_encabezados']);
        $name = ($soloFormato ? 'formato_' : '') . $this->filename() . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($filters, $soloFormato) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF"); // UTF-8 BOM para Excel
            fputcsv($h, $this->headers());
            if (!$soloFormato) {
                foreach ($this->rows($filters) as $row) {
                    fputcsv($h, array_map(fn($v) => $v ?? '', $row));
                }
            }
            fclose($h);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
