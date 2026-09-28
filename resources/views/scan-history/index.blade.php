<x-layout title="Riwayat Scan">
    <div class="page-heading page-heading-actions">
        <div>
            <p class="eyebrow">Audit penerimaan</p>
            <h1>Riwayat scan</h1>
            <p>Lihat setiap scan berhasil, scan ganda, dan resi yang tidak dikenali.</p>
        </div>
        <a class="button button-primary" href="{{ route('scan.index') }}">⌗ Scan paket</a>
    </div>

    <section class="card" style="margin-bottom:18px">
        <form class="filter-bar" method="GET" action="{{ route('scan-history.index') }}">
            <div class="field search-input"><label for="search">Nomor resi</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Cari resi"></div>
            <div class="field"><label for="result">Hasil</label><select id="result" name="result"><option value="">Semua hasil</option>@foreach($resultLabels as $value => $label)<option value="{{ $value }}" @selected(request('result') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="date">Tanggal scan</label><input id="date" type="date" name="date" value="{{ request('date') }}"></div>
            <button class="button button-secondary" type="submit">Terapkan</button>
        </form>
    </section>

    <section class="card table-card">
        <div class="card-header"><div><h2>Aktivitas scan</h2><p>{{ $histories->total() }} aktivitas ditemukan</p></div>@if(request()->hasAny(['search','result','date']))<a class="text-link" href="{{ route('scan-history.index') }}">Reset filter</a>@endif</div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Nomor resi</th><th>Hasil</th><th>Pesanan</th><th>User</th></tr></thead>
                <tbody>
                @forelse($histories as $history)
                    <tr>
                        <td>{{ $history->scan_time->translatedFormat('d M Y') }}<small>{{ $history->scan_time->format('H:i:s') }}</small></td>
                        <td>@if($history->returnRecord)<a class="tracking-link" href="{{ route('returns.show', $history->returnRecord) }}">{{ $history->tracking_number }}</a>@else<strong>{{ $history->tracking_number }}</strong>@endif</td>
                        <td><span class="badge badge-{{ $history->result === 'diterima' ? 'success' : ($history->result === 'scan_ganda' ? 'warning' : 'dark') }}">{{ $resultLabels[$history->result] ?? ucfirst(str_replace('_', ' ', $history->result)) }}</span></td>
                        <td>{{ $history->returnRecord?->order?->order_number ?: 'Tidak terkait data retur' }}</td>
                        <td>{{ $history->user->name }}<small>{{ $history->user->email }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><span>↻</span><strong>Belum ada riwayat scan</strong><p>Aktivitas akan tercatat setelah paket pertama dipindai.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($histories->hasPages())<div class="pagination-wrap"><span>Halaman {{ $histories->currentPage() }} dari {{ $histories->lastPage() }}</span><div class="button-row">@if($histories->onFirstPage())<span class="button button-secondary" style="opacity:.45">← Sebelumnya</span>@else<a class="button button-secondary" href="{{ $histories->previousPageUrl() }}">← Sebelumnya</a>@endif @if($histories->hasMorePages())<a class="button button-secondary" href="{{ $histories->nextPageUrl() }}">Berikutnya →</a>@endif</div></div>@endif
    </section>
</x-layout>
