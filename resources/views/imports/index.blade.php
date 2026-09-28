<x-layout title="Import Data">
    <div class="page-heading"><p class="eyebrow">Sumber data TikTok Shop</p><h1>Import data retur</h1><p>Upload 1 atau 2 file sekaligus — sistem deteksi otomatis Jalur A / B tanpa pilih manual.</p></div>

    <div class="import-layout">
        <section class="card import-card">
            <form action="{{ route('imports.preview') }}" method="POST" enctype="multipart/form-data" class="stack-form">
                @csrf
                <div class="upload-zone">
                    <span class="upload-icon">⇧</span>
                    <strong>Drag &amp; drop atau klik untuk pilih file</strong>
                    <p>Bisa pilih 2 file sekaligus (Jalur A + Jalur B) · Format .xlsx / .csv · Maks 10 MB/file</p>
                    <input type="file" name="files[]" accept=".xlsx,.csv" multiple required>
                </div>
                <div class="jalur-hints">
                    <div class="jalur-hint"><span class="source-icon source-icon-blue">A</span><div><strong>Jalur A · Gagal Kirim</strong><p>Resi JY · terdeteksi dari <code>Cancelation/Return Type</code></p></div></div>
                    <div class="jalur-hint"><span class="source-icon source-icon-purple">B</span><div><strong>Jalur B · Refund</strong><p>Resi JX · terdeteksi dari <code>Return Logistics Tracking ID</code></p></div></div>
                </div>
                <p class="file-note">Tidak perlu pilih radio lagi — deteksi otomatis via header file.</p>
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
                @error('files')<p class="field-error">{{ $message }}</p>@enderror
                @error('files.*')<p class="field-error">{{ $message }}</p>@enderror
                <button class="button button-primary" type="submit">Tampilkan preview →</button>
                <p class="file-note file-note-dark">Hari tanpa retur? Tidak perlu ekspor — skip saja, dashboard tetap hitung data lama.</p>
            </form>
        </section>

        <aside class="card import-guide">
            <div class="card-header"><div><h2>Sebelum import</h2><p>Checklist data yang aman</p></div></div>
            <ol><li><span>1</span><p><strong>Upload langsung</strong>Pilih 1 file atau 2 file A+B sekaligus, deteksi jalan otomatis.</p></li><li><span>2</span><p><strong>Periksa preview</strong>ID panjang & tanggal DD/MM/YYYY dinormalisasi otomatis.</p></li><li><span>3</span><p><strong>Konfirmasi simpan</strong>Resi duplikat dilewati, baris Refund only jadi Selesai Tanpa Retur Fisik.</p></li></ol>
            <div class="info-note"><span>i</span><p>Jika header tidak dikenali, cek apakah file benar ekspor Seller Center (bukan Semua Pesanan). Jalur A = menu <strong>Pesanan Dibatalkan</strong>, Jalur B = menu <strong>Barang/Dana Dikembalikan</strong>.</p></div>
        </aside>
    </div>

    <section class="card table-card">
        <div class="card-header"><div><h2>Riwayat import</h2><p>10 aktivitas import terakhir</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>File</th><th>Jalur</th><th>Total</th><th>Berhasil</th><th>Dilewati</th><th>Waktu</th></tr></thead><tbody>
        @forelse($imports as $import)<tr><td class="table-title-cell" title="{{ $import->filename }}">{{ $import->filename }}<small>{{ $import->user->name }}</small></td><td>@if($import->source === 'gagal_kirim') Gagal Kirim @elseif($import->source === 'refund') Refund @elseif($import->source === 'mixed') Campuran A+B @else {{ ucfirst($import->source) }} @endif</td><td>{{ $import->total_rows }}</td><td><span class="badge badge-success">{{ $import->success_rows }}</span></td><td>{{ $import->failed_rows }}</td><td>{{ $import->imported_at->translatedFormat('d M Y, H:i') }}</td></tr>
        @empty<tr><td colspan="6"><div class="empty-state"><span>⇧</span><strong>Belum ada riwayat import</strong><p>Aktivitas import akan muncul di sini.</p></div></td></tr>@endforelse
        </tbody></table></div>
    </section>
</x-layout>
