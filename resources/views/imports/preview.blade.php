<x-layout title="Preview Import">
    <div class="page-heading page-heading-actions">
        <div><a class="text-link" href="{{ route('imports.index') }}">← Pilih file lain</a><h1 style="margin-top:12px">Preview import</h1>
            <p style="overflow-wrap:anywhere">{{ $filename }} · 
                @if(($source ?? '') === 'mixed') Campuran A+B (otomatis terdeteksi)
                @elseif(($source ?? '') === 'gagal_kirim') Jalur A — Gagal Kirim (otomatis)
                @else Jalur B — Refund (otomatis) @endif
                @if(!empty($sources)) <small style="color:var(--muted-2)"> · sumber: {{ implode(', ', $sources) }}</small> @endif
            </p>
            @if(!empty($filenames) && count($filenames) > 1)<p style="margin-top:6px"><span class="badge badge-neutral">{{ count($filenames) }} file digabung</span></p>@endif
        </div>
        @if(count($rows) > 0)<form action="{{ route('imports.commit') }}" method="POST">@csrf<button class="button button-primary" type="submit">Simpan {{ count($rows) }} data →</button></form>@endif
    </div>

    <div class="preview-summary">
        <div class="card"><span class="metric-icon metric-icon-green">✓</span><div><strong>{{ count($rows) }}</strong><small>Baris valid (gabungan)</small></div></div>
        <div class="card"><span class="metric-icon metric-icon-red">!</span><div><strong>{{ count($errors) }}</strong><small>Baris bermasalah</small></div></div>
        <div class="card"><span class="metric-icon metric-icon-blue">↩</span><div><strong>{{ collect($rows)->where('requires_physical_return', true)->count() }}</strong><small>Retur fisik</small></div></div>
        <div class="card"><span class="metric-icon metric-icon-purple">○</span><div><strong>{{ collect($rows)->where('requires_physical_return', false)->count() }}</strong><small>Tanpa retur fisik</small></div></div>
    </div>

    @if(count($errors))<section class="card error-list"><div class="card-header"><div><h2>Baris perlu diperbaiki</h2><p>Data ini tidak akan disimpan — per file terpisah</p></div></div><ul>@foreach($errors as $error)<li><strong>Baris {{ $error['row'] }}</strong><span>{{ $error['message'] }}</span></li>@endforeach</ul></section>@endif

    <section class="card table-card" style="margin-top:18px"><div class="card-header"><div><h2>Data valid</h2><p>Periksa kembali sebelum menyimpan — 2 file sudah digabung, duplikat resi akan dilewati saat simpan</p></div></div><div class="table-wrap"><table><thead><tr><th>Order ID</th><th>Resi</th><th>Produk</th><th>Tanggal</th><th>Jalur</th><th>Status</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td>{{ $row['order_number'] }}<small>{{ $row['sku_id'] ?: 'SKU tidak tersedia' }}</small></td><td>{{ $row['tracking_number'] ?: 'Tanpa resi' }}</td><td>{{ $row['product_name'] }}<small>{{ $row['variation'] }}</small></td><td>{{ \Illuminate\Support\Carbon::parse($row['return_date'])->translatedFormat('d M Y, H:i') }}</td><td>{{ match($row['return_source']) {'gagal_kirim' => 'Gagal Kirim', 'refund_delivered' => 'Refund Setelah Diterima', default => 'Tanpa Retur Fisik'} }}</td><td><span class="badge badge-{{ $row['requires_physical_return'] ? 'neutral' : 'success' }}">{{ \App\Enums\ReturnStatus::from($row['status'])->label() }}</span></td></tr>
    @empty<tr><td colspan="6"><div class="empty-state"><span>!</span><strong>Tidak ada baris valid</strong><p>Perbaiki file lalu lakukan import ulang.</p></div></td></tr>@endforelse
    </tbody></table></div></section>
</x-layout>
