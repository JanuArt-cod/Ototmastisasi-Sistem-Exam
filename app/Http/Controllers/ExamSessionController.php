<?php

namespace App\Http\Controllers;

use App\Models\CardSetting;
use App\Models\ExamSession;
use App\Services\ExamSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    public function __construct(
        private readonly ExamSessionService $sessionService
    ) {}

    public function index(): View
    {
        $sessions = $this->sessionService->listAllWithCounts();

        return view('exam-sessions.index', [
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_name' => ['required', 'string', 'max:255'],
        ], [
            'session_name.required' => 'Nama sesi ujian wajib diisi.',
            'session_name.max' => 'Nama sesi ujian maksimal 255 karakter.',
        ]);

        $session = $this->sessionService->createSession($validated['session_name']);

        return redirect()
            ->route('exam-sessions.show', $session)
            ->with('success', 'Sesi ujian baru berhasil dibuat dan siap digunakan.');
    }

    public function show(ExamSession $examSession): View
    {
        $examSession->loadCount('students');

        if (! $examSession->cardSetting) {
            $examSession->cardSetting()->create(CardSetting::defaultValues());
            $examSession->load('cardSetting');
        }

        return view('exam-sessions.show', [
            'session' => $examSession,
            'cardSetting' => $examSession->cardSetting,
        ]);
    }

    public function update(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'session_name' => ['required', 'string', 'max:255'],
        ], [
            'session_name.required' => 'Nama sesi ujian tidak boleh kosong.',
            'session_name.max' => 'Nama sesi ujian maksimal 255 karakter.',
        ]);

        $this->sessionService->renameSession($examSession, $validated['session_name']);

        return back()->with('success', 'Nama sesi ujian berhasil diperbarui.');
    }

    public function destroy(ExamSession $examSession): RedirectResponse
    {
        $sessionName = $examSession->session_name;
        $this->sessionService->deleteSession($examSession);

        return redirect()
            ->route('exam-sessions.index')
            ->with('success', 'Sesi "' . $sessionName . '" beserta seluruh datanya berhasil dihapus.');
    }
}