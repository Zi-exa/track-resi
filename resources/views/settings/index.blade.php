<x-layout title="Pengaturan">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Konfigurasi sistem</p>
            <h1>Pengaturan</h1>
            <p>Kelola akun, batas waktu per wilayah, dan parameter monitoring retur.</p>
        </div>
    </div>

    <div class="settings-layout">
        <div style="display:flex;flex-direction:column;gap:18px">
            {{-- Akun --}}
            <section class="card" style="padding:22px">
                <div class="card-header" style="margin:-22px -22px 18px;padding:21px 22px 17px"><div><h2>Akun admin</h2><p>Nama, email, dan password login</p></div></div>
                <form action="{{ route('settings.profile') }}" method="POST" class="stack-form">
                    @csrf @method('PUT')
                    <div class="form-grid">
                        <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
                        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required></div>
                    </div>
                    <div class="field"><label for="current_password">Password saat ini <span style="color:var(--muted-2);font-weight:500">— wajib isi untuk simpan perubahan</span></label><input id="current_password" name="current_password" type="password" placeholder="Masukkan password saat ini" required></div>
                    @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="form-grid">
                        <div class="field"><label for="password">Password baru <span style="color:var(--muted-2);font-weight:500">— kosongkan jika tidak diganti</span></label><input id="password" name="password" type="password" placeholder="Minimal 8 karakter"></div>
                        <div class="field"><label for="password_confirmation">Konfirmasi password baru</label><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password baru"></div>
                    </div>
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="form-actions" style="margin-top:6px"><button class="button button-primary" type="submit">Simpan profil</button></div>
                </form>
            </section>

            {{-- Monitoring --}}
            <section class="card" style="padding:22px">
                <div class="card-header" style="margin:-22px -22px 18px;padding:21px 22px 17px"><div><h2>Monitoring</h2><p>Atur ambang batas keterlambatan & tracking stagnan</p></div></div>
                <form action="{{ route('settings.monitoring') }}" method="POST" class="stack-form">
                    @csrf @method('PUT')
                    <div class="form-grid">
                        <div class="field"><label for="stagnant_days">Hari tracking stagnan sebelum Perlu Dilaporkan</label><input id="stagnant_days" name="stagnant_days" type="number" min="1" max="30" value="{{ old('stagnant_days', $settings['stagnant_days']) }}" required><small style="color:var(--muted-2);font-size:10px">Default 3 hari. Diuji di ReturnStatusService sebagai ambang stagnan.</small></div>
                        <div class="field"><label for="late_days">Hari sebelum batas dianggap Terlambat (opsional)</label><input id="late_days" name="late_days" type="number" min="0" max="30" value="{{ old('late_days', $settings['late_days']) }}"></div>
                    </div>
                    <div class="field"><label for="report_days">Batas pelaporan (opsional)</label><input id="report_days" name="report_days" type="number" min="1" max="60" value="{{ old('report_days', $settings['report_days']) }}"></div>
                    @error('stagnant_days')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="form-actions" style="margin-top:6px"><button class="button button-primary" type="submit">Simpan monitoring</button></div>
                </form>
            </section>

            {{-- Sistem --}}
            <section class="card" style="padding:22px">
                <div class="card-header" style="margin:-22px -22px 18px;padding:21px 22px 17px"><div><h2>Sistem</h2><p>Nama toko & format tanggal</p></div></div>
                <form action="{{ route('settings.system') }}" method="POST" class="stack-form">
                    @csrf @method('PUT')
                    <div class="field"><label for="store_name">Nama toko</label><input id="store_name" name="store_name" value="{{ old('store_name', $settings['store_name']) }}" required></div>
                    <div class="field"><label for="date_format">Format tanggal</label>
                        <select id="date_format" name="date_format" required>
                            <option value="d/m/Y" @selected(old('date_format', $settings['date_format']) === 'd/m/Y')>d/m/Y (31/12/2026)</option>
                            <option value="d M Y" @selected(old('date_format', $settings['date_format']) === 'd M Y')>d M Y (31 Dec 2026)</option>
                            <option value="Y-m-d" @selected(old('date_format', $settings['date_format']) === 'Y-m-d')>Y-m-d (2026-12-31)</option>
                            <option value="d F Y" @selected(old('date_format', $settings['date_format']) === 'd F Y')>d F Y (31 December 2026)</option>
                        </select>
                    </div>
                    <div class="form-actions" style="margin-top:6px"><button class="button button-primary" type="submit">Simpan sistem</button></div>
                </form>
            </section>
        </div>

        {{-- Sidebar: Batas waktu per wilayah --}}
        <aside style="display:flex;flex-direction:column;gap:18px">
            <section class="card" style="padding:22px">
                <div class="card-header" style="margin:-22px -22px 18px;padding:21px 22px 17px"><div><h2>Batas waktu wilayah</h2><p>Estimasi hari per wilayah (Bagian 16 PRD)</p></div></div>

                <form action="{{ route('settings.deadlines.store') }}" method="POST" class="stack-form" style="margin-bottom:18px">
                    @csrf
                    <div class="field"><label for="region">Wilayah</label><input id="region" name="region" placeholder="Contoh: Jawa Barat" required></div>
                    <div class="field"><label for="maximum_days">Maksimum hari</label><input id="maximum_days" name="maximum_days" type="number" min="1" max="60" placeholder="7" required></div>
                    @error('region')<p class="field-error">{{ $message }}</p>@enderror
                    @error('maximum_days')<p class="field-error">{{ $message }}</p>@enderror
                    <button class="button button-secondary" type="submit" style="width:100%">+ Tambah wilayah</button>
                </form>

                <div style="border-top:1px solid var(--line);padding-top:14px">
                    @forelse($deadlines as $deadline)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid #eff1f5">
                            <div><strong style="font-size:12px">{{ $deadline->region }}</strong><small style="display:block;color:var(--muted-2);font-size:10px">{{ $deadline->maximum_days }} hari · Default {{ $settings['default_deadline'] }} hari</small></div>
                            <form action="{{ route('settings.deadlines.destroy', $deadline) }}" method="POST" onsubmit="return confirm('Hapus batas waktu untuk {{ $deadline->region }}?')">@csrf @method('DELETE')<button class="icon-button" type="submit" title="Hapus">×</button></form>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:20px 0"><span>!</span><strong>Belum ada batas wilayah</strong><p>Tambahkan minimal Default 10 hari.</p></div>
                    @endforelse
                </div>
                <div class="info-note" style="margin:14px 0 0"><span>i</span><p><strong>Tips:</strong> Atur per wilayah agar status Terlambat/Perlu Diperiksa lebih akurat. Papua/Maluku 14 hari, Jawa 7 hari, dst.</p></div>
            </section>

            <section class="card" style="padding:18px;background:#f1f6fd;border-color:#dbe7fb">
                <h3 style="margin:0 0 8px;font-size:12px">Cara kerja deadline</h3>
                <p style="margin:0;color:#4a6284;font-size:10px;line-height:1.6">Deadline diambil dari wilayah yang terkandung dalam kolom <code>region</code> (logika <code>str_contains</code>). Jika tidak ada yang cocok, fallback ke <code>Default</code>. Kalibrasi ulang setelah 4–6 minggu data scan riil terkumpul.</p>
            </section>
        </aside>
    </div>
</x-layout>
