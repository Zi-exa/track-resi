# Product Requirements Document (PRD)
## Sistem Tracking Retur Emza Store

> **Revisi:** Ditambahkan setelah analisis data ekspor Seller Center TikTok Shop asli (Juli–September 2026). Perubahan utama: pemisahan dua jalur retur berbeda sumber data, status baru untuk refund tanpa retur fisik, dan mapping kolom import berdasarkan struktur file ekspor sebenarnya. Lihat Bagian 7 dan 14A.

## 1. Nama Produk

**ReturnTrack – Sistem Tracking Retur Emza Store**

---

## 2. Deskripsi Produk

ReturnTrack adalah aplikasi berbasis web untuk mencatat, memantau, dan memverifikasi paket retur TikTok Shop pada Emza Store.

Sistem membantu pengguna mengetahui:

- Paket retur yang sudah sampai.
- Paket retur yang belum sampai.
- Paket retur yang terlambat.
- Paket retur yang perlu diperiksa.
- Paket retur yang perlu dilaporkan ke kurir.
- Paket yang sudah dilaporkan dan sedang dalam investigasi.
- Paket yang dikonfirmasi hilang.
- Paket yang nomor resinya tidak dikenali.
- Riwayat scan paket yang masuk.

Data retur dimasukkan melalui file hasil ekspor TikTok Shop. Ketika paket tiba di toko, pengguna dapat melakukan scan barcode atau memasukkan nomor resi secara manual.

---

## 3. Pengguna Sistem

### Admin / Pemilik Toko

Admin dapat:

- Login ke sistem.
- Import data retur.
- Melihat dashboard.
- Melihat daftar retur.
- Scan barcode paket.
- Input nomor resi manual.
- Melihat detail retur.
- Melihat paket yang belum diterima.
- Melihat paket yang terlambat.
- Melihat paket yang perlu diperiksa.
- Menandai paket sudah dilaporkan ke kurir.
- Menyimpan nomor laporan/tiket kurir.
- Melihat riwayat scan.
- Mengatur batas waktu retur.
- Melihat laporan.

---

## 4. Modul Sistem

Sistem terdiri dari:

1. Authentication
2. Dashboard
3. Import Data
4. Data Retur
5. Scan Paket
6. Monitoring Retur
7. Pelaporan Kurir
8. Riwayat Scan
9. Laporan
10. Pengaturan

---

## 5. Authentication

### Login

Input:

- Email / username
- Password

Fungsi:

- Login
- Logout
- Session login
- Password hash

---

## 6. Dashboard

Dashboard menjadi halaman utama setelah login.

### Card Dashboard

Tampilkan:

- Total Retur
- Sudah Diterima
- Belum Diterima
- Terlambat
- Perlu Diperiksa
- Perlu Dilaporkan
- Dalam Investigasi
- Hilang

Contoh:

| Informasi | Jumlah |
|---|---:|
| Total Retur | 245 |
| Sudah Diterima | 190 |
| Belum Diterima | 35 |
| Terlambat | 10 |
| Perlu Diperiksa | 5 |
| Perlu Dilaporkan | 3 |
| Dalam Investigasi | 1 |
| Hilang | 1 |

### Retur Terbaru

Tampilkan:

| Resi | Produk | Tanggal Retur | Status |
|---|---|---|---|
| JX001 | Training XL | 19 Sep | Sudah Diterima |
| SP002 | Cargo L | 19 Sep | Belum Diterima |

---

## 7. Import Data Retur

Menu:

**Import Data**

Format file:

- `.xlsx`
- `.csv`

### Dua Jalur Retur & Dua Sumber File

Berdasarkan struktur ekspor Seller Center TikTok Shop, retur di Emza Store berasal dari **dua jalur yang berbeda total sumber datanya** — bukan satu file gabungan:

**Jalur A — Gagal Kirim (paket batal sebelum sampai ke pembeli)**

- Sumber file: ekspor **Pesanan Dibatalkan** (`Order Status = Dibatalkan`, `Cancelation/Return Type = Cancel`)
- Nomor resi yang dipantau: `Tracking ID` — resi pengiriman **awal**, karena paket balik lewat jalur yang sama
- Prefix resi teramati: **JY** (J&T Express)
- Kurir & wilayah tersedia langsung di file ini (kolom `Shipping Provider Name`, `Province`, `Regency and City`, dst)
- Penyebab: ditolak pembeli, alamat tidak ditemukan, rumah kosong, paket hilang di jalan — semua tercatat generik sebagai `Cancel Reason: Pengiriman paket gagal`, `Cancel By: System` (data tidak membedakan sub-alasannya)

**Jalur B — Refund Setelah Diterima (barang sudah sampai, lalu diretur)**

- Sumber file: ekspor **Pesanan yang Barang/Dananya Dikembalikan** (return-specific)
- Nomor resi yang dipantau: `Return Logistics Tracking ID` — resi **baru**, khusus jalur retur, dikirim oleh pembeli
- Prefix resi teramati: **JX** (J&T Express)
- Kurir & wilayah **tidak tersedia** di file ini — untuk mendapatkannya sistem perlu join ke ekspor **Semua Pesanan (unfiltered/termasuk Selesai)**, bukan ke ekspor Pesanan Dibatalkan, karena kedua kategori itu **saling eksklusif** (order yang dibatalkan tidak pernah muncul di data refund, dan sebaliknya) — join Order ID antara Jalur A dan Jalur B tidak akan pernah menghasilkan match.

**Jalur B1 — Refund Tanpa Retur Fisik (sub-kasus penting)**

Sebagian baris di Jalur B punya `Return Type = Refund only` — dana dikembalikan **tanpa** pembeli perlu mengirim barang balik sama sekali (misal refund kriteria sampel/garansi). Baris ini **tidak punya `Return Logistics Tracking ID`**. Karena tidak akan pernah ada paket fisik yang datang, baris ini **harus dikeluarkan dari alur monitoring & scan** sejak saat import — lihat Bagian 14A dan 15.

Sistem perlu mengimpor **dua file secara terpisah** (Jalur A dan Jalur B), bukan berasumsi satu file mencakup semua retur.

### Frekuensi Import

Import disarankan dilakukan:

**Setiap hari atau setiap ada pembaruan data retur dari TikTok Shop.**

Import mingguan hanya digunakan untuk rekap, bukan sebagai sumber utama monitoring.

### Flow Import

```text
Upload File
    ↓
Preview Data
    ↓
Mapping Kolom
    ↓
Validasi
    ↓
Import
    ↓
Database
```

### Data yang Dibutuhkan

Mapping ke kolom asli pada tiap file ekspor:

| Field ReturnTrack | Kolom di file Jalur A (Dibatalkan) | Kolom di file Jalur B (Refund) |
|---|---|---|
| Nomor pesanan | `Order ID` | `Order ID` |
| Nomor resi | `Tracking ID` | `Return Logistics Tracking ID` (kosong jika Refund only) |
| Nama produk | `Product Name` | `Product Name` |
| Variasi | `Variation` | `SKU Name` |
| Jumlah | `Quantity` | `Return Quantity` |
| Tanggal retur | `Cancelled Time` | `Time Requested` |
| Kurir | `Shipping Provider Name` | *(tidak tersedia — lihat Bagian 7)* |
| Wilayah | `Province` / `Regency and City` | *(tidak tersedia — lihat Bagian 7)* |
| Alasan | `Cancel Reason` | `Return Reason` |
| Tipe retur (TikTok) | `Cancelation/Return Type` | `Return Type` (Return and refund / Refund only) |
| Status retur TikTok | `Order Status` | `Return Status` / `Return Sub Status` |

### Validasi Import

Sistem memeriksa:

- Nomor pesanan kosong
- Nomor resi kosong — **kecuali** baris dengan `Return Type = Refund only`, yang secara sah memang tidak punya resi (lihat Bagian 7, Jalur B1) — baris ini ditandai `SELESAI TANPA RETUR FISIK`, bukan error
- Format tanggal salah — TikTok mengekspor tanggal dengan format **DD/MM/YYYY HH:mm:ss** (parser harus eksplisit set format ini, jangan auto-detect, karena ambigu dengan MM/DD)
- Data duplikat
- **Whitespace/tab tersisa di akhir field** — kolom ID panjang (Order ID, Return Order ID, SKU ID) pada ekspor TikTok punya karakter tab menempel di belakang nilainya (trik agar Excel tidak mengonversi ke scientific notation). Setiap field wajib di-`trim()` sebelum disimpan atau dicocokkan.
- **Tipe kolom ID harus VARCHAR, bukan INTEGER** — Order ID dan SKU ID berupa angka 18–19 digit yang bisa overflow atau salah presisi jika disimpan sebagai integer/bigint biasa.
- Nominal uang (`Order Amount`, dll) berformat `Rp38.824` — perlu strip prefix `Rp` dan titik ribuan sebelum disimpan sebagai angka.

Jika nomor resi sudah ada, data tidak dimasukkan ulang.

---

## 8. Data Retur

Halaman:

**Retur**

Tampilkan tabel:

| Resi | Order ID | Produk | Tanggal Retur | Lama | Status |
|---|---|---|---|---:|---|
| JX001 | 12345 | Cargo L | 15 Sep | 4 hari | Belum Diterima |
| JX002 | 12346 | Training XL | 10 Sep | 9 hari | Terlambat |

### Search

User dapat mencari berdasarkan:

- Nomor resi
- Order ID
- Produk

### Filter

- Semua
- Belum Diterima
- Sudah Diterima
- Terlambat
- Perlu Diperiksa
- Perlu Dilaporkan
- Dalam Investigasi
- Hilang
- Tidak Dikenali

Tambahan filter:

- Kurir
- Tanggal
- Wilayah
- Jalur Retur (Gagal Kirim / Refund Setelah Diterima / Selesai Tanpa Retur Fisik) — default menyembunyikan "Selesai Tanpa Retur Fisik" kecuali dipilih eksplisit

---

## 9. Detail Retur

Halaman detail menampilkan:

### Informasi Pesanan

- Nomor pesanan
- Produk
- Variasi
- Jumlah

### Informasi Retur

- Nomor resi
- Kurir
- Tanggal retur
- Wilayah
- Lama perjalanan

### Monitoring

- Batas waktu
- Jumlah hari berjalan
- Status
- Tanggal update terakhir

### Informasi Penerimaan

Jika diterima:

- Status: Sudah Diterima
- Tanggal diterima
- Jam diterima
- User yang melakukan scan

### Informasi Pelaporan Kurir

Jika sudah dilaporkan:

- Tanggal laporan
- Nomor tiket/laporan
- Catatan
- Status investigasi

---

## 10. Scan Paket

Menu:

**Scan Paket**

Input:

```text
Scan / Masukkan Nomor Resi

[________________________]

[ Cari ]
```

Barcode scanner USB dapat digunakan karena terbaca sebagai input keyboard.

---

## 11. Flow Scan

```text
Scan Barcode
    ↓
Ambil Nomor Resi
    ↓
Cari Database
    ↓
┌───────────────┐
│               │
Ada          Tidak Ada
│               │
↓               ↓
Tampilkan     Resi Tidak
Data Paket    Dikenali
│
↓
Konfirmasi
Paket Diterima
│
↓
Status:
Sudah Diterima
```

---

## 12. Jika Resi Ditemukan

Tampilkan:

```text
PAKET DITEMUKAN

Resi:
JX123456789

Order:
57638482910

Produk:
Cargo Pendek

Variasi:
XL Hitam

[Konfirmasi Diterima]
```

Setelah dikonfirmasi:

```text
✓ Paket berhasil diterima
```

Sistem menyimpan:

- Tanggal diterima
- Waktu scan
- User
- Status

---

## 13. Jika Resi Tidak Ditemukan

Tampilkan:

```text
⚠ RESI TIDAK DITEMUKAN

JX999999999
```

Pilihan:

- Scan Ulang
- Catat Paket

Jika dicatat:

**Status: Tidak Dikenali**

---

## 14. Status Retur

### BELUM DITERIMA

Data retur sudah masuk dan masih dalam batas waktu normal.

### SUDAH DITERIMA

Paket sudah sampai dan diverifikasi melalui scan barcode atau input nomor resi.

### TERLAMBAT

Paket melewati estimasi normal, tetapi belum cukup kuat untuk dilaporkan.

### PERLU DIPERIKSA

Paket belum diterima dan perlu dilakukan pengecekan tracking/resi.

### PERLU DILAPORKAN

Paket belum diterima, sudah melewati batas waktu, dan terdapat indikasi tracking stagnan atau tidak ada perkembangan.

### DALAM INVESTIGASI

Paket sudah dilaporkan ke pihak kurir dan sedang ditelusuri.

### HILANG

Status hanya digunakan apabila pihak kurir mengonfirmasi kehilangan atau proses klaim kehilangan dilakukan.

### TIDAK DIKENALI

Paket fisik datang, tetapi nomor resinya tidak terdapat pada data retur.

---

## 14A. Status Tambahan: Di Luar Alur Monitoring Fisik

### SELESAI TANPA RETUR FISIK

Diberikan otomatis saat import untuk baris dengan `Return Type = Refund only` (dana dikembalikan tanpa pembeli perlu mengirim barang balik). Status ini **final sejak awal** — tidak pernah masuk hitungan `lama_retur`, tidak pernah dievaluasi jadi Terlambat/Perlu Diperiksa/Perlu Dilaporkan, dan tidak akan pernah cocok dengan scan barcode karena memang tidak ada nomor resi maupun paket fisik yang menyertainya.

Halaman **Retur** dan **Perlu Diperiksa** secara default menyembunyikan status ini dari tampilan (bisa dimunculkan lewat filter khusus), supaya tidak mengotori daftar paket yang benar-benar perlu dipantau.

---

## 15. Monitoring Otomatis

Sistem menghitung:

```text
lama_retur =
tanggal_hari_ini - tanggal_retur
```

Status dihitung ulang ketika:

- Dashboard dibuka
- Halaman retur dibuka
- Halaman di-refresh
- Selesai melakukan import data baru

Sistem **tidak perlu aktif 24 jam** untuk menghitung status berbasis tanggal.

### Logic Dasar

```text
IF return_type = Refund only (tidak ada retur fisik)
    status = SELESAI TANPA RETUR FISIK
    (berhenti di sini, tidak masuk hitungan lama_retur)

ELSE IF paket sudah diterima
    status = SUDAH DITERIMA

ELSE IF paket sudah dikonfirmasi hilang
    status = HILANG

ELSE IF paket sudah dilaporkan ke kurir
    status = DALAM INVESTIGASI

ELSE IF lama_retur > batas_waktu AND memenuhi syarat laporan
    status = PERLU DILAPORKAN

ELSE IF lama_retur > batas_waktu
    status = PERLU DIPERIKSA

ELSE IF mendekati/melewati estimasi normal
    status = TERLAMBAT

ELSE
    status = BELUM DITERIMA
```

---

## 16. Batas Waktu Retur

Admin dapat mengatur batas waktu berdasarkan wilayah.

Contoh:

| Wilayah | Batas |
|---|---:|
| Jawa | 7 hari |
| Sumatera | 10 hari |
| Kalimantan | 12 hari |
| Sulawesi | 12 hari |
| Bali / Nusa Tenggara | 12 hari |
| Papua / Maluku | 14 hari |
| Default | 10 hari |

Semua nilai dapat diedit dari menu pengaturan.

---

## 17. Logic Pelaporan ke Kurir

Paket tidak langsung dianggap hilang hanya karena melewati batas waktu.

Alur:

```text
Dalam Perjalanan
    ↓
Terlambat
    ↓
Perlu Diperiksa
    ↓
Perlu Dilaporkan
    ↓
Dalam Investigasi
    ↓
Hilang / Selesai
```

### Indikator Perlu Dilaporkan

Paket dapat masuk status **Perlu Dilaporkan** jika:

- Belum diterima
- Sudah melewati batas waktu
- Tracking tidak berubah dalam beberapa hari
- Tracking menunjukkan paket tertahan
- Tidak ada perkembangan pengiriman

Jika data tracking otomatis belum tersedia, admin dapat melakukan pengecekan manual ke situs/aplikasi kurir dan mengubah status menjadi **Perlu Dilaporkan**.

---

## 18. Pelaporan Kurir

Pada halaman detail retur tersedia tombol:

**Laporkan ke Kurir**

Setelah ditekan, user mengisi:

- Tanggal laporan
- Nomor tiket/laporan
- Nama kurir
- Catatan
- Status investigasi

Setelah disimpan:

**Status → Dalam Investigasi**

---

## 19. Penentuan Paket Hilang

Sistem **tidak menentukan paket hilang secara otomatis berdasarkan jumlah hari**.

Paket hanya dapat diberi status:

**Hilang**

jika:

- Kurir memberikan konfirmasi kehilangan
- Proses klaim kehilangan telah dimulai/disetujui
- Admin memberikan konfirmasi berdasarkan hasil investigasi kurir

---

## 20. Prioritas Pemeriksaan

Halaman:

**Perlu Diperiksa**

Data dapat diurutkan berdasarkan:

- Lama keterlambatan
- Lama tracking stagnan
- Tanggal retur
- Kurir

Contoh:

| Resi | Terlambat | Status |
|---|---:|---|
| JX001 | +12 hari | Perlu Dilaporkan |
| JX002 | +8 hari | Perlu Diperiksa |
| JX003 | +3 hari | Terlambat |

---

## 21. Riwayat Scan

Menu:

**Riwayat Scan**

Tabel:

| Waktu | Resi | Hasil | User |
|---|---|---|---|
| 15:21 | JX001 | Diterima | Admin |
| 15:23 | JX002 | Diterima | Admin |
| 15:25 | JX999 | Tidak Dikenali | Admin |

---

## 22. Scan Cepat

Setelah scan berhasil:

```text
✓ JX123456789 berhasil diterima.

Scan paket berikutnya:

[________________________]
```

Input otomatis dikosongkan agar dapat melakukan scan berturut-turut.

---

## 23. Pencegahan Scan Ganda

Jika resi yang sama discan lagi:

```text
⚠ Paket sudah pernah diterima.

Tanggal:
18 September 2026

Jam:
14:32
```

Sistem tidak mengubah data penerimaan sebelumnya.

---

## 24. Laporan

Menu:

**Laporan**

Filter:

- Tanggal awal
- Tanggal akhir
- Status
- Kurir

Ringkasan:

- Total retur
- Sudah diterima
- Belum diterima
- Terlambat
- Perlu diperiksa
- Perlu dilaporkan
- Dalam investigasi
- Hilang

Export:

- Excel
- CSV
- PDF (opsional)

---

## 25. Pengaturan

### Akun

- Nama
- Email
- Password

### Retur

- Batas waktu default

### Wilayah

- Nama wilayah
- Batas waktu

### Monitoring

- Jumlah hari sebelum dianggap terlambat
- Jumlah hari tracking stagnan
- Batas waktu pelaporan

### Sistem

- Nama toko
- Format tanggal

---

## 26. Struktur Menu

```text
RETURNTRACK

Dashboard

Import Data

Retur

Scan Paket

Perlu Diperiksa

Pelaporan Kurir

Riwayat Scan

Laporan

Pengaturan

Logout
```

---

## 27. Struktur Database

### users

```text
id
name
email
password
created_at
updated_at
```

### orders

```text
id
order_number
product_name
variation
quantity
order_date
created_at
updated_at
```

### returns

```text
id
order_id
tracking_number          -- nullable: kosong utk Refund only (Jalur B1)
return_source             -- enum: gagal_kirim | refund_delivered | refund_no_physical
requires_physical_return  -- boolean, false utk Jalur B1
courier                   -- nullable jika return_source = refund_delivered (lihat Bagian 7)
return_date
region                     -- nullable jika return_source = refund_delivered
tiktok_return_type        -- Return and refund / Refund only / Cancel (nilai asli dari TikTok)
tiktok_status              -- Order Status / Return Status asli dari TikTok, referensi saja
return_reason
status
received_at
last_tracking_update
reported_at
lost_confirmed_at
created_at
updated_at
```

### scan_histories

```text
id
return_id
tracking_number
scan_time
result
user_id
```

### courier_reports

```text
id
return_id
courier
report_date
ticket_number
notes
investigation_status
created_at
updated_at
```

### return_deadlines

```text
id
region
maximum_days
```

### imports

```text
id
filename
total_rows
success_rows
failed_rows
imported_at
user_id
```

---

## 28. Fitur MVP

Versi awal sistem memiliki:

- Login
- Dashboard
- Import Excel/CSV
- Data retur
- Detail retur
- Scan barcode
- Input resi manual
- Pencocokan resi
- Konfirmasi penerimaan
- Status belum diterima
- Status sudah diterima
- Status terlambat
- Status perlu diperiksa
- Status perlu dilaporkan
- Status dalam investigasi
- Status hilang
- Batas waktu berdasarkan wilayah
- Search dan filter
- Riwayat scan
- Pelaporan kurir
- Update status otomatis saat sistem dibuka

---

## 29. Fitur Pengembangan Selanjutnya

- API TikTok Shop
- API ekspedisi
- Tracking otomatis 24 jam
- Kamera scanner HP
- Notifikasi WhatsApp
- Multi-user
- Role karyawan
- Foto bukti paket
- Upload foto paket bermasalah
- Statistik kurir
- Statistik wilayah

---

## 30. Tech Stack

### Backend

**Laravel**

### Frontend

- Blade
- Tailwind CSS / Bootstrap
- JavaScript

### Database

**MySQL**

### Import Excel

**Laravel Excel**

### Barcode

Barcode scanner USB.

---

## 31. Alur Utama Sistem

```text
LOGIN
  ↓
DASHBOARD
  ↓
IMPORT DATA RETUR
  ↓
DATA MASUK DATABASE
  ↓
MONITORING OTOMATIS
  ↓
PAKET RETUR DATANG
  ↓
SCAN BARCODE
  ↓
COCOKKAN RESI
  ↓
┌───────────────────┐
│                   │
ADA               TIDAK ADA
│                   │
↓                   ↓
DETAIL            TIDAK
RETUR             DIKENALI
│
↓
KONFIRMASI
│
↓
SUDAH DITERIMA
```

Monitoring paket yang belum sampai:

```text
BELUM DITERIMA
    ↓
TERLAMBAT
    ↓
PERLU DIPERIKSA
    ↓
PERLU DILAPORKAN
    ↓
DALAM INVESTIGASI
    ↓
┌──────────────┐
│              │
DITEMUKAN     HILANG
│              │
↓              ↓
DITERIMA     SELESAI /
            PROSES KLAIM
```

---

## 32. Catatan Implementasi

Untuk MVP, sistem tidak perlu berjalan 24 jam.

Status berbasis waktu dihitung ulang ketika user membuka atau me-refresh aplikasi.

Integrasi tracking kurir otomatis dapat ditambahkan pada versi lanjutan jika tersedia API yang sesuai.

### Kalibrasi Angka Threshold (Bagian 15, 16, 17)

Ekspor Seller Center TikTok **tidak memiliki field "tanggal paket sampai di gudang seller"** — field itu murni akan dihasilkan oleh ReturnTrack sendiri lewat scan barcode setelah sistem berjalan. Akibatnya, angka-angka ambang batas (kapan status jadi Terlambat, berapa hari dianggap Perlu Diperiksa, berapa hari tracking stagnan sebelum Perlu Dilaporkan) **tidak bisa dihitung dari data historis TikTok** — kolom seperti `Time Requested` → `Refund Time` hanya mencerminkan kecepatan proses refund di platform (rata-rata di bawah 1 hari pada sampel), bukan waktu tempuh fisik paket.

Pendekatan yang disarankan: mulai dengan angka default di Bagian 16 (berbasis estimasi umum per wilayah), lalu setelah 4–6 minggu berjalan dan data scan riil terkumpul, kalibrasi ulang angkanya berdasarkan rata-rata `received_at - return_date` yang sebenarnya per wilayah/kurir/jalur retur.
