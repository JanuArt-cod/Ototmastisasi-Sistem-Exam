<?php

namespace App\Services;

use App\Models\CardSetting;
use App\Models\ExamSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamSessionService
{
    public function __construct(
        private readonly PrivateStorageService $storageService
    ) {}

    /**
     * Mengambil seluruh daftar sesi ujian beserta jumlah siswa di setiap sesi.
     */
    public function listAllWithCounts(): Collection
    {
        return ExamSession::query()
            ->withCount('students')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Membuat sesi ujian baru sekaligus pengaturan kartu bawaan dalam satu transaksi.
     */
    public function createSession(string $sessionName): ExamSession
    {
        return DB::transaction(function () use ($sessionName) {
            $session = ExamSession::create([
                'session_name' => trim($sessionName),
            ]);

            $session->cardSetting()->create(CardSetting::defaultValues());

            return $session;
        });
    }

    /**
     * Mengubah nama sesi ujian yang sudah ada.
     */
    public function renameSession(ExamSession $examSession, string $newName): ExamSession
    {
        $examSession->update([
            'session_name' => trim($newName),
        ]);

        return $examSession;
    }

    /**
     * Menghapus sesi ujian beserta seluruh data siswa dan file privat terkait.
     */
    public function deleteSession(ExamSession $examSession): void
    {
        $sessionFiles = array_filter([
            $examSession->logo_path,
            $examSession->signature_path,
        ]);

        $studentPhotos = $examSession->students()
            ->whereNotNull('photo_path')
            ->pluck('photo_path')
            ->all();

        $filesToDelete = array_unique(array_merge($sessionFiles, $studentPhotos));

        DB::transaction(function () use ($examSession) {
            $examSession->delete();
        });

        foreach ($filesToDelete as $relativePath) {
            $this->storageService->deleteIfExists($relativePath);
        }
    }
}