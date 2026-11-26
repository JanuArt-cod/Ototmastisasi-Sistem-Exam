@php
    $showBackHeader = ($layout['show_back_header'] ?? false) && ! empty($layout['back_header_title']);
    $cardHeightMm = (float) ($layout['card_height_mm'] ?? 75.0);
    $scheduleCollection = $schedules ?? collect();
    $totalScheduleRows = $scheduleCollection->count();
    $denseThreshold = $cardHeightMm >= 68.0 ? 18 : 14;
    $isDenseSchedule = $totalScheduleRows >= $denseThreshold;

    $groupedSchedules = $scheduleCollection->groupBy(function ($item) {
        return $item->day_number . '||' . trim((string) $item->day_date_label);
    });
@endphp

<div class="ds-card ds-card-back">
    @if ($showBackHeader)
        <div class="ds-back-header">{{ $layout['back_header_title'] }}</div>
    @endif

    <div class="ds-back-body">
        @if ($groupedSchedules->isEmpty())
            <div class="ds-schedule-empty">
                Jadwal ujian belum diisi.<br>
                Atur jadwal melalui menu <strong>Jadwal Ujian</strong>.
            </div>
        @else
            <table class="ds-schedule-table {{ $isDenseSchedule ? 'ds-schedule-dense' : '' }}">
                <thead>
                    <tr>
                        <th style="width: 7%;">NO</th>
                        <th style="width: 28%;">HARI &amp; TANGGAL</th>
                        <th style="width: 24%;">JAM</th>
                        <th style="width: 41%;">MATA PELAJARAN</th>
                    </tr>
                </thead>
                <tbody>
                    @php $dayGroupIndex = 0; @endphp
                    @foreach ($groupedSchedules as $dayItems)
                        @php
                            $spanCount = $dayItems->count();
                            $isAltDay = ($dayGroupIndex % 2) === 1;
                            $dayGroupIndex++;
                        @endphp
                        @foreach ($dayItems as $idx => $row)
                            <tr class="{{ $isAltDay ? 'ds-day-alt' : '' }}">
                                @if ($idx === 0)
                                    <td rowspan="{{ $spanCount }}" class="ds-schedule-no">
                                        {{ $row->day_number }}
                                    </td>
                                    <td rowspan="{{ $spanCount }}" class="ds-schedule-day">
                                        {{ $row->day_date_label }}
                                    </td>
                                @endif
                                <td class="ds-schedule-time">{{ $row->time_slot }}</td>
                                <td class="ds-schedule-subject">{{ $row->subject_name }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>