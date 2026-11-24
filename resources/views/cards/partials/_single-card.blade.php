@php
    $widthMm = (float) ($layout['card_width_mm'] ?? 90);
    $heightMm = (float) ($layout['card_height_mm'] ?? 55);
    $innerHeightMm = max(15, round($heightMm - 0.8, 2));

    $showLogo = (bool) ($layout['show_logo'] ?? true);
    $showSchoolAddress = (bool) ($layout['show_school_address'] ?? true);
    $showExamDate = (bool) ($layout['show_exam_date'] ?? false);
    $showParticipantNumber = (bool) ($layout['show_participant_number'] ?? true);
    $showNis = (bool) ($layout['show_nis'] ?? true);
    $showNisn = (bool) ($layout['show_nisn'] ?? true);
    $showClassName = (bool) ($layout['show_class_name'] ?? true);
    $showRoomName = (bool) ($layout['show_room_name'] ?? true);
    $showPhoto = (bool) ($layout['show_photo'] ?? true);
    $photoPosition = ($layout['photo_position'] ?? 'right') === 'left' ? 'left' : 'right';
    $showSignature = (bool) ($layout['show_signature'] ?? true);
    $showCardNote = (bool) ($layout['show_card_note'] ?? true);

    $logoSrc = $assets['logo_src'] ?? null;
    $signatureSrc = $assets['signature_src'] ?? null;
    $photoSrc = $assets['photo_src'] ?? null;

    $schoolName = $session->school_name ?: 'NAMA SEKOLAH';
    $schoolAddress = $session->school_address ?? '';
    $cardTitle = $session->card_title ?: 'KARTU PESERTA UJIAN';
    $examTitle = $session->exam_title ?: 'UJIAN SEKOLAH';
    $academicYear = $session->academic_year ?: '';
    $term = $session->term ?? '';
    $examDateText = '';
    if (! empty($session->exam_date)) {
        $examDateText = $session->exam_date instanceof \DateTimeInterface
            ? $session->exam_date->format('d/m/Y')
            : preg_replace('/\s+\d{2}:\d{2}(:\d{2})?$/', '', (string) $session->exam_date);
    }

    $studentName = (string) ($student->student_name ?? '-');
    $nameLength = mb_strlen($studentName);
    $nameFontSize = $nameLength > 34 ? '6.2pt' : ($nameLength > 25 ? '6.8pt' : '7.4pt');
@endphp

<div class="uk-card" style="width: {{ $widthMm }}mm; height: {{ $heightMm }}mm;">
    <table class="uk-shell" style="width: 100%; height: {{ $innerHeightMm }}mm; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; padding: 0;">
                {{-- KOP / KEPALA KARTU --}}
                <table class="uk-head">
                    <tr>
                        @if ($showLogo and ! empty($logoSrc))
                            <td class="uk-logo-td">
                                <img src="{{ $logoSrc }}" alt="Logo" class="uk-logo-img">
                            </td>
                        @endif
                        <td class="uk-head-td">
                            <div class="uk-school">{{ $schoolName }}</div>
                            @if ($showSchoolAddress and $schoolAddress !== '')
                                <div class="uk-addr">{{ \Illuminate\Support\Str::limit($schoolAddress, 68) }}</div>
                            @endif
                            <div class="uk-title">{{ $cardTitle }}</div>
                            <div class="uk-exam">
                                {{ $examTitle }}
                                @if ($academicYear !== '')
                                    &bull; TP {{ $academicYear }}
                                @endif
                                @if ($term !== '')
                                    ({{ $term }})
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                {{-- ISI DATA SISWA & FOTO --}}
                <table class="uk-body">
                    <tr>
                        @if ($showPhoto and $photoPosition === 'left')
                            <td class="uk-photo-td-left">
                                <div class="uk-photo-box">
                                    @if (! empty($photoSrc))
                                        <img src="{{ $photoSrc }}" alt="Foto" class="uk-photo-img">
                                    @else
                                        <div class="uk-photo-empty">FOTO</div>
                                    @endif
                                </div>
                            </td>
                        @endif

                        <td style="vertical-align: top;">
                            <table class="uk-info">
                                @if ($showParticipantNumber)
                                    <tr>
                                        <td class="uk-lbl">No. Peserta</td>
                                        <td class="uk-sep">:</td>
                                        <td class="uk-val-num">{{ $student->participant_number ?: '-' }}</td>
                                    </tr>
                                @endif

                                <tr>
                                    <td class="uk-lbl">Nama Siswa</td>
                                    <td class="uk-sep">:</td>
                                    <td class="uk-val-name" style="font-size: {{ $nameFontSize }};">{{ $studentName }}</td>
                                </tr>

                                @if ($showNis or $showNisn)
                                    <tr>
                                        <td class="uk-lbl">
                                            @if ($showNis and $showNisn)
                                                NIS / NISN
                                            @elseif ($showNis)
                                                NIS
                                            @else
                                                NISN
                                            @endif
                                        </td>
                                        <td class="uk-sep">:</td>
                                        <td class="uk-val">
                                            @if ($showNis and $showNisn)
                                                {{ $student->nis ?: '-' }} / {{ $student->nisn ?: '-' }}
                                            @elseif ($showNis)
                                                {{ $student->nis ?: '-' }}
                                            @else
                                                {{ $student->nisn ?: '-' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif

                                @if ($showClassName)
                                    <tr>
                                        <td class="uk-lbl">Kelas</td>
                                        <td class="uk-sep">:</td>
                                        <td class="uk-val-bold">{{ $student->class_name ?: '-' }}</td>
                                    </tr>
                                @endif

                                @if ($showRoomName)
                                    <tr>
                                        <td class="uk-lbl">Ruang Ujian</td>
                                        <td class="uk-sep">:</td>
                                        <td class="uk-val">{{ $student->room_name ?: '-' }}</td>
                                    </tr>
                                @endif

                                @if ($showExamDate and $examDateText !== '')
                                    <tr>
                                        <td class="uk-lbl">Tanggal</td>
                                        <td class="uk-sep">:</td>
                                        <td class="uk-val">{{ $examDateText }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>

                        @if ($showPhoto and $photoPosition === 'right')
                            <td class="uk-photo-td-right">
                                <div class="uk-photo-box uk-photo-box-right">
                                    @if (! empty($photoSrc))
                                        <img src="{{ $photoSrc }}" alt="Foto" class="uk-photo-img">
                                    @else
                                        <div class="uk-photo-empty">FOTO</div>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: bottom; padding: 0 2mm 1mm 2mm;">
                {{-- BAGIAN BAWAH: CATATAN & PENGESAHAN --}}
                <table class="uk-foot">
                    <tr>
                        <td class="uk-note">
                            @if ($showCardNote and ! empty($session->card_note))
                                <em>* {{ \Illuminate\Support\Str::limit($session->card_note, 90) }}</em>
                            @endif
                        </td>

                        @if ($showSignature and (! empty($session->principal_name) or ! empty($session->principal_title)))
                            <td class="uk-sig">
                                <div>{{ $session->principal_title ?: 'Kepala Sekolah' }},</div>
                                <div class="uk-sig-box">
                                    @if (! empty($signatureSrc))
                                        <img src="{{ $signatureSrc }}" alt="TTD" class="uk-sig-img">
                                    @endif
                                </div>
                                <div class="uk-sig-name">{{ $session->principal_name ?: '' }}</div>
                            </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>