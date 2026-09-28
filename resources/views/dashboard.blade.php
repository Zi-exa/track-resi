<x-layout title="Dashboard">
    @php
        $rate = $metrics['receive_rate'] ?? 0;
        $ringC = 2 * pi() * 54;
        $ringOff = $ringC * (1 - min(100, max(0, $rate)) / 100);
        $healthLabel = $rate >= 90 ? 'Sehat' : ($rate >= 70 ? 'Stabil' : ($rate >= 40 ? 'Perlu perhatian' : 'Kritis'));
        $healthTone = $rate >= 90 ? 'good' : ($rate >= 70 ? 'ok' : ($rate >= 40 ? 'warn' : 'bad'));
    @endphp

    {{-- HERO --}}
    <section class="dash-hero">
        <div class="dash-hero-bg" aria-hidden="true"></div>
        <div class="dash-hero-main">
            <p class="eyebrow eyebrow-light">Emza Store · Live monitoring · {{ now()->translatedFormat('l, d F Y') }}</p>
            <h1>Semua retur terkendali<br>dalam satu layar.</h1>
            <p class="dash-hero-sub">Pantau paket masuk, kejar yang terlambat, dan selesaikan laporan kurir tanpa pindah menu.</p>
            <div class="dash-hero-stats">
                <div><strong>{{ number_format($metrics['today_received']) }}</strong><span>diterima hari ini</span></div>
                <div><strong>{{ number_format($metrics['week_returns']) }}</strong><span>retur 7 hari terakhir</span></div>
                <div><strong>{{ $metrics['avg_days'] !== null ? $metrics['avg_days'].' hr' : '—' }}</strong><span>rata-rata sampai</span></div>
                <div><strong>{{ number_format($metrics['attention']) }}</strong><span>butuh tindakan</span></div>
            </div>
            <div class="heading-actions dash-hero-actions">
                <a class="button button-hero-primary" href="{{ route('scan.index') }}">⌗ Scan paket masuk</a>
                <a class="button button-hero-ghost" href="/imports">⇧ Import TikTok Shop</a>
                <a class="button button-hero-ghost" href="/reports">▥ Laporan</a>
            </div>
        </div>
        <div class="dash-hero-side">
            <div class="health-card">
                <div class="health-ring-wrap">
                    <svg class="health-ring" viewBox="0 0 130 130" role="img" aria-label="Tingkat penerimaan {{ $rate }} persen">
                        <circle cx="65" cy="65" r="54" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="11"/>
                        <circle cx="65" cy="65" r="54" fill="none" stroke="#fff" stroke-width="11" stroke-linecap="round"
                            stroke-dasharray="{{ $ringC }}" stroke-dashoffset="{{ $ringOff }}"
                            transform="rotate(-90 65 65)"/>
                    </svg>
                    <div class="health-ring-center"><strong>{{ $rate }}%</strong><span>diterima</span></div>
                </div>
                <div class="health-copy">
                    <span class="health-pill health-{{ $healthTone }}">{{ $healthLabel }}</span>
                    <strong>{{ number_format($metrics['sudah_diterima']) }} dari {{ number_format($metrics['total']) }} paket</strong>
                    <p>sudah kembali ke gudang. Sisa {{ number_format($metrics['total'] - $metrics['sudah_diterima']) }} masih dalam perjalanan / penanganan.</p>
                </div>
                <div class="health-bar"><span style="width: {{ min(100, max(0, $rate)) }}%"></span></div>
            </div>
        </div>
    </section>

    {{-- KPI UTAMA --}}
    <section class="kpi-grid" aria-label="Indikator utama">
        <a class="kpi-card kpi-dark" href="/returns">
            <div class="kpi-top"><span class="kpi-icon">↩</span><span class="kpi-pill">Retur fisik · +{{ $metrics['week_returns'] }} / 7hr</span></div>
            <strong>{{ number_format($metrics['total']) }}</strong>
            <span class="kpi-label">Total Retur</span>
            <span class="kpi-foot">{{ number_format($metrics['belum_diterima'] + $metrics['terlambat'] + $metrics['perlu_diperiksa'] + $metrics['perlu_dilaporkan']) }} belum selesai</span>
        </a>
        <a class="kpi-card" href="/returns?status=sudah_diterima">
            <div class="kpi-top"><span class="kpi-icon kpi-green">✓</span><span class="kpi-pill kpi-pill-green">{{ $rate }}% tuntas</span></div>
            <strong>{{ number_format($metrics['sudah_diterima']) }}</strong>
            <span class="kpi-label">Sudah Diterima</span>
            <div class="kpi-progress"><span class="kpi-progress-green" style="width: {{ min(100, max(0, $rate)) }}%"></span></div>
        </a>
        <a class="kpi-card" href="/returns?status=belum_diterima">
            <div class="kpi-top"><span class="kpi-icon kpi-blue">⌁</span><span class="kpi-pill">On track</span></div>
            <strong>{{ number_format($metrics['belum_diterima']) }}</strong>
            <span class="kpi-label">Belum Diterima</span>
            <span class="kpi-foot">Dalam batas waktu normal</span>
        </a>
        <a class="kpi-card {{ $metrics['attention'] > 0 ? 'kpi-alert' : '' }}" href="/inspections">
            <div class="kpi-top"><span class="kpi-icon kpi-red">⚑</span><span class="kpi-pill {{ $metrics['attention'] > 0 ? 'kpi-pill-red' : '' }}">{{ $metrics['attention'] > 0 ? 'Butuh aksi sekarang' : 'Aman' }}</span></div>
            <strong>{{ number_format($metrics['attention']) }}</strong>
            <span class="kpi-label">Terlambat + Perlu Tindakan</span>
            <span class="kpi-foot">{{ number_format($metrics['terlambat']) }} terlambat · {{ number_format($metrics['perlu_diperiksa']) }} periksa · {{ number_format($metrics['perlu_dilaporkan']) }} lapor</span>
        </a>
    </section>

    {{-- STRIP STATUS SEKUNDER --}}
    <section class="status-strip" aria-label="Status detail">
        <a href="/returns?status=terlambat"><span class="status-dot dot-amber">◷</span><div><strong>{{ number_format($metrics['terlambat']) }}</strong><span>Terlambat</span></div></a>
        <a href="/inspections"><span class="status-dot dot-orange">!</span><div><strong>{{ number_format($metrics['perlu_diperiksa']) }}</strong><span>Perlu Diperiksa</span></div></a>
        <a href="/returns?status=perlu_dilaporkan"><span class="status-dot dot-red">⚑</span><div><strong>{{ number_format($metrics['perlu_dilaporkan']) }}</strong><span>Perlu Dilaporkan</span></div></a>
        <a href="/returns?status=dalam_investigasi"><span class="status-dot dot-purple">⌕</span><div><strong>{{ number_format($metrics['dalam_investigasi']) }}</strong><span>Investigasi</span></div></a>
        <a href="/returns?status=hilang"><span class="status-dot dot-dark">×</span><div><strong>{{ number_format($metrics['hilang']) }}</strong><span>Hilang</span></div></a>
        <a href="/returns?status=tidak_dikenali"><span class="status-dot dot-grey">?</span><div><strong>{{ number_format($metrics['tidak_dikenali']) }}</strong><span>Tidak Dikenali</span></div></a>
    </section>

    <div class="dash-main">
        <div class="dash-left">
            {{-- TREN --}}
            <section class="card trend-card">
                <div class="card-header">
                    <div><h2>Tren 14 hari terakhir</h2><p>Retur masuk vs paket diterima per hari</p></div>
                    <div class="legend"><span><i class="lg-blue"></i>Masuk</span><span><i class="lg-green"></i>Diterima</span></div>
                </div>
                <div class="trend-chart">
                    @foreach ($trend as $row)
                        <div class="trend-col" title="{{ $row['label'] }} — {{ $row['masuk'] }} masuk, {{ $row['diterima'] }} diterima">
                            <div class="trend-bars">
                                <div class="trend-bar-stack">
                                    <span class="trend-bar trend-masuk" style="height: {{ max(4, $row['masuk_pct']) }}%"></span>
                                </div>
                                <div class="trend-bar-stack">
                                    <span class="trend-bar trend-diterima" style="height: {{ max(4, $row['diterima_pct']) }}%"></span>
                                </div>
                            </div>
                            <small>{{ \Carbon\Carbon::parse($row['date'])->format('d') }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="trend-foot">
                    <span>Maks {{ $maxTrend }} paket/hari</span>
                    <span>{{ number_format($metrics['week_received']) }} diterima dalam 7 hari</span>
                </div>
            </section>

            {{-- PAKET MENDESAK --}}
            <section class="card table-card">
                <div class="card-header">
                    <div><h2>Prioritas penanganan</h2><p>Paket tertua yang belum selesai — tangani dari atas</p></div>
                    <a class="text-link" href="/inspections">Buka inspeksi →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Resi / Order</th><th>Produk</th><th>Umur</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($urgent as $return)
                            @php $age = $return->age_in_days ?? 0; @endphp
                            <tr class="{{ $return->status->value === 'perlu_dilaporkan' ? 'row-urgent' : '' }}">
                                <td>
                                    <a class="tracking-link" href="/returns/{{ $return->id }}">{{ $return->tracking_number ?: 'Tanpa resi' }}</a>
                                    <small>{{ $return->order->order_number ?? '—' }} · {{ $return->courier ?: 'Kurir —' }}</small>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($return->order->product_name ?? '—', 28) }}<small>{{ $return->return_date->translatedFormat('d M Y') }} · {{ $return->source_label }}</small></td>
                                <td><span class="age-pill {{ $age > 10 ? 'age-bad' : ($age > 7 ? 'age-warn' : 'age-ok') }}">{{ $age }} hr</span></td>
                                <td><span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span></td>
                                <td><a class="row-arrow" href="/returns/{{ $return->id }}">›</a></td>
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
                    <div><h2>Retur terbaru</h2><p>8 data terakhir dari semua jalur</p></div>
                    <a class="text-link" href="/returns">Lihat semua →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nomor resi</th><th>Produk</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($latest as $return)
                            <tr>
                                <td><a class="tracking-link" href="/returns/{{ $return->id }}">{{ $return->tracking_number ?: 'Tanpa resi' }}</a><small>{{ $return->order->order_number ?? '' }}</small></td>
                                <td>{{ \Illuminate\Support\Str::limit($return->order->product_name ?? '—', 26) }}<small>{{ $return->order->variation ?? '' }}</small></td>
                                <td>{{ $return->return_date->translatedFormat('d M Y') }}<small>{{ $return->source_label }}</small></td>
                                <td><span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span></td>
                                <td><a class="row-arrow" href="/returns/{{ $return->id }}">›</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><span>↩</span><strong>Belum ada data retur</strong><p>Mulai dengan mengimpor file dari TikTok Shop.</p><a class="button button-primary button-small" href="/imports">Import data</a></div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="dash-right">
            <section class="card focus-card">
                <div class="card-header"><div><h2>Fokus hari ini</h2><p>3 antrian wajib selesai</p></div><span class="focus-count">{{ $metrics['attention'] + $metrics['dalam_investigasi'] }}</span></div>
                <div class="focus-list">
                    <a href="/inspections" class="focus-item">
                        <span class="attention-dot dot-orange">!</span>
                        <div><strong>{{ number_format($metrics['perlu_diperiksa']) }} pemeriksaan</strong><p>Cek tracking yang stagnan</p></div>
                        <span class="focus-go">›</span>
                    </a>
                    <a href="/returns?status=perlu_dilaporkan" class="focus-item">
                        <span class="attention-dot dot-red">⚑</span>
                        <div><strong>{{ number_format($metrics['perlu_dilaporkan']) }} siap dilapor</strong><p>Buat tiket ke kurir</p></div>
                        <span class="focus-go">›</span>
                    </a>
                    <a href="/returns?status=dalam_investigasi" class="focus-item">
                        <span class="attention-dot dot-purple">⌕</span>
                        <div><strong>{{ number_format($metrics['dalam_investigasi']) }} investigasi</strong><p>Follow-up hasil kurir</p></div>
                        <span class="focus-go">›</span>
                    </a>
                </div>
                <a class="button button-primary button-wide" href="{{ route('scan.index') }}"><span>⌗ Scan paket berikutnya</span><span>→</span></a>
                @if (($metrics['tanpa_fisik'] ?? 0) > 0)
                    <div class="info-note"><span>i</span><p><strong>{{ $metrics['tanpa_fisik'] }} refund</strong> selesai tanpa retur fisik, disembunyikan dari monitoring.</p></div>
                @endif
            </section>

            <section class="card mini-card">
                <div class="card-header"><div><h2>Jalur retur</h2><p>Asal paket fisik</p></div></div>
                <div class="mini-body">
                    @foreach ($sources as $s)
                        <div class="source-row">
                            <div class="source-head"><strong>{{ $s['label'] }}</strong><span>{{ number_format($s['count']) }} · {{ $s['pct'] }}%</span></div>
                            <div class="bar"><span class="bar-{{ $s['tone'] }}" style="width: {{ $s['pct'] }}%"></span></div>
                            <small>{{ $s['desc'] }}</small>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card mini-card">
                <div class="card-header"><div><h2>Kurir teratas</h2><p>Beban retur per ekspedisi</p></div></div>
                <div class="mini-body">
                    @forelse ($topCouriers as $c)
                        <div class="source-row">
                            <div class="source-head"><strong>{{ $c->courier }}</strong><span>{{ number_format($c->aggregate) }}</span></div>
                            <div class="bar"><span class="bar-blue" style="width: {{ $maxCourier > 0 ? round(($c->aggregate / $maxCourier) * 100) : 0 }}%"></span></div>
                        </div>
                    @empty
                        <p class="muted-small">Belum ada data kurir.</p>
                    @endforelse
                </div>
            </section>

            <section class="card mini-card">
                <div class="card-header"><div><h2>Komposisi status</h2><p>100% paket fisik</p></div></div>
                <div class="mini-body distro-list">
                    @foreach ($distribution as $d)
                        @php $pct = $metrics['total'] > 0 ? round(($d['count'] / $metrics['total']) * 100, 1) : 0; @endphp
                        <div class="distro-row"><span class="distro-dot distro-{{ $d['tone'] }}"></span><span class="distro-label">{{ $d['label'] }}</span><strong>{{ number_format($d['count']) }}</strong><span class="distro-pct">{{ $pct }}%</span></div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
</x-layout>
