# Tasklist Development — ReturnTrack (Emza Store)

Disusun berurutan sesuai dependensi (fase awal harus selesai dulu sebelum fase berikutnya). Referensi bagian PRD dicantumkan di tiap grup biar gampang dicek ulang.

---

## Fase 0 — Setup Project

- [ ] Init project Laravel + repo Git
- [ ] Setup database MySQL, `.env`
- [ ] Install Laravel Excel (import xlsx/csv)
- [ ] Setup Tailwind CSS / Bootstrap
- [ ] Setup struktur folder & routing dasar

## Fase 1 — Authentication *(PRD Bagian 5)*

- [ ] Migration & model `users`
- [ ] Halaman login (Blade)
- [ ] Session login / logout
- [ ] Password hashing (bawaan Laravel)
- [ ] Middleware auth di semua route admin

## Fase 2 — Skema Database Inti *(PRD Bagian 27)*

- [ ] Migration `orders`
- [ ] Migration `returns` — **termasuk kolom baru**: `return_source`, `requires_physical_return`, `tiktok_return_type`, `tiktok_status`; `courier` & `region` dibuat nullable
- [ ] Migration `scan_histories`
- [ ] Migration `courier_reports`
- [ ] Migration `return_deadlines`
- [ ] Migration `imports`
- [ ] Seed batas waktu default per wilayah (Bagian 16)

## Fase 3 — Import Data, Dua Jalur *(PRD Bagian 7)*

- [ ] Form upload — pilihan eksplisit "Jalur A (Pesanan Dibatalkan)" vs "Jalur B (Refund)"
- [ ] Parser: `trim()` semua kolom ID (fix whitespace/tab dari ekspor TikTok)
- [ ] Parser: kolom ID (Order ID, SKU ID) disimpan sebagai string, bukan integer
- [ ] Parser: format tanggal eksplisit `DD/MM/YYYY HH:mm:ss`
- [ ] Parser: strip `Rp` + titik ribuan dari kolom nominal
- [ ] Mapping kolom sesuai tabel di Bagian 7 (kolom beda antara dua file)
- [ ] Logic set `return_source`: `gagal_kirim` (Jalur A) / `refund_delivered` (Jalur B, Return and refund) / `refund_no_physical` (Jalur B, Refund only)
- [ ] Logic: baris `Refund only` otomatis → status `SELESAI TANPA RETUR FISIK`, `requires_physical_return = false`, tanpa nomor resi
- [ ] Validasi resi duplikat — skip jika sudah ada
- [ ] Preview data sebelum commit ke database
- [ ] Simpan log ke tabel `imports`

## Fase 4 — Dashboard *(PRD Bagian 6)*

- [ ] Card ringkasan (Total, Sudah Diterima, Belum Diterima, Terlambat, dst)
- [ ] Query count status — exclude `SELESAI TANPA RETUR FISIK` dari card utama
- [ ] Tabel "Retur Terbaru"

## Fase 5 — Data Retur: List, Search, Filter *(PRD Bagian 8)*

- [ ] Tabel list + pagination
- [ ] Search: nomor resi, Order ID, nama produk
- [ ] Filter status (semua status di Bagian 14)
- [ ] Filter tambahan: kurir, tanggal, wilayah
- [ ] Filter baru: **Jalur Retur** (Gagal Kirim / Refund Setelah Diterima / Selesai Tanpa Retur Fisik) — default sembunyikan "Selesai Tanpa Retur Fisik"

## Fase 6 — Detail Retur *(PRD Bagian 9)*

- [ ] Halaman detail: info pesanan, info retur, monitoring, info penerimaan, info pelaporan kurir
- [ ] Tombol "Laporkan ke Kurir"

## Fase 7 — Scan Paket *(PRD Bagian 10–13, 22–23)*

- [ ] Form input/scan nomor resi
- [ ] Matching: satu field `tracking_number` di DB menampung nilai dari kedua jalur, jadi cukup satu lookup
- [ ] Handle resi ditemukan → tampilkan data paket + tombol konfirmasi
- [ ] Handle resi tidak ditemukan → catat status Tidak Dikenali
- [ ] Handle scan ganda → tampilkan info penerimaan sebelumnya, jangan overwrite data
- [ ] Auto-clear input setelah scan sukses (scan cepat berturut-turut)
- [ ] Simpan ke `scan_histories`

## Fase 8 — Monitoring Otomatis & Status Engine *(PRD Bagian 14A, 15)*

- [ ] Function hitung `lama_retur = tanggal_hari_ini - tanggal_retur`
- [ ] Implementasi logic dasar sesuai urutan di Bagian 15 (pengecualian `refund_no_physical` dicek **paling awal**)
- [ ] Trigger recompute status: saat dashboard/halaman retur dibuka, saat refresh, setelah import baru
- [ ] Halaman "Perlu Diperiksa" dengan sorting (lama keterlambatan, lama tracking stagnan, tanggal retur, kurir)

## Fase 9 — Pelaporan Kurir *(PRD Bagian 18)*

- [ ] Form isi laporan (tanggal, nomor tiket, kurir, catatan, status investigasi)
- [ ] Simpan → update status jadi Dalam Investigasi

## Fase 10 — Penentuan Hilang *(PRD Bagian 19)*

- [ ] Form konfirmasi hilang (manual only, tidak ada auto-trigger by hari)
- [ ] Update status → Hilang, catat `lost_confirmed_at`

## Fase 11 — Riwayat Scan *(PRD Bagian 21)*

- [ ] Tabel riwayat: waktu, resi, hasil, user

## Fase 12 — Laporan *(PRD Bagian 24)*

- [ ] Filter: tanggal awal/akhir, status, kurir
- [ ] Ringkasan angka per status
- [ ] Export Excel & CSV
- [ ] Export PDF *(opsional, boleh dilewati di MVP)*

## Fase 13 — Pengaturan *(PRD Bagian 25)*

- [ ] Akun (nama, email, password)
- [ ] Batas waktu default & per wilayah (CRUD)
- [ ] Konfigurasi monitoring (hari terlambat, hari stagnan, batas pelaporan)
- [ ] Sistem (nama toko, format tanggal)

## Fase 14 — QA & Kalibrasi *(PRD Bagian 32)*

- [ ] Test import pakai data riil (file Jalur A & Jalur B sekaligus)
- [ ] Test semua edge case: refund only, resi kosong, resi duplikat, scan ganda
- [ ] Setelah 4–6 minggu live: tarik data `received_at - return_date` riil, kalibrasi ulang angka threshold per wilayah/kurir/jalur

---

## Backlog — Fase Pengembangan Selanjutnya *(PRD Bagian 29, di luar scope MVP)*

- [ ] Integrasi API TikTok Shop
- [ ] Integrasi API ekspedisi / tracking otomatis 24 jam
- [ ] Kamera scanner via HP
- [ ] Notifikasi WhatsApp
- [ ] Multi-user & role karyawan
- [ ] Upload foto bukti paket / paket bermasalah
- [ ] Statistik kurir & wilayah
