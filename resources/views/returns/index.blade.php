<x-layout title="Data Retur">
    <div class="page-heading page-heading-actions">
        <div><p class="eyebrow">Monitoring paket</p><h1>Data retur</h1><p>Cari, filter, dan tindak lanjuti seluruh retur TikTok Shop.</p></div>
        <a class="button button-primary" href="/imports">⇧ Import data</a>
    </div>

    <section class="card" style="margin-bottom:18px">
        <form class="filter-bar" method="GET" action="{{ route('returns.index') }}">
            <div class="field search-input"><label for="search">Pencarian</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Resi, Order ID, atau produk"></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="source">Jalur retur</label><select id="source" name="source"><option value="">Retur fisik</option><option value="gagal_kirim" @selected(request('source') === 'gagal_kirim')>Gagal Kirim</option><option value="refund_delivered" @selected(request('source') === 'refund_delivered')>Refund Setelah Diterima</option><option value="refund_no_physical" @selected(request('source') === 'refund_no_physical')>Tanpa Retur Fisik</option></select></div>
            <div class="field"><label for="courier">Kurir</label><select id="courier" name="courier"><option value="">Semua kurir</option>@foreach($couriers as $courier)<option value="{{ $courier }}" @selected(request('courier') === $courier)>{{ $courier }}</option>@endforeach</select></div>
            <button class="button button-secondary" type="submit">Terapkan</button>
        </form>
    </section>

    <section class="card table-card">
        <div class="card-header"><div><h2>Daftar retur</h2><p>{{ $returns->total() }} data ditemukan</p></div>@if(request()->hasAny(['search','status','source','courier']))<a class="text-link" href="{{ route('returns.index') }}">Reset filter</a>@endif</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Resi / Order</th><th>Produk</th><th>Jalur & Kurir</th><th>Tanggal Retur</th><th>Lama</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($returns as $return)
                    <tr>
                        <td><a class="tracking-link" href="{{ route('returns.show', $return) }}">{{ $return->tracking_number ?: 'Tanpa resi' }}</a><small>{{ $return->order->order_number }}</small></td>
                        <td>{{ $return->order->product_name }}<small>{{ $return->order->variation ?: '—' }} · {{ $return->order->quantity }} pcs</small></td>
                        <td>{{ $return->source_label }}<small>{{ $return->courier ?: 'Kurir tidak tersedia' }}</small></td>
                        <td>{{ $return->return_date->translatedFormat('d M Y') }}<small>{{ $return->return_date->format('H:i') }}</small></td>
                        <td>{{ $return->age_in_days !== null ? $return->age_in_days.' hari' : '—' }}</td>
                        <td>
                            <div class="status-stack">
                                <span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span>
                                @if(!in_array($return->courier_refund_status, [\App\Enums\CourierRefundStatus::PENDING, \App\Enums\CourierRefundStatus::NOT_APPLICABLE], true))
                                    <small>Dana: {{ $return->courier_refund_status->label() }}</small>
                                @endif
                            </div>
                        </td>
                        <td><a class="row-arrow" href="{{ route('returns.show', $return) }}">›</a></td>
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
