<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\SeatingPlan;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeatingFloorPlanController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService
    ) {
    }

    public function show(Request $request, ExamSession $examSession, SeatingPlan $seatingPlan): View
    {
        abort_if($seatingPlan->exam_session_id !== $examSession->id, 404);

        $rooms = $seatingPlan->assignments()
            ->select('room_number', 'room_name')
            ->distinct()
            ->orderBy('room_number')
            ->get();

        abort_if($rooms->isEmpty(), 404, 'Belum ada data ruangan pada rencana penempatan ini.');

        $requestedRoomNumber = $request->integer('room');
        $activeRoom = $rooms->firstWhere('room_number', $requestedRoomNumber) ?? $rooms->first();

        $layoutSettings = $seatingPlan->resolvedLayoutSettings();
        $columns = $request->integer('cols');
        if ($columns < 1 || $columns > 6) {
            $columns = max(1, min(6, (int) ($layoutSettings['floor_columns'] ?? 3)));
        }

        $roomAssignments = $seatingPlan->assignments()
            ->where('room_number', $activeRoom->room_number)
            ->orderBy('desk_number')
            ->orderBy('seat_position')
            ->get();

        $desks = $roomAssignments
            ->groupBy('desk_number')
            ->map(function ($seats, $deskNumber) {
                return [
                    'desk_number' => (int) $deskNumber,
                    'seat_1' => $seats->firstWhere('seat_position', 1),
                    'seat_2' => $seats->firstWhere('seat_position', 2),
                ];
            })
            ->values();

        $floorPlanUrl = route('exam-sessions.seating.floor-plan', [
            'examSession' => $examSession->id,
            'seatingPlan' => $seatingPlan->id,
            'room' => $activeRoom->room_number,
        ]);

        $qrDataUri = $this->qrCodeService->toSvgDataUri($floorPlanUrl, 5);

        return view('seating.floor-plan', [
            'session' => $examSession,
            'plan' => $seatingPlan,
            'rooms' => $rooms,
            'activeRoom' => $activeRoom,
            'desks' => $desks,
            'totalRoomStudents' => $roomAssignments->count(),
            'columns' => $columns,
            'floorPlanUrl' => $floorPlanUrl,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    public function exportHtml(ExamSession $examSession, SeatingPlan $seatingPlan): Response
    {
        abort_if($seatingPlan->exam_session_id !== $examSession->id, 404);

        $allAssignments = $seatingPlan->assignments()
            ->orderBy('room_number')
            ->orderBy('desk_number')
            ->orderBy('seat_position')
            ->get();

        abort_if($allAssignments->isEmpty(), 404, 'Belum ada data ruangan pada rencana penempatan ini.');

        $roomsPayload = [];
        foreach ($allAssignments->groupBy('room_number') as $roomNum => $roomAssignments) {
            $roomName = (string) $roomAssignments->first()->room_name;
            $desks = $roomAssignments
                ->groupBy('desk_number')
                ->map(function ($seats, $deskNumber) {
                    return [
                        'desk_number' => (int) $deskNumber,
                        'seat_1' => $seats->firstWhere('seat_position', 1),
                        'seat_2' => $seats->firstWhere('seat_position', 2),
                    ];
                })
                ->values();

            $roomsPayload[] = [
                'room_number' => (int) $roomNum,
                'room_name' => $roomName,
                'room_slug' => Str::slug($roomName),
                'total_students' => $roomAssignments->count(),
                'total_desks' => $desks->count(),
                'desks' => $desks,
            ];
        }

        $html = view('seating.floor-plan-standalone', [
            'session' => $examSession,
            'plan' => $seatingPlan,
            'roomsPayload' => $roomsPayload,
        ])->render();

        $slugSession = Str::slug((string) $examSession->session_name) ?: 'sesi-ujian';
        $filename = 'denah-ujian-' . $slugSession . '-v' . $seatingPlan->version_number . '.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}