<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamInformationController extends Controller
{
    public function edit(ExamSession $examSession): View
    {
        return view('exam-information.edit', [
            'session' => $examSession,
        ]);
    }

    public function update(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'foundation_name' => ['nullable', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:150'],
            'school_accreditation' => ['nullable', 'string', 'max:80'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'card_title' => ['required', 'string', 'max:120'],
            'exam_title' => ['required', 'string', 'max:150'],
            'academic_year' => ['required', 'string', 'max:50'],
            'exam_date' => ['nullable', 'string', 'max:80'],
            'issue_city' => ['nullable', 'string', 'max:100'],
            'card_note' => ['nullable', 'string', 'max:255'],
            'signatory_title' => ['nullable', 'string', 'max:100'],
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'signatory_nip' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'stamp' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_signature' => ['nullable', 'boolean'],
            'remove_stamp' => ['nullable', 'boolean'],
        ], [
            'school_name.required' => 'Nama sekolah/madrasah wajib diisi.',
            'card_title.required' => 'Judul kartu wajib diisi (contoh: KARTU PESERTA UJIAN).',
            'exam_title.required' => 'Nama ujian wajib diisi.',
            'academic_year.required' => 'Tahun pelajaran wajib diisi.',
            'logo.image' => 'File logo harus berupa gambar (JPG, PNG, atau WEBP).',
            'logo.max' => 'Ukuran file logo maksimal 2 MB.',
            'signature.image' => 'File tanda tangan harus berupa gambar (JPG, PNG, atau WEBP).',
            'signature.max' => 'Ukuran file tanda tangan maksimal 2 MB.',
            'stamp.image' => 'File stempel harus berupa gambar (JPG, PNG, atau WEBP).',
            'stamp.max' => 'Ukuran file stempel maksimal 2 MB.',
        ]);

        $signatoryTitle = $this->cleanNullableString($validated['signatory_title'] ?? null);
        $signatoryName = $this->cleanNullableString($validated['signatory_name'] ?? null);

        $examSession->foundation_name = $this->cleanNullableString($validated['foundation_name'] ?? null);
        $examSession->school_name = trim($validated['school_name']);
        $examSession->school_accreditation = $this->cleanNullableString($validated['school_accreditation'] ?? null);
        $examSession->school_address = $this->cleanNullableString($validated['school_address'] ?? null);
        $examSession->card_title = trim($validated['card_title']);
        $examSession->exam_title = trim($validated['exam_title']);
        $examSession->academic_year = trim($validated['academic_year']);
        $examSession->exam_date = $this->cleanNullableString($validated['exam_date'] ?? null);
        $examSession->issue_city = $this->cleanNullableString($validated['issue_city'] ?? null);
        $examSession->card_note = $this->cleanNullableString($validated['card_note'] ?? null);
        $examSession->signatory_title = $signatoryTitle;
        $examSession->signatory_name = $signatoryName;
        $examSession->signatory_nip = $this->cleanNullableString($validated['signatory_nip'] ?? null);

        // Jaga kompatibilitas kolom lama agar template 1 sisi tetap terbaca
        if (Schema::hasColumn('exam_sessions', 'headmaster_name')) {
            $examSession->setAttribute('headmaster_name', $signatoryName);
        }
        if (Schema::hasColumn('exam_sessions', 'principal_name')) {
            $examSession->setAttribute('principal_name', $signatoryName);
        }
        if (Schema::hasColumn('exam_sessions', 'headmaster_title')) {
            $examSession->setAttribute('headmaster_title', $signatoryTitle);
        }
        if (Schema::hasColumn('exam_sessions', 'principal_title')) {
            $examSession->setAttribute('principal_title', $signatoryTitle);
        }

        $assetDirectory = 'sessions/' . $examSession->id . '/assets';

        if ($request->boolean('remove_logo') and $examSession->logo_path) {
            $this->deletePrivateFile($examSession->logo_path);
            $examSession->logo_path = null;
        }
        if ($request->hasFile('logo')) {
            if ($examSession->logo_path) {
                $this->deletePrivateFile($examSession->logo_path);
            }
            $examSession->logo_path = $request->file('logo')->store($assetDirectory, 'local');
        }

        if ($request->boolean('remove_signature') and $examSession->signature_path) {
            $this->deletePrivateFile($examSession->signature_path);
            $examSession->signature_path = null;
        }
        if ($request->hasFile('signature')) {
            if ($examSession->signature_path) {
                $this->deletePrivateFile($examSession->signature_path);
            }
            $examSession->signature_path = $request->file('signature')->store($assetDirectory, 'local');
        }

        if ($request->boolean('remove_stamp') and $examSession->stamp_path) {
            $this->deletePrivateFile($examSession->stamp_path);
            $examSession->stamp_path = null;
        }
        if ($request->hasFile('stamp')) {
            if ($examSession->stamp_path) {
                $this->deletePrivateFile($examSession->stamp_path);
            }
            $examSession->stamp_path = $request->file('stamp')->store($assetDirectory, 'local');
        }

        $examSession->save();

        return redirect()
            ->route('exam-sessions.information.edit', $examSession)
            ->with('success', 'Informasi sekolah, periode ujian, dan kelengkapan pengesahan berhasil disimpan.');
    }

    public function asset(ExamSession $examSession, string $type): BinaryFileResponse
    {
        $rawPath = match ($type) {
            'logo' => $examSession->logo_path,
            'signature' => $examSession->signature_path,
            'stamp' => $examSession->stamp_path,
            default => null,
        };

        if (! $rawPath) {
            abort(404);
        }

        $normalizedPath = $this->normalizeLocalPath($rawPath);

        if (! Storage::disk('local')->exists($normalizedPath)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($normalizedPath), [
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    private function cleanNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeLocalPath(string $path): string
    {
        $cleaned = str_replace('\\', '/', trim($path));
        if (str_starts_with($cleaned, 'private/')) {
            return substr($cleaned, strlen('private/'));
        }

        return ltrim($cleaned, '/');
    }

    private function deletePrivateFile(string $path): void
    {
        $normalized = $this->normalizeLocalPath($path);
        if ($normalized !== '' and Storage::disk('local')->exists($normalized)) {
            Storage::disk('local')->delete($normalized);
        }
    }
}