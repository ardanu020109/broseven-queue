<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') @yield('title') | {{ config('app.name') }}</title>
    <style>
        :root { --primary: #F6EB61; --accent: #F9C74F; --dark: #6D6D6D; --bg: #F9F9F9; --text: #292929; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem;
            background: var(--bg); color: var(--text);
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
        .error-page { width: 100%; max-width: 520px; padding: 2.5rem 2rem; text-align: center; background: #fff;
            border: 1px solid #EAEAEA; border-radius: 20px; box-shadow: 0 8px 24px rgba(0, 0, 0, .08); }
        .error-code { font-size: 4.5rem; font-weight: 800; line-height: 1; color: var(--dark);
            background: linear-gradient(transparent 62%, var(--primary) 62%); display: inline-block; padding: 0 .2em; }
        h1 { margin: 1rem 0 .5rem; font-size: 1.5rem; }
        p { margin: 0 0 1.6rem; color: var(--dark); line-height: 1.6; }
        .actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; }
        .btn { display: inline-block; padding: .7rem 1.2rem; border-radius: 10px; border: 1px solid #EAEAEA;
            background: #fff; color: var(--text); font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn.primary { background: var(--primary); border-color: var(--accent); }
    </style>
</head>
<body>
    <main class="error-page">
        <div class="error-code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn primary">Kembali ke Beranda</a>
            <button type="button" class="btn" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Halaman Sebelumnya</button>
        </div>
    </main>
</body>
</html>
