<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · ReturnTrack</title>
    @fonts('instrument-sans')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-brand-panel">
            <div class="login-brand"><span class="brand-mark brand-mark-large">R</span><span><strong>ReturnTrack</strong><small>EMZA STORE</small></span></div>
            <div class="login-message">
                <span class="eyebrow eyebrow-light">Kontrol retur tanpa tebak-tebakan</span>
                <h1>Setiap paket kembali, tercatat dengan pasti.</h1>
                <p>Import data TikTok Shop, pantau paket terlambat, dan verifikasi paket yang tiba dari satu dashboard.</p>
            </div>
            <div class="login-stat"><span>●</span> Sistem siap digunakan</div>
        </section>

        <section class="login-form-panel">
            <div class="login-form-wrap">
                <p class="eyebrow">Area admin</p>
                <h2>Selamat datang kembali</h2>
                <p class="muted">Masuk untuk melanjutkan monitoring retur Emza Store.</p>

                <form action="{{ route('login.store') }}" method="POST" class="stack-form">
                    @csrf
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="admin@emzastore.id" autofocus autocomplete="email" required>
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        @error('password') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <label class="check-row"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
                    <button class="button button-primary button-wide" type="submit">Masuk ke ReturnTrack <span>→</span></button>
                </form>
                <p class="login-footnote">Akses khusus admin & pemilik toko.</p>
            </div>
        </section>
    </main>
</body>
</html>
