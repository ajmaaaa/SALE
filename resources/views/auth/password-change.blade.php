<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — SALE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.25rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 440px;
            backdrop-filter: blur(16px);
            box-shadow: 0 24px 48px rgba(0,0,0,.4);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.35);
            color: #fca5a5;
            border-radius: 99px;
            padding: .3rem .75rem;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        h1 {
            color: #f8fafc;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .5rem;
        }

        .sub {
            color: #94a3b8;
            font-size: .875rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .field { margin-bottom: 1.25rem; }

        label {
            display: block;
            color: #cbd5e1;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: .4rem;
            letter-spacing: .03em;
        }

        input[type="password"] {
            width: 100%;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.14);
            border-radius: .6rem;
            padding: .65rem .9rem;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            font-size: .875rem;
            transition: border-color .2s;
            outline: none;
        }

        input[type="password"]:focus {
            border-color: #6366f1;
            background: rgba(99,102,241,.08);
        }

        .error-bag {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: .6rem;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
        }

        .error-bag p {
            color: #fca5a5;
            font-size: .8rem;
            line-height: 1.6;
        }

        .hint {
            color: #64748b;
            font-size: .75rem;
            margin-top: .35rem;
        }

        .hint ul { padding-left: 1rem; margin-top: .25rem; }
        .hint li { margin-bottom: .15rem; }

        .btn {
            width: 100%;
            padding: .8rem;
            border: none;
            border-radius: .7rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
            margin-top: .5rem;
        }

        .btn:hover { opacity: .92; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
    </style>
</head>
<body>
<div class="card">
    <div class="badge">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Keamanan Akun
    </div>

    <h1>Ganti Password Anda</h1>
    <p class="sub">
        Akun Anda menggunakan password sementara yang diberikan oleh admin.
        Harap ganti dengan password baru sebelum melanjutkan.
    </p>

    @if($errors->any())
        <div class="error-bag">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf

        <div class="field">
            <label for="password">Password Baru</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="new-password"
                required
                minlength="12"
                placeholder="Minimal 12 karakter"
            >
            <div class="hint">
                Password harus:
                <ul>
                    <li>Minimal 12 karakter</li>
                    <li>Berbeda dari password sementara</li>
                </ul>
            </div>
        </div>

        <div class="field">
            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                required
                minlength="12"
                placeholder="Ketik ulang password baru"
            >
        </div>

        <button type="submit" class="btn">Simpan Password Baru</button>
    </form>
</div>
</body>
</html>
