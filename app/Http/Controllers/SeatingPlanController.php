<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\SeatingPlan;
use App\Services\SeatingRandomizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SeatingPlanController extends Controller
{
    public function __construct(
        private readonly SeatingRandomizerService $randomizerService
    ) {
    }

    public function index(Request $request, ExamSession $examSession): View
    {
        $allPlans = $examSession->seatingPlans()->get();

        $requestedPlanId = $request->integer('plan_id');
        $activePlan = null;

        if ($requestedPlanId > 0) {
            $activePlan = $allPlans->firstWhere('id', $requestedPlanId);
        }

        if (! $activePlan) {
            $activePlan = $allPlans->firstWhere('status', 'draft')
                ?? $allPlans->firstWhere('status', 'finalized')
                ?? $allPlans->first();
        }

        $classBreakdown = $examSession->students()
            ->select('class_name', DB::raw('COUNT(*) as total'))
            ->groupBy('class_name')
            ->orderBy('class_name')
            ->get()
            ->map(function ($row) {
                $className = trim((string) $row->class_name);
                $gradeLevel = $this->randomizerService->extractGradeLevel($className);

                return [
                    'class_name' => $className !== '' ? $className : '(Tanpa Kelas)',
                    'raw_class_name' => (string) $row->class_name,
                    'total' => (int) $row->total,
                    'grade_level' => $gradeLevel,
                ];
            });

        $sessionInspection = $this->randomizerService->inspectCandidates($examSession, 'all', []);

        $assignments = null;
        $planClasses = collect();
        $planRooms = collect();
        $maxDeskNumber = 0;

        $search = trim((string) $request->query('search', ''));
        $filterRoom = trim((string) $request->query('filter_room', ''));
        $filterClass = trim((string) $request->query('filter_class', ''));
        $filterDesk = $request->integer('filter_desk');

        if ($activePlan) {
            $maxDeskNumber = (int) ($activePlan->assignments()->max('desk_number') ?? 1);

            $planClasses = $activePlan->assignments()
                ->select('class_name_snapshot')
                ->distinct()
                ->orderBy('class_name_snapshot')
                ->pluck('class_name_snapshot');

            $planRooms = $activePlan->assignments()
                ->select('room_name', 'room_number')
                ->distinct()
                ->orderBy('room_number')
                ->pluck('room_name');

            $assignmentQuery = $activePlan->assignments()->getQuery();

            if ($search !== '') {
                $assignmentQuery->where(function ($q) use ($search) {
                    $q->where('student_name_snapshot', 'like', '%' . $search . '%')
                        ->orWhere('desk_participant_number', 'like', '%' . $search . '%')
                        ->orWhere('participant_number_snapshot', 'like', '%' . $search . '%');
                });
            }

            if ($filterRoom !== '') {
                $assignmentQuery->where('room_name', $filterRoom);
            }

            if ($filterClass !== '') {
                $assignmentQuery->where('class_name_snapshot', $filterClass);
            }

            if ($filterDesk > 0) {
                $assignmentQuery->where('desk_number', $filterDesk);
            }

            $assignments = $assignmentQuery
                ->orderBy('room_number')
                ->orderBy('seat_number')
                ->paginate(30)
                ->withQueryString();
        }

        return view('seating.index', [
            'session' => $examSession,
            'allPlans' => $allPlans,
            'activePlan' => $activePlan,
            'classBreakdown' => $classBreakdown,
            'sessionInspection' => $sessionInspection,
            'assignments' => $assignments,
            'planClasses' => $planClasses,
            'planRooms' => $planRooms,
            'maxDeskNumber' => $maxDeskNumber,
            'filters' => [
                'search' => $search,
                'filter_room' => $filterRoom,
                'filter_class' => $filterClass,
                'filter_desk' => $filterDesk > 0 ? $filterDesk : '',
            ],
        ]);
    }

    public function generate(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'selection_mode' => ['required', 'in:all,classes'],
            'selected_classes' => ['nullable', 'array'],
            'selected_classes.*' => ['nullable', 'string', 'max:100'],
            'starting_seat_number' => ['nullable', 'integer', 'min:1', 'max:99999'],
        ]);

        try {
            $plan = $this->randomizerService->generateAndSavePlan($examSession, [
                'selection_mode' => $validated['selection_mode'],
                'selected_classes' => $validated['selected_classes'] ?? [],
                'starting_seat_number' => $validated['starting_seat_number'] ?? 1,
            ]);

            return redirect()
                ->route('exam-sessions.seating.index', [
                    'examSession' => $examSession->id,
                    'plan_id' => $plan->id,
                ])
                ->with(
                    'success',
                    'Penempatan Nomor Meja Peserta (Versi #' . $plan->version_number . ') berhasil dibuat untuk ' .
                    number_format($plan->total_students, 0, ',', '.') . ' peserta di ' .
                    number_format($plan->total_rooms, 0, ',', '.') . ' ruangan (' .
                    number_format($plan->total_desks, 0, ',', '.') . ' meja).'
                );
        } catch (Throwable $e) {
            return redirect()
                ->route('exam-sessions.seating.index', ['examSession' => $examSession->id])
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function finalize(ExamSession $examSession, SeatingPlan $seatingPlan): RedirectResponse
    {
        abort_if($seatingPlan->exam_session_id !== $examSession->id, 404);

        $finalized = $this->randomizerService->finalizePlan($seatingPlan);

        return redirect()
            ->route('exam-sessions.seating.index', [
                'examSession' => $examSession->id,
                'plan_id' => $finalized->id,
            ])
            ->with(
                'success',
                'Rencana penempatan Versi #' . $finalized->version_number . ' telah difinalisasi dan dikunci sebagai acuan cetak.'
            );
    }
}