<x-layout title="Detail Retur">
    <div class="page-heading page-heading-actions">
        <div><a class="text-link" href="{{ route('returns.index') }}">← Kembali ke data retur</a><h1 style="margin-top:12px">{{ $return->tracking_number ?: 'Retur tanpa resi' }}</h1><p>Order {{ $return->order->order_number }} · {{ $return->source_label }}</p></div>
        <span class="badge badge-{{ $return->status->tone() }}" style="font-size:11px;padding:8px 12px">{{ $return->status->label() }}</span>
    </div>

    <div class="detail-page-grid">
        <div class="detail-main">
            <section class="card detail-section">
                <div class="card-header"><div><h2>Informasi pesanan</h2><p>Data produk dari file TikTok Shop</p></div></div>
                <dl class="detail-list"><div><dt>Order ID</dt><dd>{{ $return->order->order_number }}</dd></div><div><dt>Produk</dt><dd>{{ $return->order->product_name }}</dd></div><div><dt>Variasi</dt><dd>{{ $return->order->variation ?: '—' }}</dd></div><div><dt>Jumlah</dt><dd>{{ $return->order->quantity }} pcs</dd></div><div><dt>SKU ID</dt><dd>{{ $return->order->sku_id ?: '—' }}</dd></div><div><dt>Alasan retur</dt><dd>{{ $return->return_reason ?: '—' }}</dd></div></dl>
            </section>

            <section class="card detail-section">
                <div class="card-header"><div><h2>Informasi retur</h2><p>Perjalanan dan sumber retur</p></div></div>
                <dl class="detail-list"><div><dt>Nomor resi</dt><dd>{{ $return->tracking_number ?: 'Tidak ada (Refund only)' }}</dd></div><div><dt>Kurir</dt><dd>{{ $return->courier ?: 'Tidak tersedia' }}</dd></div><div><dt>Tanggal retur</dt><dd>{{ $return->return_date->translatedFormat('d F Y, H:i') }}</dd></div><div><dt>Wilayah</dt><dd>{{ $return->region ?: 'Tidak tersedia' }}</dd></div><div><dt>Lama perjalanan</dt><dd>{{ $return->age_in_days !== null ? $return->age_in_days.' hari' : 'Tidak dimonitor' }}</dd></div><div><dt>Status TikTok</dt><dd>{{ $return->tiktok_status ?: '—' }}</dd></div></dl>
            </section>

            @if($return->received_at)
                <section class="card detail-section accent-success"><div class="card-header"><div><h2>Informasi penerimaan</h2><p>Paket telah diverifikasi lewat scan</p></div></div><dl class="detail-list"><div><dt>Diterima pada</dt><dd>{{ $return->received_at->translatedFormat('d F Y, H:i') }}</dd></div><div><dt>Diterima oleh</dt><dd>{{ $return->receiver?->name ?: 'Admin' }}</dd></div></dl></section>
            @endif

            @if($return->courierReport)
                <section class="card detail-section accent-purple"><div class="card-header"><div><h2>Laporan kurir</h2><p>Informasi investigasi terakhir</p></div></div><dl class="detail-list"><div><dt>Nomor tiket</dt><dd>{{ $return->courierReport->ticket_number }}</dd></div><div><dt>Tanggal laporan</dt><dd>{{ $return->courierReport->report_date->translatedFormat('d F Y') }}</dd></div><div><dt>Status investigasi</dt><dd>{{ $return->courierReport->investigation_status }}</dd></div><div><dt>Catatan</dt><dd>{{ $return->courierReport->notes ?: '—' }}</dd></div></dl></section>
            @endif
        </div>

        <aside class="detail-aside">
            @if($return->requires_physical_return)
                <section class="card action-card manual-status-card">
                    <h2>Update status manual</h2>
                    <p>Pilih hasil terbaru. Status paket dan dana kurir disimpan terpisah agar tindak lanjut tetap jelas.</p>

                    <div class="manual-status-summary">
                        <div>
                            <span>Status paket</span>
                            <strong class="badge badge-{{ $return->status->tone() }}">{{ $return->status->label() }}</strong>
                        </div>
                        <div>
                            <span>Status dana kurir</span>
                            @if($return->courier_refund_status === \App\Enums\CourierRefundStatus::PENDING)
                                <strong>Belum ditetapkan</strong>
                            @else
                                <strong class="badge badge-{{ $return->courier_refund_status->tone() }}">{{ $return->courier_refund_status->label() }}</strong>
                            @endif
                        </div>
                    </div>

                    <div class="manual-action-grid">
                        @if(!$return->received_at)
                            <form action="{{ route('returns.manual-action', $return) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="received">
                                <button class="button button-success" type="submit">✓ Paket sudah diterima</button>
                            </form>
                        @endif

                        @if(!$return->reported_at && !$return->received_at && !$return->lost_confirmed_at)
                            <form action="{{ route('returns.manual-action', $return) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="reported">
                                <button class="button button-primary" type="submit">↗ Sudah dilaporkan</button>
                            </form>
                        @endif

                        @if(!$return->received_at && $return->courier_refund_status !== \App\Enums\CourierRefundStatus::REFUNDED)
                            <form action="{{ route('returns.manual-action', $return) }}" method="POST" onsubmit="return confirm('Konfirmasi bahwa dana dari kurir sudah benar-benar diterima?')">
                                @csrf
                                <input type="hidden" name="action" value="refunded">
                                <button class="button button-secondary" type="submit">Rp Uang sudah direfund</button>
                            </form>
                        @endif

                        @if(!$return->received_at && !($return->lost_confirmed_at && $return->courier_refund_status === \App\Enums\CourierRefundStatus::NOT_REFUNDED))
                            <form action="{{ route('returns.manual-action', $return) }}" method="POST" onsubmit="return confirm('Yakin paket ini hilang dan belum mendapat refund dari kurir?')">
                                @csrf
                                <input type="hidden" name="action" value="lost_without_refund">
                                <button class="button button-danger" type="submit">× Hilang tanpa refund</button>
                            </form>
                        @endif
                    </div>

                    @if($return->courier_refunded_at)
                        <p class="manual-status-note">Refund dicatat {{ $return->courier_refunded_at->translatedFormat('d F Y, H:i') }}.</p>
                    @elseif($return->no_refund_confirmed_at)
                        <p class="manual-status-note">Tanpa refund dikonfirmasi {{ $return->no_refund_confirmed_at->translatedFormat('d F Y, H:i') }}.</p>
                    @endif
                </section>
            @endif

            @if($return->requires_physical_return && !$return->received_at && !$return->lost_confirmed_at)
                <section class="card action-card"><h2>Laporkan ke kurir</h2><p>Gunakan setelah paket melewati batas dan tracking tidak berkembang.</p><form class="stack-form" action="{{ route('returns.report', $return) }}" method="POST">@csrf<div class="field"><label>Tanggal laporan</label><input type="date" name="report_date" value="{{ now()->format('Y-m-d') }}" required></div><div class="field"><label>Nomor tiket</label><input name="ticket_number" placeholder="Contoh: TKT-88421" required></div><div class="field"><label>Kurir</label><input name="courier" value="{{ $return->courier }}" required></div><div class="field"><label>Status investigasi</label><input name="investigation_status" value="Dalam proses" required></div><div class="field"><label>Catatan</label><textarea name="notes" placeholder="Hasil pengecekan atau kronologi"></textarea></div><button class="button button-primary" type="submit">Simpan laporan</button></form></section>
            @endif
        </aside>
    </div>
</x-layout>
