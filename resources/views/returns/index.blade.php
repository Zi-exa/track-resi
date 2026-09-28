<x-layout title="Data Retur">
    @php
        $pillTone = [
            'belum_diterima' => 'pill-slate',
            'terlambat' => 'pill-amber',
            'perlu_diperiksa' => 'pill-orange',
            'perlu_dilaporkan' => 'pill-orange',
            'dalam_investigasi' => 'pill-slate',
            'sudah_diterima' => 'pill-slate',
            'hilang' => 'pill-slate',
        ];
        $pillKeys = ['belum_diterima', 'terlambat', 'perlu_diperiksa', 'perlu_dilaporkan', 'dalam_investigasi', 'sudah_diterima', 'hilang'];
    @endphp

    <div class="page-heading page-heading-actions">
        <div><p class="eyebrow">Monitoring paket</p><h1>Data retur</h1><p>Cari, filter, dan tindak lanjuti seluruh retur TikTok Shop.</p></div>
        <a class="button button-primary" href="{{ route('imports.index') }}">⇧ Import data</a>
    </div>

    <section class="pill-row" aria-label="Filter cepat status">
        <span class="pill-caption">Status:</span>
        <a class="pill {{ request('status') ? 'pill-slate' : 'pill-amber' }}" href="{{ route('returns.index', request()->except('status')) }}">Semua</a>
        @foreach($pillKeys as $key)
            <a class="pill {{ request('status') === $key ? 'pill-amber' : ($pillTone[$key] ?? 'pill-slate') }}" href="{{ route('returns.index', array_merge(request()->except('status'), ['status' => $key])) }}"><i></i>{{ $statuses[$key] ?? $key }}</a>
        @endforeach
    </section>

    <section class="card stack-section">
        <form class="filter-bar" method="GET" action="{{ route('returns.index') }}">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="field search-input"><label for="search">Pencarian</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Resi, Order ID, atau produk"></div>
            <div class="field"><label for="source">Jalur retur</label><select id="source" name="source"><option value="">Retur fisik</option><option value="gagal_kirim" @selected(request('source') === 'gagal_kirim')>Gagal Kirim</option><option value="refund_delivered" @selected(request('source') === 'refund_delivered')>Refund Setelah Diterima</option><option value="refund_no_physical" @selected(request('source') === 'refund_no_physical')>Tanpa Retur Fisik</option></select></div>
            <div class="field"><label for="courier">Kurir</label><select id="courier" name="courier"><option value="">Semua kurir</option>@foreach($couriers as $courier)<option value="{{ $courier }}" @selected(request('courier') === $courier)>{{ $courier }}</option>@endforeach</select></div>
            <div class="field"><label for="date">Tanggal retur</label><input id="date" type="date" name="date" value="{{ request('date') }}"></div>
            <button class="button button-secondary" type="submit">Terapkan</button>
        </form>
    </section>

    <section class="card table-card">
        <div class="card-header"><div><h2>Daftar retur</h2><p>{{ $returns->total() }} data ditemukan</p></div>@if(request()->hasAny(['search','status','source','courier','date']))<a class="text-link" href="{{ route('returns.index') }}">Reset filter</a>@endif</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Resi / Order</th><th>Produk</th><th>Jalur &amp; Kurir</th><th>Tanggal Retur</th><th>Umur</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
                <tbody>
                @forelse($returns as $return)
                    @php $age = $return->age_in_days; @endphp
                    <tr>
                        <td><a class="tracking-link" href="{{ route('returns.show', $return) }}">{{ $return->tracking_number ?: 'Tanpa resi' }}</a><small>{{ $return->order->order_number }}</small></td>
                        <td>{{ $return->order->product_name }}<small>{{ $return->order->variation ?: '—' }} · {{ $return->order->quantity }} pcs</small></td>
                        <td>{{ $return->source_label }}<small>{{ $return->courier ?: 'Kurir tidak tersedia' }}</small></td>
                        <td>{{ $return->return_date->translatedFormat('d M Y') }}<small>{{ $return->return_date->format('H:i') }}</small></td>
                        <td>@if($age !== null)<span class="age-pill {{ $age > 10 ? 'age-bad' : ($age > 7 ? 'age-warn' : 'age-ok') }}">{{ $age }} hr</span>@else<span style="color:var(--muted-2)">—</span>@endif</td>
                        <td>
                            <div class="status-stack">
                                <span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span>
                                @if(!in_array($return->courier_refund_status, [\App\Enums\CourierRefundStatus::PENDING, \App\Enums\CourierRefundStatus::NOT_APPLICABLE], true))
                                    <small>Dana: {{ $return->courier_refund_status->label() }}</small>
                                @endif
                            </div>
                        </td>
                        <td style="text-align:right"><a class="button button-secondary button-small" href="{{ route('returns.show', $return) }}">Proses</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><span>⌕</span><strong>Data tidak ditemukan</strong><p>Coba ubah kata kunci atau filter yang digunakan.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())<div class="pagination-wrap"><span>Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}</span><div class="button-row">@if($returns->onFirstPage())<span class="button button-secondary" style="opacity:.45">← Sebelumnya</span>@else<a class="button button-secondary" href="{{ $returns->previousPageUrl() }}">← Sebelumnya</a>@endif @if($returns->hasMorePages())<a class="button button-secondary" href="{{ $returns->nextPageUrl() }}">Berikutnya →</a>@endif</div></div>@endif
    </section>
</x-layout>
