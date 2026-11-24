<?php

namespace App\Console\Commands;

use App\Services\PrivateStorageService;
use Illuminate\Console\Command;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Throwable;

class GenerateDummyStudentsCommand extends Command
{
    protected $signature = 'students:generate-dummy';

    protected $description = 'Membuat file Excel dan CSV data siswa sintetis (fiktif) untuk pengujian impor dan performa';

    private array $firstNames = [
        'Ahmad', 'Siti', 'Budi', 'Dewi', 'Rizki', 'Putri', 'Dimas', 'Nurul',
        'Fajar', 'Ayu', 'Rendra', 'Intan', 'Galih', 'Nadia', 'Ilham', 'Fitri',
        'Bagas', 'Wulan', 'Arif', 'Citra', 'Hendra', 'Lestari', 'Yoga', 'Ratna',
    ];

    private array $middleNames = [
        'Saputra', 'Rahmawati', 'Santoso', 'Kusuma', 'Pratama', 'Anggraini',
        'Wijaya', 'Permata', 'Nugroho', 'Sari', 'Hidayat', 'Puspita',
        'Setiawan', 'Mulyani', 'Ramadhan', 'Handayani', 'Kurniawan', 'Utami',
    ];

    private array $classes = [
        'X TJKT 1', 'X TJKT 2', 'X DKV 1', 'X MPLB 1',
        'XI TJKT 1', 'XI TJKT 2', 'XI DKV 1', 'XI MPLB 1',
        'XII TJKT 1', 'XII TJKT 2', 'XII DKV 1', 'XII MPLB 1',
    ];

    public function handle(PrivateStorageService $storageService): int
    {
        try {
            $storageService->ensureDirectoriesExist();
            $tempDir = $storageService->fullPath(PrivateStorageService::DIR_TEMP);

            $filesCreated = [];

            // 1. File XLSX 10 siswa valid (termasuk nol di depan & nama panjang bertanda baca)
            $path10 = $tempDir . DIRECTORY_SEPARATOR . 'dummy_10_siswa.xlsx';
            $this->writeXlsx($path10, $this->buildValidRows(10));
            $filesCreated[] = 'storage/app/private/temp/dummy_10_siswa.xlsx (10 siswa valid)';

            // 2. File XLSX kasus uji validasi (baris kosong, nama kosong, kelas kosong, duplikat)
            $pathValidation = $tempDir . DIRECTORY_SEPARATOR . 'dummy_kasus_validasi.xlsx';
            $this->writeXlsx($pathValidation, $this->buildValidationCaseRows());
            $filesCreated[] = 'storage/app/private/temp/dummy_kasus_validasi.xlsx (uji baris kosong & duplikat)';

            // 3. File CSV 1.000 siswa dengan pemisah titik koma (;)
            $path1000Csv = $tempDir . DIRECTORY_SEPARATOR . 'dummy_1000_siswa.csv';
            $this->writeCsv($path1000Csv, $this->buildValidRows(1000), ';');
            $filesCreated[] = 'storage/app/private/temp/dummy_1000_siswa.csv (1.000 siswa, delimiter titik koma)';

            // 4. File XLSX 5.000 siswa untuk pengujian performa target MVP
            $path5000 = $tempDir . DIRECTORY_SEPARATOR . 'dummy_5000_siswa.xlsx';
            $this->writeXlsx($path5000, $this->buildValidRows(5000));
            $filesCreated[] = 'storage/app/private/temp/dummy_5000_siswa.xlsx (5.000 siswa, uji performa)';

            $this->info('File data siswa sintetis (fiktif) berhasil dibuat:');
            foreach ($filesCreated as $item) {
                $this->line(' - ' . $item);
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal membuat data uji sintetis: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function headerRow(): array
    {
        return ['Nama', 'NIS', 'NISN', 'Kelas', 'No Peserta', 'Ruang', 'Foto'];
    }

    private function buildValidRows(int $count): iterable
    {
        yield $this->headerRow();

        $firstCount = count($this->firstNames);
        $middleCount = count($this->middleNames);
        $classCount = count($this->classes);

        for ($i = 1; $i <= $count; $i++) {
            $first = $this->firstNames[($i - 1) % $firstCount];
            $middle = $this->middleNames[(int) floor(($i - 1) / $firstCount) % $middleCount];
            $suffix = $i > ($firstCount * $middleCount) ? ' Ke-' . (int) ceil($i / ($firstCount * $middleCount)) : '';

            if ($i === 1) {
                $fullName = 'Muhammad Raden Mas Suryadinata Kusumawardhana, S.Pd.I.' . $suffix;
            } elseif ($i === 2) {
                $fullName = "Aisyah Qurrotu'aini Al-Munawwaroh" . $suffix;
            } else {
                $fullName = $first . ' ' . $middle . $suffix;
            }

            $nis = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $nisn = '00' . str_pad((string) (80000000 + $i), 8, '0', STR_PAD_LEFT);
            $className = $this->classes[($i - 1) % $classCount];
            $participantNumber = 'UAS-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $roomNumber = str_pad((string) (((int) floor(($i - 1) / 30)) + 1), 2, '0', STR_PAD_LEFT);
            $photoFile = $i <= 5 ? $nis . '.jpg' : '';

            yield [
                $fullName,
                $nis,
                $nisn,
                $className,
                $participantNumber,
                $roomNumber,
                $photoFile,
            ];
        }
    }

    private function buildValidationCaseRows(): array
    {
        return [
            $this->headerRow(),
            ['Andi Saputra', '00101', '0098765401', 'XI TJKT 1', 'UAS-0101', '01', '00101.jpg'],
            ['', '', '', '', '', '', ''], // Baris kosong di tengah tabel
            ['', '00102', '0098765402', 'XI TJKT 1', 'UAS-0102', '01', ''], // Nama siswa kosong
            ['Budi Santoso', '00103', '0098765403', '', 'UAS-0103', '01', ''], // Kelas kosong
            ['Citra Lestari', '00101', '0098765404', 'XI DKV 1', 'UAS-0101', '02', 'tidak-ada.jpg'], // NIS & No Peserta duplikat
            ['Dewi Anggraini', '00105', '0098765405', 'XI DKV 1', 'UAS-0105', '02', ''],
        ];
    }

    private function writeXlsx(string $filePath, iterable $rows): void
    {
        $writer = new XlsxWriter();
        $writer->openToFile($filePath);

        foreach ($rows as $rowValues) {
            $writer->addRow(Row::fromValues($rowValues));
        }

        $writer->close();
    }

    private function writeCsv(string $filePath, iterable $rows, string $delimiter = ','): void
    {
        $options = new CsvOptions();
        $options->FIELD_DELIMITER = $delimiter;

        $writer = new CsvWriter($options);
        $writer->openToFile($filePath);

        foreach ($rows as $rowValues) {
            $writer->addRow(Row::fromValues($rowValues));
        }

        $writer->close();
    }
}