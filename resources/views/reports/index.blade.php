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

    <section class="card" style="margin-bottom:18px">
        <form class="filter-bar report-filter" method="GET" action="{{ route('reports.index') }}">
            <div class="field"><label for="date_from">Tanggal awal</label><input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"></div>
            <div class="field"><label for="date_to">Tanggal akhir</label><input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="courier">Kurir</label><select id="courier" name="courier"><option value="">Semua kurir</option>@foreach($couriers as $courier)<option value="{{ $courier }}" @selected(request('courier') === $courier)>{{ $courier }}</option>@endforeach</select></div>
            <button class="button button-secondary" type="submit">Terapkan</button>
        </form>
    </section>

    <section class="report-summary-grid">
        <div class="card"><span>Total retur</span><strong>{{ number_format($summary['total']) }}</strong></div>
        <div class="card"><span>Sudah diterima</span><strong>{{ number_format($summary['sudah_diterima']) }}</strong></div>
        <div class="card"><span>Belum diterima</span><strong>{{ number_format($summary['belum_diterima']) }}</strong></div>
        <div class="card"><span>Terlambat</span><strong>{{ number_format($summary['terlambat']) }}</strong></div>
        <div class="card"><span>Perlu diperiksa</span><strong>{{ number_format($summary['perlu_diperiksa']) }}</strong></div>
        <div class="card"><span>Perlu dilaporkan</span><strong>{{ number_format($summary['perlu_dilaporkan']) }}</strong></div>
        <div class="card"><span>Dalam investigasi</span><strong>{{ number_format($summary['dalam_investigasi']) }}</strong></div>
        <div class="card"><span>Hilang</span><strong>{{ number_format($summary['hilang']) }}</strong></div>
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
