<?php

namespace App\Console\Commands;

use App\Services\PrivateStorageService;
use Illuminate\Console\Command;
use Throwable;

class InitPrivateStorageCommand extends Command
{
    protected $signature = 'storage:init-private';

    protected $description = 'Menyiapkan struktur folder penyimpanan file privat dan menguji akses baca-tulis';

    public function handle(PrivateStorageService $storageService): int
    {
        try {
            $directories = $storageService->ensureDirectoriesExist();

            $testFile = PrivateStorageService::DIR_TEMP . '/healthcheck.txt';
            $storageService->disk()->put($testFile, 'ok');
            $content = $storageService->disk()->get($testFile);
            $storageService->deleteIfExists($testFile);

            if ($content !== 'ok') {
                $this->error('Pengujian baca-tulis file privat gagal memverifikasi isi file.');
                return self::FAILURE;
            }

            $this->info('Penyimpanan file privat berhasil disiapkan di: ' . storage_path('app/private'));
            foreach ($directories as $dir) {
                $this->line(' - ' . $dir);
            }
            $this->info('Pengujian tulis, baca, dan hapus file sementara berhasil.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}