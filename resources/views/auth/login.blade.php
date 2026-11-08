<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin AMIRA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #020617;
            border-radius: 16px;
            border: 1px solid #1f2937;
            box-shadow: 0 20px 40px rgba(0,0,0,0.7);
            padding: 24px 24px 20px;
            width: 100%;
            max-width: 380px;
        }

        .login-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .login-subtitle {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            padding: 8px 10px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .error {
            color: #fca5a5;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .alert {
            background: #1e293b;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .remember-row input[type="checkbox"] {
            margin: 0;
        }

        button {
            width: 100%;
            border: none;
            border-radius: 9999px;
            padding: 9px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #22c55e;
            color: #020617;
        }

        button:disabled {
            opacity: 0.6;
        }

        .footer-note {
            margin-top: 14px;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-card {
                margin: 0 12px;
            }
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-title">Login Admin AMIRA</div>
    <div class="login-subtitle">
        Khusus untuk guru/pengelola yang akan mengatur sistem prompt dan resource AMIRA.
    </div>

    @if (session('error'))
        <div class="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div>
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
            >
        </div>

        <div>
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <div class="remember-row">
            <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember" style="margin-bottom:0;">Ingat saya di perangkat ini</label>
        </div>

        <button type="submit">Masuk</button>
    </form>

    <div class="footer-note">
        Default admin: admin@smkn4tpi.sch.id / password  
        (ubah password setelah login pertama)
    </div>
</div>
</body>
</html>
