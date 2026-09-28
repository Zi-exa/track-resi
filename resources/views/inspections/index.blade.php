<x-layout title="Perlu Diperiksa">
    <div class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">Prioritas operasional</p>
            <h1>Perlu diperiksa</h1>
            <p>Paket terlambat diurutkan agar kasus paling lama ditangani lebih dahulu.</p>
        </div>
        <a class="button button-primary" href="{{ route('scan.index') }}">⌗ Scan paket</a>
    </div>

    <section class="card" style="margin-bottom:18px">
        <form class="filter-bar inspection-filter" method="GET" action="{{ route('inspections.index') }}">
            <div class="field">
                <label for="sort">Urutkan berdasarkan</label>
                <select id="sort" name="sort">
                    <option value="" @selected($sort === '')>Lama keterlambatan</option>
                    <option value="tracking" @selected($sort === 'tracking')>Tracking paling lama tidak berubah</option>
                    <option value="newest" @selected($sort === 'newest')>Tanggal retur terbaru</option>
                    <option value="courier" @selected($sort === 'courier')>Nama kurir</option>
                </select>
            </div>
            <button class="button button-secondary" type="submit">Terapkan</button>
        </form>
    </section>

    <section class="card table-card">
        <div class="card-header">
            <div><h2>Antrian pemeriksaan</h2><p>{{ $returns->total() }} paket membutuhkan perhatian</p></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Resi / Order</th><th>Produk</th><th>Kurir</th><th>Usia retur</th><th>Tracking terakhir</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($returns as $return)
                    <tr>
                        <td><a class="tracking-link" href="{{ route('returns.show', $return) }}">{{ $return->tracking_number }}</a><small>{{ $return->order->order_number }}</small></td>
                        <td>{{ $return->order->product_name }}<small>{{ $return->region ?: 'Wilayah tidak tersedia' }}</small></td>
                        <td>{{ $return->courier ?: 'Belum diketahui' }}</td>
                        <td><strong>{{ $return->age_in_days }} hari</strong><small>Batas {{ $return->deadline_days }} hari · lewat {{ $return->overdue_days }} hari</small></td>
                        <td>{{ $return->last_tracking_update?->translatedFormat('d M Y, H:i') ?: 'Belum dicatat' }}</td>
                        <td><span class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</span></td>
                        <td><a class="row-arrow" href="{{ route('returns.show', $return) }}">›</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><span>✓</span><strong>Tidak ada antrian pemeriksaan</strong><p>Semua paket masih dalam batas normal atau sudah ditangani.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="pagination-wrap">
                <span>Halaman {{ $returns->currentPage() }} dari {{ $returns->lastPage() }}</span>
                <div class="button-row">
                    @if($returns->onFirstPage())<span class="button button-secondary" style="opacity:.45">← Sebelumnya</span>@else<a class="button button-secondary" href="{{ $returns->previousPageUrl() }}">← Sebelumnya</a>@endif
                    @if($returns->hasMorePages())<a class="button button-secondary" href="{{ $returns->nextPageUrl() }}">Berikutnya →</a>@endif
                </div>
            </div>
        @endif
    </section>
</x-layout>
