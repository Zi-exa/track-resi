<x-layout title="Laporan">
    <div class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">Analitik retur</p>
            <h1>Laporan</h1>
            <p>Filter data operasional lalu unduh hasil yang sama dalam CSV atau Excel.</p>
        </div>
        <div class="heading-actions">
            <a class="button button-secondary" href="{{ route('reports.csv', request()->query()) }}">↓ CSV</a>
            <a class="button button-primary" href="{{ route('reports.xlsx', request()->query()) }}">↓ Excel</a>
        </div>
    </div>

    <section class="card stack-section">
        <form class="filter-bar report-filter" method="GET" action="{{ route('reports.index') }}">
            <div class="field"><label for="date_from">Tanggal awal</label><input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
            <div class="field"><label for="date_to">Tanggal akhir</label><input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="courier">Kurir</label><select id="courier" name="courier"><option value="">Semua kurir</option>@foreach($couriers as $courier)<option value="{{ $courier }}" @selected(request('courier') === $courier)>{{ $courier }}</option>@endforeach</select></div>
            <button class="button button-secondary" type="submit">Terapkan</button>
        </form>
    </section>

    <section class="kpi-grid" aria-label="Ringkasan laporan">
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Total retur</span></div><strong>{{ number_format($summary['total']) }}</strong><span class="kpi-foot">Sesuai filter aktif</span></div>
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Sudah diterima</span><span class="kpi-ico kpi-ico-green">✓</span></div><strong>{{ number_format($summary['sudah_diterima']) }}</strong><span class="kpi-foot">Paket tiba di gudang</span></div>
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Belum diterima</span><span class="kpi-pill">On Track</span></div><strong>{{ number_format($summary['belum_diterima']) }}</strong><span class="kpi-foot">Dalam batas normal</span></div>
        <div class="kpi-card {{ ($summary['terlambat'] + $summary['perlu_diperiksa'] + $summary['perlu_dilaporkan']) > 0 ? 'kpi-alert' : '' }}"><div class="kpi-top"><span class="kpi-label">Butuh tindakan</span>@if(($summary['terlambat'] + $summary['perlu_diperiksa'] + $summary['perlu_dilaporkan']) > 0)<span class="kpi-pill kpi-pill-red">Kritis</span>@endif</div><strong>{{ number_format($summary['terlambat'] + $summary['perlu_diperiksa'] + $summary['perlu_dilaporkan']) }}</strong><span class="kpi-foot">{{ number_format($summary['terlambat']) }} terlambat · {{ number_format($summary['perlu_diperiksa']) }} periksa · {{ number_format($summary['perlu_dilaporkan']) }} lapor</span></div>
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Terlambat</span></div><strong>{{ number_format($summary['terlambat']) }}</strong><span class="kpi-foot">Melewati estimasi normal</span></div>
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Perlu diperiksa</span></div><strong>{{ number_format($summary['perlu_diperiksa']) }}</strong><span class="kpi-foot">Cek tracking stagnan</span></div>
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Dalam investigasi</span></div><strong>{{ number_format($summary['dalam_investigasi']) }}</strong><span class="kpi-foot">Dilaporkan ke kurir</span></div>
        <div class="kpi-card"><div class="kpi-top"><span class="kpi-label">Hilang</span></div><strong>{{ number_format($summary['hilang']) }}</strong><span class="kpi-foot">Dikonfirmasi hilang</span></div>
    </section>

    <section class="card table-card" style="margin-top:18px">
        <div class="card-header"><div><h2>Data laporan</h2><p>{{ $returns->total() }} retur sesuai filter</p></div>@if(request()->query())<a class="text-link" href="{{ route('reports.index') }}">Reset filter</a>@endif</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Resi / Order</th><th>Produk</th><th>Kurir & Wilayah</th><th>Tanggal Retur</th><th>Diterima</th><th>Status</th><th>Dana Kurir</th></tr></thead>
                <tbody>
                @forelse($returns as $return)
                    <tr>
                        <td>
                            @if($return->tracking_number)
                                <a class="tracking-link" href="{{ route('returns.show', $return) }}">{{ $return->tracking_number }}</a>
                            @else
                                Tanpa resi
                            @endif
                            <small>{{ $return->order->order_number }}</small>
                        </td>
                        <td>{{ $return->order->product_name }}<small>{{ $return->order->variation ?: '—' }}</small></td>
                        <td>{{ $return->courier ?: '—' }}<small>{{ $return->region ?: 'Wilayah tidak tersedia' }}</small></td>
                        <td>{{ $return->return_date->translatedFormat('d M Y, H:i') }}</td>
                        <td>{{ $return->received_at?->translatedFormat('d M Y, H:i') ?: '—' }}</td>
                        <td><span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span></td>
                        <td><span class="badge badge-{{ $return->courier_refund_status->tone() }}">{{ $return->courier_refund_status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><span>▥</span><strong>Tidak ada data laporan</strong><p>Ubah filter atau import data retur terlebih dahulu.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())<div class="pagination-wrap"><span>Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}</span><div class="button-row">@if($returns->onFirstPage())<span class="button button-secondary" style="opacity:.45">← Sebelumnya</span>@else<a class="button button-secondary" href="{{ $returns->previousPageUrl() }}">← Sebelumnya</a>@endif @if($returns->hasMorePages())<a class="button button-secondary" href="{{ $returns->nextPageUrl() }}">Berikutnya →</a>@endif</div></div>@endif
    </section>
</x-layout>
