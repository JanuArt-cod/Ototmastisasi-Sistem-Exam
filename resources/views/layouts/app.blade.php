<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Otomatisasi Kartu Ujian Siswa')</title>
    <style>
        :root {
            --bg-page: #f1f5f9;
            --bg-surface: #ffffff;
            --bg-muted: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --border-color: #cbd5e1;
            --primary: #1e3a8a;
            --primary-hover: #1e40af;
            --danger: #b91c1c;
            --danger-hover: #991b1b;
            --success-bg: #ecfdf5;
            --success-border: #6ee7b7;
            --success-text: #065f46;
            --error-bg: #fef2f2;
            --error-border: #fca5a5;
            --error-text: #991b1b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            line-height: 1.5;
            font-size: 14px;
        }

        .topbar {
            background-color: var(--primary);
            color: #ffffff;
            padding: 14px 24px;
            border-bottom: 1px solid #172554;
        }

        .topbar-inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }

        .brand-sub {
            font-size: 12px;
            color: #cbd5e1;
            display: block;
            font-weight: 400;
        }

        .nav-link {
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.22);
        }

        .container {
            max-width: 1140px;
            margin: 24px auto;
            padding: 0 24px 48px;
        }

        .card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .card-title {
            margin: 0 0 6px;
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .card-desc {
            margin: 0 0 16px;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            font-size: 13px;
        }

        .alert-success {
            background-color: var(--success-bg);
            border-color: var(--success-border);
            color: var(--success-text);
        }

        .alert-error {
            background-color: var(--error-bg);
            border-color: var(--error-border);
            color: var(--error-text);
        }

        .form-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .input-text {
            flex: 1;
            min-width: 260px;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            color: var(--text-primary);
            background-color: #ffffff;
        }

        .input-text:focus {
            outline: 2px solid #93c5fd;
            border-color: var(--primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: #ffffff;
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background-color: var(--bg-muted);
        }

        .btn-danger {
            background-color: #ffffff;
            border-color: #fca5a5;
            color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #fef2f2;
            border-color: var(--danger);
        }

        .btn-sm {
            padding: 6px 11px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            text-align: left;
            padding: 10px 12px;
            background-color: var(--bg-muted);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background-color: #e2e8f0;
            color: #334155;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .actions-group {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 32px 16px;
            color: var(--text-secondary);
            background-color: var(--bg-muted);
            border-radius: 6px;
            border: 1px dashed var(--border-color);
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ route('exam-sessions.index') }}" class="brand">
                Sistem Otomatisasi Kartu Ujian Siswa
                <span class="brand-sub">Administrasi Cetak Kartu Panitia Ujian Sekolah</span>
            </a>
            <nav>
                <a href="{{ route('exam-sessions.index') }}" class="nav-link">Daftar Sesi Ujian</a>
            </nav>
        </div>
    </header>

    <main class="container">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Terjadi kesalahan pengisian:</strong>
                <ul style="margin: 6px 0 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>