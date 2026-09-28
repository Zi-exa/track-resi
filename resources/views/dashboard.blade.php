<x-layout title="Dashboard">
    @php
        $rate = $metrics['receive_rate'] ?? 0;
        $unfinished = ($metrics['belum_diterima'] ?? 0) + ($metrics['terlambat'] ?? 0) + ($metrics['perlu_diperiksa'] ?? 0) + ($metrics['perlu_dilaporkan'] ?? 0);
        $attention = $metrics['attention'] ?? 0;
        $focusCount = $attention + ($metrics['dalam_investigasi'] ?? 0);
    @endphp

    {{-- HERO --}}
    <section class="dash-hero">
        <div class="dash-hero-bg" aria-hidden="true"></div>
        <div class="dash-hero-main">
            <h1>Semua retur terkendali dalam satu layar.</h1>
            <p class="dash-hero-sub">Pantau paket masuk real-time, kejar status tertunda, dan selesaikan claim kurir tanpa hambatan.</p>
            <div class="dash-hero-meta">
                <div><span>Diterima Hari Ini</span><strong>{{ number_format($metrics['today_received']) }}</strong></div>
                <div><span>Retur 7 Hari</span><strong>{{ number_format($metrics['week_returns']) }}</strong></div>
                <div><span>Butuh Tindakan</span><strong class="amber">{{ number_format($attention) }} <small style="font-size:12px;font-weight:500">paket</small></strong></div>
            </div>
            <div class="heading-actions dash-hero-actions">
                <a class="button button-hero-primary" href="{{ route('scan.index') }}">＋ Scan Paket Masuk</a>
                <a class="button button-hero-ghost" href="{{ route('imports.index') }}">⇧ Import TikTok</a>
                <a class="button button-hero-ghost" href="{{ route('reports.index') }}">▥ Laporan</a>
            </div>
        </div>
    </section>

    {{-- KPI --}}
    <section class="kpi-grid" aria-label="Indikator utama">
        <a class="kpi-card" href="{{ route('returns.index') }}">
            <div class="kpi-top"><span class="kpi-label">Total Retur Aktif</span><span class="kpi-ico">↩</span></div>
            <strong>{{ number_format($metrics['total']) }} <small>paket total</small></strong>
            <span class="kpi-foot {{ $unfinished > 0 ? 'kpi-foot-amber' : '' }}">{{ $unfinished > 0 ? number_format($unfinished).' belum selesai penanganan' : 'Semua paket selesai' }}</span>
        </a>
        <a class="kpi-card" href="{{ route('returns.index', ['status' => 'sudah_diterima']) }}">
            <div class="kpi-top"><span class="kpi-label">Sudah Diterima Fisik</span><span class="kpi-ico kpi-ico-green">✓</span></div>
            <strong>{{ number_format($metrics['sudah_diterima']) }}</strong>
            <span class="kpi-foot"><span class="kpi-pill">{{ $rate }}% tuntas</span></span>
            <span class="kpi-foot">{{ $metrics['sudah_diterima'] > 0 ? 'Paket sudah tiba di gudang' : 'Belum ada paket tiba di gudang' }}</span>
        </a>
        <a class="kpi-card" href="{{ route('returns.index', ['status' => 'belum_diterima']) }}">
            <div class="kpi-top"><span class="kpi-label">Dalam Pengiriman</span><span class="kpi-pill">On Track</span></div>
            <strong>{{ number_format($metrics['belum_diterima']) }} <small>paket</small></strong>
            <span class="kpi-foot">Dalam batas estimasi normal kurir</span>
        </a>
        <a class="kpi-card {{ $attention > 0 ? 'kpi-alert' : '' }}" href="{{ route('inspections.index') }}">
            <div class="kpi-top"><span class="kpi-label">Perlu Penanganan Cepat</span>@if($attention > 0)<span class="kpi-pill kpi-pill-red">Kritis</span>@endif</div>
            <strong>{{ number_format($attention) }} <small>paket tertunda</small></strong>
            <span class="kpi-foot">{{ number_format($metrics['terlambat']) }} terlambat • {{ number_format($metrics['perlu_diperiksa']) }} perlu periksa</span>
        </a>
    </section>

    {{-- STATUS PILLS --}}
    <section class="pill-row" aria-label="Rincian status">
        <span class="pill-caption">Status Rincian:</span>
        <a class="pill pill-amber" href="{{ route('returns.index', ['status' => 'terlambat']) }}"><i></i>Terlambat: <strong>{{ number_format($metrics['terlambat']) }}</strong></a>
        <a class="pill pill-orange" href="{{ route('inspections.index') }}"><i></i>Perlu Diperiksa: <strong>{{ number_format($metrics['perlu_diperiksa']) }}</strong></a>
        <a class="pill pill-slate" href="{{ route('returns.index', ['status' => 'dalam_investigasi']) }}"><i></i>Investigasi: <strong>{{ number_format($metrics['dalam_investigasi']) }}</strong></a>
        <a class="pill pill-slate" href="{{ route('returns.index', ['status' => 'hilang']) }}"><i></i>Hilang: <strong>{{ number_format($metrics['hilang']) }}</strong></a>
        <span class="pill-hint">Klik badge status untuk menyaring tabel langsung</span>
    </section>

    <div class="dash-main">
        <div class="dash-left">
            {{-- TREN --}}
            <section class="card trend-card">
                <div class="card-header">
                    <div><h2>Tren 14 Hari Terakhir</h2><p>Perbandingan paket masuk vs paket diterima per hari</p></div>
                    <div class="legend"><span><i class="lg-blue"></i>Masuk</span><span><i class="lg-green"></i>Diterima</span></div>
                </div>
                <div class="trend-chart">
                    @foreach ($trend as $row)
                        @php $isToday = \Carbon\Carbon::parse($row['date'])->isToday(); @endphp
                        <div class="trend-col {{ $isToday ? 'today' : '' }}" title="{{ $row['label'] }} — {{ $row['masuk'] }} masuk, {{ $row['diterima'] }} diterima">
                            <div class="trend-bars">
                                <div class="trend-bar-stack"><span class="trend-bar trend-masuk" style="height: {{ max(4, $row['masuk_pct']) }}%"></span></div>
                                <div class="trend-bar-stack"><span class="trend-bar trend-diterima" style="height: {{ max(4, $row['diterima_pct']) }}%"></span></div>
                            </div>
                            <small>{{ \Carbon\Carbon::parse($row['date'])->format('d') }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="trend-foot">
                    <span>Maksimal: {{ $maxTrend }} paket / hari</span>
                    <span>{{ number_format($metrics['week_received']) }} diterima dalam 7 hari terakhir</span>
                </div>
            </section>

            {{-- PRIORITAS --}}
            <section class="card table-card">
                <div class="card-header">
                    <div><h2>Prioritas Penanganan @if($urgent->count() > 0)<span class="kpi-pill kpi-pill-red" style="margin-left:6px">{{ $urgent->count() }} Perlu Respon</span>@endif</h2><p>Selesaikan urutan dari umur retur paling lama</p></div>
                    <a class="text-link" href="{{ route('inspections.index') }}">Buka inspeksi →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Resi / Kurir</th><th>Produk</th><th>Umur</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
                        <tbody>
                        @forelse ($urgent as $return)
                            @php $age = $return->age_in_days ?? 0; @endphp
                            <tr class="{{ $return->status->value === 'perlu_dilaporkan' ? 'row-urgent' : '' }}">
                                <td>
                                    <a class="tracking-link" href="{{ route('returns.show', $return) }}">{{ $return->tracking_number ?: 'Tanpa resi' }}</a>
                                    <small>{{ $return->courier ?: '—' }} • {{ $return->order->order_number ?? '—' }}</small>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($return->order->product_name ?? '—', 26) }}<small>{{ $return->return_date->translatedFormat('d M Y') }} • {{ $return->source_label }}</small></td>
                                <td><span class="age-pill {{ $age > 10 ? 'age-bad' : ($age > 7 ? 'age-warn' : 'age-ok') }}">{{ $age }} hr</span></td>
                                <td><span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span></td>
                                <td style="text-align:right"><a class="button button-secondary button-small" href="{{ route('returns.show', $return) }}">Proses</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state empty-inline"><span>✓</span><strong>Semua aman</strong><p>Tidak ada paket mendesak saat ini.</p></div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- RETUR TERBARU --}}
            <section class="card table-card">
                <div class="card-header">
                    <div><h2>Retur Terbaru</h2><p>Aktivitas paket yang tercatat dari semua saluran</p></div>
                    <a class="text-link" href="{{ route('returns.index') }}">Lihat semua →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nomor Resi</th><th>Produk</th><th>Tanggal</th><th>Status Akhir</th></tr></thead>
                        <tbody>
                        @forelse ($latest as $return)
                            <tr>
                                <td>
                                    @if($return->tracking_number)
                                        <a class="tracking-link" href="{{ route('returns.show', $return) }}">{{ $return->tracking_number }}</a>
                                    @else
                                        <span style="color:var(--muted-2);font-style:italic">Tanpa resi fisik</span>
                                    @endif
                                    <small>{{ $return->order->order_number ?? '' }}</small>
                                </td>
                                <td>{{ $return->order->product_name ?? '—' }}@if($return->order->variation)<small>({{ $return->order->variation }})</small>@endif</td>
                                <td>{{ $return->return_date->translatedFormat('d M Y') }}</td>
                                <td><span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state"><span>↩</span><strong>Belum ada data retur</strong><p>Mulai dengan mengimpor file dari TikTok Shop.</p><a class="button button-primary button-small" href="{{ route('imports.index') }}">Import data</a></div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="dash-right">
            {{-- FOKUS --}}
            <section class="card focus-card">
                <div class="card-header"><div><h2>Fokus Hari Ini</h2><p>Daftar tindakan yang wajib diselesaikan</p></div><span class="focus-count">{{ $focusCount }}</span></div>
                <div class="focus-list">
                    <a href="{{ route('inspections.index') }}" class="focus-item">
                        <span class="focus-ico focus-amber">!</span>
                        <div><strong>{{ number_format($metrics['perlu_diperiksa']) }} Pemeriksaan resi</strong><p>Cek tracking yang stagnan</p></div>
                        <span class="focus-go">›</span>
                    </a>
                    <a href="{{ route('returns.index', ['status' => 'perlu_dilaporkan']) }}" class="focus-item">
                        <span class="focus-ico focus-blue">▥</span>
                        <div><strong>{{ number_format($metrics['perlu_dilaporkan']) }} Klaim siap dibuat</strong><p>Siapkan tiket ganti rugi kurir</p></div>
                        <span class="focus-go">›</span>
                    </a>
                    <a href="{{ route('returns.index', ['status' => 'dalam_investigasi']) }}" class="focus-item">
                        <span class="focus-ico focus-blue">⌕</span>
                        <div><strong>{{ number_format($metrics['dalam_investigasi']) }} Investigasi berjalan</strong><p>Follow-up hasil dari kurir</p></div>
                        <span class="focus-go">›</span>
                    </a>
                </div>
                <a class="button button-primary button-wide" href="{{ route('scan.index') }}"><span>Scan Paket Berikutnya</span><span>→</span></a>
                @if (($metrics['tanpa_fisik'] ?? 0) > 0)
                    <div class="info-note"><span>i</span><p><strong>{{ $metrics['tanpa_fisik'] }} Refund</strong> diselesaikan tanpa retur fisik (otomatis disaring dari radar fisik).</p></div>
                @endif
            </section>

            {{-- DISTRIBUSI --}}
            <section class="card mini-card">
                <div class="card-header"><div><h2>Distribusi Jalur Retur</h2><p>Asal paket &amp; beban pengiriman</p></div></div>
                <div class="mini-body">
                    @foreach ($sources as $s)
                        <div class="source-row">
                            <div class="source-head"><strong>{{ $s['label'] }}</strong><span>{{ number_format($s['count']) }} <small>({{ $s['pct'] }}%)</small></span></div>
                            <div class="bar"><span class="bar-{{ $s['tone'] }}" style="width: {{ $s['pct'] }}%"></span></div>
                            <small>{{ $s['desc'] }}</small>
                        </div>
                    @endforeach
                    <div style="border-top:1px solid var(--line-soft);padding-top:12px">
                        <div class="source-head"><strong style="font-size:12px">Ekspedisi Teratas</strong><span class="text-link" style="font-size:12px">100% Volume</span></div>
                        @forelse ($topCouriers as $c)
                            <div class="courier-chip" style="margin-top:8px">
                                <div><span class="courier-mark">J&amp;T</span><strong>{{ $c->courier }}</strong></div>
                                <strong>{{ number_format($c->aggregate) }} Paket</strong>
                            </div>
                        @empty
                            <p class="muted-small">Belum ada data kurir.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </aside>
    </div>
</x-layout>
