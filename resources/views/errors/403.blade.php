<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Smash Arena</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=barlow-condensed:700,800,900|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --arena-base: #0A0F1D;
            --arena-card: #111A2E;
            --volt: #CCFF00;
            --volt-contrast: #0A0F1D;
            --court-orange: #FF5500;
            --court-slate-400: #94A3B8;
            --court-slate-50: #F8FAFC;
        }
        body {
            background-color: var(--arena-base);
            color: var(--court-slate-50);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            padding: 2rem;
            max-width: 600px;
        }
        .error-code {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 140px;
            font-weight: 900;
            line-height: 1;
            margin: 0 0 1rem 0;
            letter-spacing: 0.05em;
        }
        .error-code.volt { color: var(--volt); }
        .error-code.orange { color: var(--court-orange); }
        .error-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 0 1rem 0;
            letter-spacing: 0.025em;
        }
        .error-desc {
            color: var(--court-slate-400);
            font-size: 1.125rem;
            line-height: 1.6;
            margin: 0 0 2rem 0;
        }
        .btn {
            display: inline-block;
            background-color: var(--volt);
            color: var(--volt-contrast);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            text-transform: uppercase;
            text-decoration: none;
            padding: 0.75rem 2rem;
            transform: skewX(-3deg);
            transition: all 0.2s ease;
        }
        .btn:hover {
            opacity: 0.9;
            transform: skewX(-3deg) translateY(-2px);
        }
        .btn-inner {
            display: block;
            transform: skewX(3deg);
        }
        .decoration {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 640px) {
            .error-code { font-size: 100px; }
            .error-title { font-size: 2rem; }
            .error-desc { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="decoration">⛔</div>
        <h1 class="error-code orange">403</h1>
        <h2 class="error-title">Akses Ditolak</h2>
        <p class="error-desc">Anda tidak memiliki izin untuk mengakses area ini. Hubungi admin gelanggang jika ini adalah kesalahan.</p>
        <a href="/" class="btn">
            <span class="btn-inner">Kembali</span>
        </a>
    </div>
</body>
</html>
