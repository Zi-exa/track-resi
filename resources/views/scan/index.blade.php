<x-layout title="Scan Paket">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Penerimaan gudang</p>
            <h1>Scan paket retur</h1>
            <p>Arahkan scanner ke barcode atau masukkan nomor resi secara manual.</p>
        </div>
    </div>

    <section class="card scan-card">
        <form action="{{ route('scan.lookup') }}" method="POST" class="scan-form" data-scan-form>
            @csrf
            <label for="tracking_number">Nomor resi</label>
            <div class="scan-input-row">
                <input id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}" placeholder="Contoh: JX123456789" autofocus autocomplete="off" required>
                <button class="button button-primary" type="submit">Cari paket</button>
            </div>
            @error('tracking_number') <p class="field-error">{{ $message }}</p> @enderror
        </form>
        <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
                <div><strong style="font-size:12px">Scan dengan kamera HP</strong><p style="margin:3px 0 0;color:var(--muted);font-size:11px">Tanpa scanner fisik — pakai kamera belakang HP</p></div>
                <button class="button button-secondary button-small" type="button" id="toggleCamera">📷 Buka kamera</button>
            </div>
            <div id="qr-reader" style="display:none;overflow:hidden;border-radius:12px;border:1px solid var(--line)"></div>
            <p id="qr-error" style="display:none;margin:10px 0 0;color:var(--red);font-size:11px"></p>
            <p style="margin:10px 0 0;color:var(--muted-2);font-size:10px;line-height:1.5">Butuh HTTPS di HP (kecuali <code>localhost</code>). Kalau buka via <code>192.168.x.x:8000</code> dan kamera diblok, pakai <code>ngrok http 8000</code> atau buka di HP via <code>chrome://flags#unsafely-treat-insecure-origin-as-secure</code>.</p>
        </div>
    </section>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
    (() => {
        const btn = document.getElementById('toggleCamera');
        const readerEl = document.getElementById('qr-reader');
        const errEl = document.getElementById('qr-error');
        const input = document.getElementById('tracking_number');
        const form = document.querySelector('[data-scan-form]');
        let html5QrCode = null;
        let running = false;

        async function onScanSuccess(decodedText) {
            const code = decodedText.trim();
            if (!code) return;
            // QR J&T kadang ada prefix/suffix, ambil yang mirip JX/JY + angka
            input.value = code;
            errEl.style.display = 'none';
            // Hentikan kamera sejenak biar nggak double scan
            if (running && html5QrCode) {
                try { await html5QrCode.stop(); running = false; readerEl.style.display = 'none'; btn.textContent = '📷 Buka kamera'; } catch(e) {}
            }
            form.requestSubmit();
        }

        btn?.addEventListener('click', async () => {
            errEl.style.display = 'none';
            if (running) {
                try { await html5QrCode.stop(); } catch(e) {}
                running = false;
                readerEl.style.display = 'none';
                btn.textContent = '📷 Buka kamera';
                return;
            }
            if (!window.Html5Qrcode) { errEl.textContent = 'Gagal load html5-qrcode. Cek koneksi.'; errEl.style.display = 'block'; return; }
            readerEl.style.display = 'block';
            html5QrCode = new Html5Qrcode('qr-reader');
            const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0, disableFlip: false };
            try {
                await html5QrCode.start({ facingMode: 'environment' }, config, onScanSuccess, () => {});
                running = true;
                btn.textContent = '✕ Tutup kamera';
            } catch (err) {
                errEl.textContent = 'Gagal buka kamera: ' + (err?.message || err) + '. Pastikan izin kamera & HTTPS.';
                errEl.style.display = 'block';
                readerEl.style.display = 'none';
            }
        });
    })();
    </script>

    @if ($found)
        <section class="card result-card result-success">
            <span class="result-kicker">Paket ditemukan</span>
            <h2>{{ $found->tracking_number }}</h2>
            <dl class="detail-grid">
                <div><dt>Order ID</dt><dd>{{ $found->order->order_number }}</dd></div>
                <div><dt>Produk</dt><dd>{{ $found->order->product_name }}</dd></div>
                <div><dt>Variasi</dt><dd>{{ $found->order->variation ?: '—' }}</dd></div>
                <div><dt>Jalur retur</dt><dd>{{ $found->source_label }}</dd></div>
            </dl>
            <form action="{{ route('scan.confirm', $found) }}" method="POST">@csrf
                <button class="button button-success" type="submit">Konfirmasi diterima</button>
            </form>
        </section>
    @endif

    @if (session('scan_missing'))
        <section class="card result-card result-warning">
            <span class="result-kicker">Resi tidak ditemukan</span>
            <h2>{{ session('scan_missing') }}</h2>
            <p>Periksa kembali nomor resi. Jika paket memang ada secara fisik, catat sebagai paket tidak dikenali.</p>
            <div class="button-row">
                <a class="button button-secondary" href="{{ route('scan.index') }}">Scan ulang</a>
                <form action="{{ route('scan.unknown') }}" method="POST">@csrf
                    <input type="hidden" name="tracking_number" value="{{ session('scan_missing') }}">
                    <button class="button button-danger" type="submit">Catat paket</button>
                </form>
            </div>
        </section>
    @endif
</x-layout>
