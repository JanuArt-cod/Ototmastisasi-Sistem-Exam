@php
    $cardHeightMm = (float) ($layout['card_height_mm'] ?? 55.0);
    $innerHeightMm = max(15, round($cardHeightMm - 0.8, 2));

    $showLogo = ($layout['show_logo'] ?? true) && ! empty($logoUrl);
    $showFoundation = ($layout['show_foundation_name'] ?? true) && ! empty($session->foundation_name);
    $showAccreditation = ($layout['show_accreditation'] ?? true) && ! empty($session->school_accreditation);
    $showAddress = ($layout['show_school_address'] ?? true) && ! empty($session->school_address);

    $showPhoto = $layout['show_photo'] ?? false;
    $photoPosition = ($layout['photo_position'] ?? 'right') === 'left' ? 'left' : 'right';

    $showParticipantNumber = $layout['show_participant_number'] ?? true;
    $showNis = $layout['show_nis'] ?? false;
    $showNisn = $layout['show_nisn'] ?? false;
    $showClass = $layout['show_class_name'] ?? true;
    $showRoom = $layout['show_room_name'] ?? true;

    $showIssueCity = ($layout['show_issue_city'] ?? true) && ! empty($session->issue_city);
    $showExamDate = ($layout['show_exam_date'] ?? true) && ! empty($session->exam_date);
    $showSignature = ($layout['show_signature'] ?? true) && ! empty($signatureUrl);
    $showStamp = ($layout['show_stamp'] ?? true) && ! empty($stampUrl);
    $showNip = ($layout['show_signatory_nip'] ?? true) && ! empty($session->signatory_nip);
    $showNote = ($layout['show_card_note'] ?? false) && ! empty($session->card_note);

    $signatoryTitle = $session->signatory_title ?: ($session->headmaster_title ?? ($session->principal_title ?? 'Kepala Sekolah'));
    $signatoryName = $session->signatory_name ?: ($session->headmaster_name ?? ($session->principal_name ?? ''));

    // Format tanggal terbit/ujian ke format Indonesia bersih tanpa jam (00:00:00)
    $formattedExamDate = '';
    if (! empty($session->exam_date)) {
        $rawDate = $session->exam_date;
        $indoMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        if ($rawDate instanceof \DateTimeInterface) {
            $day = $rawDate->format('d');
            $month = $indoMonths[(int) $rawDate->format('n')] ?? $rawDate->format('F');
            $year = $rawDate->format('Y');
            $formattedExamDate = "{$day} {$month} {$year}";
        } else {
            $strDate = trim((string) $rawDate);
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $strDate)) {
                try {
                    $parsed = \Carbon\Carbon::parse($strDate);
                    $day = $parsed->format('d');
                    $month = $indoMonths[(int) $parsed->format('n')] ?? $parsed->format('F');
                    $year = $parsed->format('Y');
                    $formattedExamDate = "{$day} {$month} {$year}";
                } catch (\Throwable $e) {
                    $formattedExamDate = preg_replace('/\s+\d{2}:\d{2}(:\d{2})?$/', '', $strDate);
                }
            } else {
                $formattedExamDate = preg_replace('/\s+\d{2}:\d{2}(:\d{2})?$/', '', $strDate);
            }
        }
    }

    $cityDateParts = [];
    if ($showIssueCity) {
        $cityDateParts[] = trim((string) $session->issue_city);
    }
    if ($showExamDate && $formattedExamDate !== '') {
        $cityDateParts[] = $formattedExamDate;
    }
    $cityDateLine = implode(', ', $cityDateParts);

    $nisParts = [];
    if ($showNis) {
        $nisParts[] = $student->nis ?: '-';
    }
    if ($showNisn) {
        $nisParts[] = $student->nisn ?: '-';
    }
    $nisCombined = implode(' / ', $nisParts);
    $nisLabel = ($showNis && $showNisn) ? 'NIS / NISN' : ($showNis ? 'NIS' : 'NISN');

    $studentName = (string) ($student->student_name ?? '-');
    $nameLength = mb_strlen($studentName);
    $nameFontSize = $nameLength > 34 ? '5.2pt' : ($nameLength > 26 ? '5.6pt' : '6pt');
@endphp

<div class="ds-card ds-card-front">
    <table class="ds-shell" style="width: 100%; height: {{ $innerHeightMm }}mm; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; padding: 0;">
                {{-- KOP YAYASAN & SEKOLAH --}}
                <table class="ds-front-header">
                    <tr>
                        @if ($showLogo)
                            <td class="ds-front-logo-cell">
                                <img src="{{ $logoUrl }}" alt="Logo" class="ds-front-logo">
                            </td>
                        @endif
                        <td class="ds-front-school-cell">
                            @if ($showFoundation)
                                <div class="ds-front-foundation">{{ $session->foundation_name }}</div>
                            @endif
                            <div class="ds-front-school">{{ $session->school_name ?: 'NAMA SEKOLAH' }}</div>
                            @if ($showAccreditation)
                                <div class="ds-front-accreditation">{{ $session->school_accreditation }}</div>
                            @endif
                            @if ($showAddress)
                                <div class="ds-front-address">{{ $session->school_address }}</div>
                            @endif
                        </td>
                    </tr>
                </table>

                {{-- JUDUL KARTU & NAMA UJIAN --}}
                <div class="ds-front-banner">
                    <div class="ds-front-card-title">{{ $session->card_title ?: 'KARTU PESERTA UJIAN' }}</div>
                    <div class="ds-front-exam-title">
                        {{ trim(($session->exam_title ?: 'UJIAN SEKOLAH') . ' ' . ($session->academic_year ?: '')) }}
                    </div>
                </div>

                {{-- IDENTITAS PESERTA UJIAN --}}
                <table class="ds-front-body">
                    <tr>
                        @if ($showPhoto && $photoPosition === 'left')
                            <td class="uk-photo-td-left">
                                <div class="uk-photo-box">
                                    @if (! empty($student->photo_data_uri))
                                        <img src="{{ $student->photo_data_uri }}" alt="Foto" class="uk-photo-img">
                                    @else
                                        <div class="uk-photo-empty">FOTO</div>
                                    @endif
                                </div>
                            </td>
                        @endif

                        <td class="ds-front-fields-cell">
                            <table class="ds-front-fields-table">
                                @if ($showParticipantNumber)
                                    <tr>
                                        <td class="ds-front-label">No. Peserta</td>
                                        <td class="ds-front-sep">:</td>
                                        <td class="ds-front-val ds-front-val-num">{{ $student->participant_number ?: '-' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="ds-front-label">Nama Lengkap</td>
                                    <td class="ds-front-sep">:</td>
                                    <td class="ds-front-val" style="font-size: {{ $nameFontSize }};">{{ $studentName }}</td>
                                </tr>
                                @if ($showNis || $showNisn)
                                    <tr>
                                        <td class="ds-front-label">{{ $nisLabel }}</td>
                                        <td class="ds-front-sep">:</td>
                                        <td class="ds-front-val">{{ $nisCombined }}</td>
                                    </tr>
                                @endif
                                @if ($showClass)
                                    <tr>
                                        <td class="ds-front-label">Kelas</td>
                                        <td class="ds-front-sep">:</td>
                                        <td class="ds-front-val">{{ $student->class_name ?: '-' }}</td>
                                    </tr>
                                @endif
                                @if ($showRoom)
                                    <tr>
                                        <td class="ds-front-label">Ruang</td>
                                        <td class="ds-front-sep">:</td>
                                        <td class="ds-front-val">{{ $student->room_name ?: '-' }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>

                        @if ($showPhoto && $photoPosition === 'right')
                            <td class="uk-photo-td-right">
                                <div class="uk-photo-box uk-photo-box-right">
                                    @if (! empty($student->photo_data_uri))
                                        <img src="{{ $student->photo_data_uri }}" alt="Foto" class="uk-photo-img">
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
            <td style="vertical-align: bottom; padding: 0 2.2mm 1mm 2.2mm;">
                {{-- BAGIAN PENGESAHAN & CATATAN KAKI --}}
                <table class="ds-front-bottom">
                    <tr>
                        <td class="ds-front-note-cell">
                            @if ($showNote)
                                <div class="ds-front-note">* {{ $session->card_note }}</div>
                            @endif
                        </td>

                        <td class="ds-front-sign-cell">
                            @if ($cityDateLine !== '')
                                <div class="ds-front-sign-city">{{ $cityDateLine }}</div>
                            @endif
                            <div class="ds-front-sign-role">{{ $signatoryTitle }},</div>

                            <div class="ds-front-sign-media">
                                @if ($showStamp)
                                    <img src="{{ $stampUrl }}" alt="Stempel" class="ds-front-stamp-img">
                                @endif
                                @if ($showSignature)
                                    <img src="{{ $signatureUrl }}" alt="TTD" class="ds-front-sign-img">
                                @endif
                            </div>

                            @if ($signatoryName !== '')
                                <div class="ds-front-sign-name">{{ $signatoryName }}</div>
                            @endif
                            @if ($showNip)
                                <div class="ds-front-sign-nip">{{ $session->signatory_nip }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>