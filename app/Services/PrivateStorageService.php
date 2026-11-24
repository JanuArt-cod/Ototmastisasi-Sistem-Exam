<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PrivateStorageService
{
    public const DIR_IMPORTS_TEMP = 'imports-temp';
    public const DIR_LOGOS = 'school-assets/logos';
    public const DIR_SIGNATURES = 'school-assets/signatures';
    public const DIR_STUDENT_PHOTOS = 'student-photos';
    public const DIR_EXPORTS = 'exports';
    public const DIR_TEMP = 'temp';

    public function disk(): Filesystem
    {
        return Storage::disk('private');
    }

    /**
     * Memastikan seluruh direktori privat tersedia dan terlindungi dari Git.
     */
    public function ensureDirectoriesExist(): array
    {
        $directories = [
            self::DIR_IMPORTS_TEMP,
            self::DIR_LOGOS,
            self::DIR_SIGNATURES,
            self::DIR_STUDENT_PHOTOS,
            self::DIR_EXPORTS,
            self::DIR_TEMP,
        ];

        $disk = $this->disk();

        foreach ($directories as $directory) {
            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }
        }

        $gitignoreContent = "*\n!.gitignore\n";
        if (! $disk->exists('.gitignore')) {
            $disk->put('.gitignore', $gitignoreContent);
        }

        return $directories;
    }

    /**
     * Menyimpan logo sekolah atau tanda tangan ke folder privat dengan nama acak.
     */
    public function storeSchoolAsset(UploadedFile $file, string $assetType): string
    {
        $directory = match ($assetType) {
            'logo' => self::DIR_LOGOS,
            'signature' => self::DIR_SIGNATURES,
            default => throw new InvalidArgumentException('Jenis aset sekolah tidak dikenali.'),
        };

        return $this->storeValidatedImage($file, $directory);
    }

    /**
     * Menyimpan foto siswa ke folder privat dengan nama acak.
     */
    public function storeStudentPhoto(UploadedFile $file): string
    {
        return $this->storeValidatedImage($file, self::DIR_STUDENT_PHOTOS);
    }

    /**
     * Menyimpan file Excel atau CSV sementara sebelum dikonfirmasi impornya.
     */
    public function storeTempImport(UploadedFile $file): string
    {
        $this->ensureDirectoriesExist();

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new InvalidArgumentException('Format file impor harus berupa .xlsx atau .csv.');
        }

        $safeFilename = Str::uuid()->toString() . '.' . $extension;
        $storedPath = $this->disk()->putFileAs(self::DIR_IMPORTS_TEMP, $file, $safeFilename);

        if ($storedPath === false) {
            throw new RuntimeException('Gagal menyimpan file impor sementara.');
        }

        return $storedPath;
    }

    /**
     * Menghasilkan path relatif untuk file PDF hasil ekspor.
     */
    public function makeExportRelativePath(string $prefix = 'kartu-ujian'): string
    {
        $this->ensureDirectoriesExist();

        $safePrefix = Str::slug($prefix) ?: 'kartu-ujian';
        $uniqueSuffix = now()->format('Ymd-His') . '-' . Str::lower(Str::random(6));

        return self::DIR_EXPORTS . '/' . $safePrefix . '-' . $uniqueSuffix . '.pdf';
    }

    /**
     * Mengambil path lengkap di sistem komputer dengan perlindungan path traversal.
     */
    public function fullPath(string $relativePath): string
    {
        $normalized = $this->sanitizeRelativePath($relativePath);

        return $this->disk()->path($normalized);
    }

    /**
     * Memeriksa apakah file privat tersedia.
     */
    public function exists(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $normalized = $this->sanitizeRelativePath($relativePath);

        return $this->disk()->exists($normalized);
    }

    /**
     * Menghapus file privat secara aman apabila ada.
     */
    public function deleteIfExists(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $normalized = $this->sanitizeRelativePath($relativePath);

        if ($this->disk()->exists($normalized)) {
            return $this->disk()->delete($normalized);
        }

        return false;
    }

    /**
     * Membersihkan file sementara impor dan temp yang sudah lebih lama dari batas menit.
     */
    public function cleanupOldTempFiles(int $olderThanMinutes = 120): int
    {
        $this->ensureDirectoriesExist();

        $disk = $this->disk();
        $threshold = now()->subMinutes($olderThanMinutes)->getTimestamp();
        $deletedCount = 0;

        foreach ([self::DIR_IMPORTS_TEMP, self::DIR_TEMP] as $directory) {
            foreach ($disk->files($directory) as $filePath) {
                if ($disk->lastModified($filePath) < $threshold) {
                    if ($disk->delete($filePath)) {
                        $deletedCount++;
                    }
                }
            }
        }

        return $deletedCount;
    }

    private function storeValidatedImage(UploadedFile $file, string $directory): string
    {
        $this->ensureDirectoriesExist();

        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $mimeType = $file->getMimeType();
        if (! isset($mimeToExtension[$mimeType])) {
            throw new InvalidArgumentException('Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.');
        }

        $safeFilename = Str::uuid()->toString() . '.' . $mimeToExtension[$mimeType];
        $storedPath = $this->disk()->putFileAs($directory, $file, $safeFilename);

        if ($storedPath === false) {
            throw new RuntimeException('Gagal menyimpan file gambar ke penyimpanan privat.');
        }

        return $storedPath;
    }

    private function sanitizeRelativePath(string $relativePath): string
    {
        $cleaned = str_replace('\\', '/', trim($relativePath));
        $cleaned = ltrim($cleaned, '/');

        if ($cleaned === '' || str_contains($cleaned, '..')) {
            throw new InvalidArgumentException('Lokasi file tidak valid.');
        }

        return $cleaned;
    }
}