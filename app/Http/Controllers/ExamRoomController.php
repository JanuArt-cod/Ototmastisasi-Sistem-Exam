<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Services\ExamRoomAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ExamRoomController extends Controller
{
    public function __construct(
        private readonly ExamRoomAssignmentService $roomService
    ) {}

    public function index(Request $request, ExamSession $examSession): View
    {
        $validQuery = $examSession->students()
            ->getQuery()
            ->whereNotNull('student_name')
            ->where('student_name', '!=', '')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '');

        $totalValidStudents = (clone $validQuery)->count();
        $studentsWithRoom = (clone $validQuery)
            ->whereNotNull('room_name')
            ->where('room_name', '!=', '')
            ->count();
        $studentsWithoutRoom = max(0, $totalValidStudents - $studentsWithRoom);

        $currentRooms = (clone $validQuery)
            ->whereNotNull('room_name')
            ->where('room_name', '!=', '')
            ->selectRaw('room_name, COUNT(*) as total')
            ->groupBy('room_name')
            ->orderBy('room_name')
            ->get();

        $preview = $request->session()->get($this->previewSessionKey($examSession));

        return view('rooms.index', [
            'session' => $examSession,
            'totalValidStudents' => $totalValidStudents,
            'studentsWithRoom' => $studentsWithRoom,
            'studentsWithoutRoom' => $studentsWithoutRoom,
            'currentRooms' => $currentRooms,
            'preview' => $preview,
        ]);
    }

    public function preview(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'room_prefix' => ['nullable', 'required_unless:allocation_mode,custom_per_room', 'string', 'max:40'],
            'start_number' => ['nullable', 'required_unless:allocation_mode,custom_per_room', 'integer', 'min:1', 'max:9999'],
            'digit_length' => ['nullable', 'required_unless:allocation_mode,custom_per_room', 'integer', 'in:1,2,3,4'],
            'allocation_mode' => ['required', 'in:by_capacity,by_room_count,custom_per_room'],
            'capacity_per_room' => ['nullable', 'required_if:allocation_mode,by_capacity', 'integer', 'min:1', 'max:500'],
            'total_rooms' => ['nullable', 'required_if:allocation_mode,by_room_count', 'integer', 'min:1', 'max:500'],
            'custom_rooms' => ['nullable', 'required_if:allocation_mode,custom_per_room', 'array', 'min:1', 'max:500'],
            'custom_rooms.*.name' => ['required_if:allocation_mode,custom_per_room', 'nullable', 'string', 'max:60'],
            'custom_rooms.*.capacity' => ['required_if:allocation_mode,custom_per_room', 'nullable', 'integer', 'min:1', 'max:500'],
            'distribution_method' => ['required', 'in:random,random_per_class,sequential'],
            'target_scope' => ['required', 'in:empty_only,all'],
        ], [
            'room_prefix.required_unless' => 'Awalan nama ruang wajib diisi (misalnya: Ruang).',
            'capacity_per_room.required_if' => 'Kapasitas maksimal siswa per ruang wajib diisi.',
            'total_rooms.required_if' => 'Jumlah total ruangan wajib diisi.',
            'custom_rooms.required_if' => 'Daftar ruangan beserta kapasitasnya wajib diisi minimal 1 ruangan.',
            'custom_rooms.*.name.required_if' => 'Nama ruang pada setiap baris wajib diisi.',
            'custom_rooms.*.capacity.required_if' => 'Kapasitas siswa pada setiap baris wajib diisi minimal 1.',
        ]);

        try {
            $previewData = $this->roomService->buildPreview($examSession, $validated);
            $request->session()->put($this->previewSessionKey($examSession), $previewData);

            return redirect()
                ->route('exam-sessions.rooms.index', $examSession)
                ->withInput()
                ->with('success', 'Pratinjau pembagian ruang berhasil disusun untuk ' . number_format($previewData['total_assigned'], 0, ',', '.') . ' siswa ke dalam ' . $previewData['total_rooms_used'] . ' ruangan. Periksa hasil di bawah sebelum menerapkan.');
        } catch (Throwable $e) {
            $request->session()->forget($this->previewSessionKey($examSession));

            return redirect()
                ->route('exam-sessions.rooms.index', $examSession)
                ->withInput()
                ->withErrors(['room' => $e->getMessage()]);
        }
    }

    public function apply(Request $request, ExamSession $examSession): RedirectResponse
    {
        $sessionKey = $this->previewSessionKey($examSession);
        $previewData = $request->session()->get($sessionKey);

        if (empty($previewData['assignments'])) {
            return redirect()
                ->route('exam-sessions.rooms.index', $examSession)
                ->withErrors(['room' => 'Belum ada pratinjau pembagian ruang yang siap diterapkan. Silakan buat pratinjau terlebih dahulu.']);
        }

        $updated = $this->roomService->applyAssignments($examSession, $previewData['assignments']);
        $request->session()->forget($sessionKey);

        return redirect()
            ->route('exam-sessions.rooms.index', $examSession)
            ->with('success', 'Pembagian ruang ujian berhasil disimpan untuk ' . number_format($updated, 0, ',', '.') . ' siswa.');
    }

    public function clear(Request $request, ExamSession $examSession): RedirectResponse
    {
        $cleared = $this->roomService->clearAllRooms($examSession);
        $request->session()->forget($this->previewSessionKey($examSession));

        return redirect()
            ->route('exam-sessions.rooms.index', $examSession)
            ->with('success', 'Kolom ruang ujian berhasil dikosongkan untuk ' . number_format($cleared, 0, ',', '.') . ' siswa.');
    }

    private function previewSessionKey(ExamSession $examSession): string
    {
        return 'room_assignment_preview_' . $examSession->id;
    }
}