<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\Student;
use App\Services\ParticipantNumberService;
use App\Services\PrivateStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        private readonly ParticipantNumberService $numberService,
        private readonly PrivateStorageService $storageService
    ) {}

    public function index(Request $request, ExamSession $examSession): View
    {
        $search = trim((string) $request->query('search', ''));
        $classFilter = trim((string) $request->query('class_name', ''));
        $issueFilter = trim((string) $request->query('issue', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $query = $examSession->students();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhere('nisn', 'like', '%' . $search . '%')
                    ->orWhere('participant_number', 'like', '%' . $search . '%')
                    ->orWhere('room_name', 'like', '%' . $search . '%');
            });
        }

        if ($classFilter !== '') {
            $query->where('class_name', $classFilter);
        }

        if ($issueFilter === 'empty_participant') {
            $query->where(function ($q) {
                $q->whereNull('participant_number')
                    ->orWhere('participant_number', '');
            });
        } elseif ($issueFilter === 'empty_nis') {
            $query->where(function ($q) {
                $q->whereNull('nis')
                    ->orWhere('nis', '');
            });
        } elseif ($issueFilter === 'empty_room') {
            $query->where(function ($q) {
                $q->whereNull('room_name')
                    ->orWhere('room_name', '');
            });
        }

        $students = $query
            ->orderBy('class_name')
            ->orderBy('student_name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $classes = $examSession->students()
            ->select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        $totalStudents = $examSession->students()->count();
        $emptyParticipantCount = $examSession->students()
            ->where(function ($q) {
                $q->whereNull('participant_number')
                    ->orWhere('participant_number', '');
            })
            ->count();

        $numberPreview = $request->session()->get($this->previewSessionKey($examSession));

        return view('students.index', [
            'session' => $examSession,
            'students' => $students,
            'classes' => $classes,
            'totalStudents' => $totalStudents,
            'emptyParticipantCount' => $emptyParticipantCount,
            'filters' => [
                'search' => $search,
                'class_name' => $classFilter,
                'issue' => $issueFilter,
                'per_page' => $perPage,
            ],
            'numberPreview' => $numberPreview,
        ]);
    }

    public function update(Request $request, ExamSession $examSession, Student $student): RedirectResponse
    {
        abort_if($student->exam_session_id !== $examSession->id, 404);

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'nis' => ['nullable', 'string', 'max:50'],
            'nisn' => ['nullable', 'string', 'max:50'],
            'participant_number' => ['nullable', 'string', 'max:100'],
            'room_name' => ['nullable', 'string', 'max:100'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'student_name.required' => 'Nama siswa wajib diisi.',
            'class_name.required' => 'Kelas / Rombel wajib diisi.',
        ]);

        $cleanData = [
            'student_name' => trim($validated['student_name']),
            'class_name' => trim($validated['class_name']),
            'nis' => trim((string) ($validated['nis'] ?? '')) ?: null,
            'nisn' => trim((string) ($validated['nisn'] ?? '')) ?: null,
            'participant_number' => trim((string) ($validated['participant_number'] ?? '')) ?: null,
            'room_name' => trim((string) ($validated['room_name'] ?? '')) ?: null,
            'internal_note' => trim((string) ($validated['internal_note'] ?? '')) ?: null,
        ];

        // Periksa keunikan nomor peserta, NIS, dan NISN dalam sesi yang sama
        if ($cleanData['participant_number'] !== null) {
            $duplicate = $examSession->students()
                ->where('id', '!=', $student->id)
                ->where('participant_number', $cleanData['participant_number'])
                ->first();
            if ($duplicate) {
                return back()->withErrors([
                    'participant_number' => 'Nomor peserta "' . $cleanData['participant_number'] . '" sudah digunakan oleh siswa ' . $duplicate->student_name . '.',
                ]);
            }
        }

        if ($cleanData['nis'] !== null) {
            $duplicateNis = $examSession->students()
                ->where('id', '!=', $student->id)
                ->where('nis', $cleanData['nis'])
                ->first();
            if ($duplicateNis) {
                return back()->withErrors([
                    'nis' => 'NIS "' . $cleanData['nis'] . '" sudah digunakan oleh siswa ' . $duplicateNis->student_name . '.',
                ]);
            }
        }

        if ($cleanData['nisn'] !== null) {
            $duplicateNisn = $examSession->students()
                ->where('id', '!=', $student->id)
                ->where('nisn', $cleanData['nisn'])
                ->first();
            if ($duplicateNisn) {
                return back()->withErrors([
                    'nisn' => 'NISN "' . $cleanData['nisn'] . '" sudah digunakan oleh siswa ' . $duplicateNisn->student_name . '.',
                ]);
            }
        }

        $student->update($cleanData);
        $examSession->touch();

        return back()->with('success', 'Data siswa "' . $student->student_name . '" berhasil diperbarui.');
    }

    public function destroy(ExamSession $examSession, Student $student): RedirectResponse
    {
        abort_if($student->exam_session_id !== $examSession->id, 404);

        $studentName = $student->student_name;
        $photoPath = $student->photo_path;

        $student->delete();
        $examSession->touch();

        if ($photoPath) {
            $this->storageService->deleteIfExists($photoPath);
        }

        return back()->with('success', 'Data siswa "' . $studentName . '" berhasil dihapus dari sesi.');
    }

    public function previewNumbers(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'prefix' => ['nullable', 'string', 'max:30'],
            'start_number' => ['required', 'integer', 'min:0', 'max:99999999'],
            'digit_length' => ['required', 'integer', 'min:1', 'max:12'],
            'sort_order' => ['required', 'in:class_name,student_name,id'],
            'target_scope' => ['required', 'in:only_empty,all'],
        ]);

        $previewResult = $this->numberService->preview($examSession, $validated);
        $request->session()->put($this->previewSessionKey($examSession), $previewResult);

        if ($previewResult['total_target'] === 0) {
            return back()->withErrors([
                'participant_number' => 'Tidak ada siswa yang sesuai dengan cakupan penomoran yang dipilih (misalnya semua siswa sudah memiliki nomor peserta).',
            ]);
        }

        if ($previewResult['has_conflicts']) {
            return back()->withErrors([
                'participant_number' => 'Ditemukan ' . count($previewResult['conflicts']) . ' konflik nomor peserta pada pratinjau. Silakan periksa daftar konflik dan ubah awalan atau angka awal.',
            ]);
        }

        return back()->with('success', 'Pratinjau nomor peserta berhasil dibuat untuk ' . number_format($previewResult['total_target'], 0, ',', '.') . ' siswa. Silakan periksa contoh nomor lalu klik Terapkan.');
    }

    public function applyNumbers(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'prefix' => ['nullable', 'string', 'max:30'],
            'start_number' => ['required', 'integer', 'min:0', 'max:99999999'],
            'digit_length' => ['required', 'integer', 'min:1', 'max:12'],
            'sort_order' => ['required', 'in:class_name,student_name,id'],
            'target_scope' => ['required', 'in:only_empty,all'],
            'confirm_overwrite' => ['nullable'],
        ]);

        if ($validated['target_scope'] === 'all' && ! $request->boolean('confirm_overwrite')) {
            return back()->withErrors([
                'confirm_overwrite' => 'Anda memilih untuk menimpa seluruh nomor peserta yang ada. Centang kotak konfirmasi terlebih dahulu.',
            ]);
        }

        try {
            $updatedCount = $this->numberService->apply($examSession, $validated);
            $request->session()->forget($this->previewSessionKey($examSession));

            return back()->with('success', 'Nomor peserta otomatis berhasil diterapkan ke ' . number_format($updatedCount, 0, ',', '.') . ' siswa.');
        } catch (Throwable $e) {
            return back()->withErrors([
                'participant_number' => $e->getMessage(),
            ]);
        }
    }

    private function previewSessionKey(ExamSession $examSession): string
    {
        return 'participant_number_preview_' . $examSession->id;
    }
}