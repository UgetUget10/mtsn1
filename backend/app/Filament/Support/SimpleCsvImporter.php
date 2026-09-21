<?php

namespace App\Filament\Support;

use League\Csv\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Pembaca CSV/XLSX generik untuk import massal resource sederhana (Guru &
 * Tendik, Prestasi) — dipisah dari PostImporter karena resource-resource ini
 * tidak butuh penanganan taksonomi/HTML/WXR yang membuat PostImporter rumit.
 * Sinkron (bukan queue) dengan alasan yang sama seperti PostImporter: tidak
 * ada queue worker berjalan di server ini.
 *
 * @see \App\Filament\Support\PostImporter Alasan pemrosesan sinkron.
 */
class SimpleCsvImporter
{
    /**
     * Baca berkas CSV/XLSX (baris pertama = header) menjadi array baris
     * ternormalisasi: kunci huruf kecil + trim, nilai trim.
     *
     * @return array<int, array<string, string>>
     */
    public static function read(string $path, string $format): array
    {
        return $format === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
    }

    /** @return array<int, array<string, string>> */
    private static function readCsv(string $path): array
    {
        $csv = CsvReader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $row = [];
            foreach ($record as $key => $value) {
                $row[strtolower(trim((string) $key))] = trim((string) $value);
            }
            if (array_filter($row, fn ($v) => $v !== '')) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return array<int, array<string, string>> */
    private static function readXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);

        $rows = [];
        $header = null;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = array_map(fn ($v) => trim((string) $v), $row->toArray());

                    if ($header === null) {
                        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $cells);

                        continue;
                    }

                    if (! array_filter($cells, fn ($v) => $v !== '')) {
                        continue;
                    }

                    $keyed = [];
                    foreach ($header as $i => $key) {
                        if ($key !== '') {
                            $keyed[$key] = $cells[$i] ?? '';
                        }
                    }
                    $rows[] = $keyed;
                }

                break; // hanya sheet pertama
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }
}
