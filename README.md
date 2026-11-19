# Broseven Queue

Aplikasi manajemen antrean (Queue Management System) modern. Pengunjung mengambil nomor dari perangkat mereka, admin memanggil dan mengelola antrean, dan layar display menampilkan panggilan secara real-time lengkap dengan suara.

Dibangun dengan **Laravel 13**, **PHP 8.3**, **MySQL**, Blade, CSS manual, JavaScript murni, dan **Laravel Reverb** (WebSocket).

## Fitur

**Publik**
- Landing page dengan jumlah antrean menunggu dan nomor yang sedang dipanggil.
- Ambil nomor antrean tanpa login (nama, telepon, dan catatan bersifat opsional).
- Tiket antrean yang dapat dicetak: 58 mm, 80 mm (thermal), dan A4.
- Layar display untuk TV/monitor: nomor besar, animasi, jam digital, pesan berjalan, mode layar penuh, suara panggilan, dan polling cadangan jika WebSocket terputus.

**Admin**
- Dashboard: statistik hari ini, grafik harian/mingguan/bulanan, antrean terbaru, dan aktivitas terbaru.
- Master Antrean: tambah, ubah, hapus, aktif/nonaktif, dan atur urutan layanan; prefix otomatis, format nomor dinamis, periode reset, jam operasional, ikon/logo, dan warna layanan.
- Pengelolaan antrean: Panggil Berikutnya, Panggil Lagi, Skip, Panggil Ulang antrean yang di-skip, Selesaikan, riwayat panggilan, serta filter status dan tanggal.
- Manajemen user admin, pengaturan website (General, Contact, Social Media, Display, Ticket), pengaturan warna (simpan, reset, preview, peringatan kontras), dan profil admin.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3 atau lebih baru, Eloquent ORM |
| Database | MySQL 8 (atau MariaDB 10.3+) |
| Tampilan | Blade, HTML5, CSS3 manual, Bootstrap Icons (CDN), font Plus Jakarta Sans |
| Frontend | JavaScript murni (tanpa framework), Vite |
| Real-time | Laravel Reverb dan Laravel Echo |
| Suara | Web Speech API (browser) |

Tanpa Tailwind, AdminLTE, Vue, React, atau Inertia. Seluruh gaya ditulis sendiri.

## Persyaratan

- PHP 8.3+ dengan ekstensi `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `curl`, `xml`
- Composer 2
- Node.js 20+ dan npm
- MySQL 8 / MariaDB 10.3+

## Instalasi

```bash
# 1. Pasang dependensi
composer install
npm install

# 2. Konfigurasi lingkungan
copy .env.example .env          # Windows (Linux/macOS: cp .env.example .env)
php artisan key:generate
```

Buat database, lalu sesuaikan `.env`:

```sql
CREATE DATABASE broseven_queue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```env
APP_NAME="Broseven Queue"
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Makassar
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=broseven_queue
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
BROADCAST_CONNECTION=reverb
```

Variabel `REVERB_*` dan `VITE_REVERB_*` dibuat otomatis saat instalasi Reverb. Jika belum ada, jalankan `php artisan install:broadcasting`.

```bash
# 3. Buat tabel dan data awal
php artisan migrate --seed

# 4. Tautan folder unggahan
php artisan storage:link
```

`--seed` membuat akun admin, pengaturan website, palet warna default, dan **enam layanan contoh**. Untuk mengisi riwayat contoh agar grafik dashboard terisi (hanya untuk percobaan):

```bash
php artisan db:seed --class=DemoQueueSeeder
```

## Menjalankan (pengembangan)

Jalankan di tiga terminal terpisah:

```bash
php artisan serve              # aplikasi di http://127.0.0.1:8000
php artisan reverb:start --debug   # server WebSocket (port 8080)
npm run dev                    # Vite
```

Akun admin awal:

| | |
|---|---|
| Email | `admin@brosevenqueue.com` |
| Password | `password` |

> **Segera ganti password default** setelah login pertama (menu Profil Admin).

## Halaman utama

| URL | Fungsi |
|---|---|
| `/` | Beranda publik |
| `/ambil-antrean` | Ambil nomor antrean (`?layanan=slug` memilih layanan) |
| `/display` | Layar display; `?layanan=slug` menampilkan satu layanan saja |
| `/login` | Login admin |
| `/admin` | Dashboard admin |

## Cara pakai singkat

1. **Siapkan layanan**: Master Antrean > Item Antrean. Atur nama, prefix, format nomor, jumlah digit, dan kapan nomor di-reset.
2. **Atur identitas**: Pengaturan > Pengaturan Website (nama, logo, kontak, display, tiket) dan Pengaturan Warna.
3. **Pasang layar display**: buka `/display` di TV, klik **Mulai Layar Display** (satu klik diperlukan agar browser mengizinkan suara dan layar penuh). Gerakkan mouse untuk memunculkan tombol layar penuh (`F`) dan pengaturan suara (`S`).
4. **Layani antrean**: Item Antrean > **Kelola**. Isi nama loket, lalu **Panggil Berikutnya**. Gunakan Panggil Lagi, Skip, Selesaikan, atau Panggil Ulang dari daftar di-skip.

## Aturan antrean

- Nomor dibuat oleh server, berurutan per layanan dan periode reset, serta tidak pernah duplikat (kunci baris dan batasan unik di database).
- Hanya satu antrean aktif (`Sedang Dipanggil`) per layanan; Panggil Berikutnya ditolak jika masih ada antrean aktif.
- Skip tidak menghapus data; antrean yang di-skip dapat dipanggil ulang tanpa nomor baru.
- Antrean yang sudah selesai tidak dapat dipanggil lagi.
- Reset nomor tidak menghapus riwayat. Antrean menunggu dari periode sebelumnya ditandai kedaluwarsa dan ditutup dengan Skip.
- Seluruh waktu memakai zona waktu `Asia/Makassar`.
- Setiap aksi tercatat pada riwayat panggilan dan log aktivitas.

## Keamanan

- Autentikasi Laravel, middleware `auth` dan `admin` pada seluruh rute `/admin`, proteksi CSRF, dan password ter-hash.
- Pembatasan percobaan login, pengambilan nomor antrean, dan akses data display.
- Tiket antrean diakses lewat URL bertanda tangan sehingga ID tidak dapat diubah-ubah.
- Channel real-time bersifat publik dan hanya memuat nomor, layanan, loket, dan status. Data pribadi pengunjung tidak pernah dikirim.
- Unggahan dibatasi pada gambar PNG/JPG/WebP (favicon juga ICO), tanpa SVG, dengan batas ukuran dan nama file acak.
- Header keamanan (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS di HTTPS) dan halaman admin tidak disimpan di cache browser.

## Struktur proyek

```text
app/
├── Console/Commands/      Perintah artisan
├── Enums/                 QueueStatus, QueueAction, ResetPeriod
├── Events/                QueueCreated, QueueCalled, QueueRecalled, QueueSkipped, QueueCompleted
├── Http/
│   ├── Controllers/       Auth, Admin, Public
│   ├── Middleware/        EnsureAdminIsActive, SecurityHeaders
│   └── Requests/          Form Request (validasi)
├── Models/                User, QueueItem, Queue, QueueCallHistory,
│                          WebsiteSetting, ColorSetting, ActivityLog
├── Observers/             QueueObserver (menyiarkan perubahan antrean)
├── Services/              QueueNumberService, QueueManagementService,
│                          QueueOverviewService, DisplayDataService, DashboardStatsService
├── Support/               Contrast (perhitungan kontras warna)
└── View/Composers/        SiteComposer, AdminComposer

resources/
├── views/                 layouts, components, public, display, tickets,
│                          admin (dashboard, queue-items, users, settings), auth, errors
├── css/                   app, admin, public, display, auth, print
└── js/                    admin, public, display, queue-manage, dashboard,
                           queue-detail, queue-item-form, color-settings, ui, echo
```

## Pengujian

```bash
php artisan test
```

Tes memakai SQLite memori (lihat `phpunit.xml`; pastikan `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, dan `BROADCAST_CONNECTION=null` aktif) sehingga **tidak menyentuh** database aplikasi.

Pemeriksaan sintaks seluruh file PHP (PowerShell):

```powershell
Get-ChildItem app, database, routes, tests -Recurse -Filter *.php |
  ForEach-Object { php -l $_.FullName 2>&1 } |
  Where-Object { $_ -and $_ -notmatch 'No syntax errors|PHP Startup' }
```

## Menampilkan di TV atau perangkat lain (jaringan lokal)

```bash
php artisan serve --host=0.0.0.0
npm run build                   # gunakan aset hasil build, bukan npm run dev
```

- Buka dari perangkat lain: `http://<IP-komputer>:8000/display`.
- Izinkan port **8000** dan **8080** di firewall.
- `VITE_REVERB_HOST="localhost"` tidak perlu diubah; hostname halaman dipakai otomatis oleh `resources/js/echo.js`.

## Penyebaran ke produksi (ringkas)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

- Atur `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan gunakan HTTPS.
- Document root web server harus mengarah ke folder `public`.
- Pada instalasi pertama, jalankan seeder satu per satu (`AdminSeeder`, `WebsiteSettingSeeder`, `ColorSettingSeeder`) dan **jangan** menjalankan `QueueItemSeeder`/`DemoQueueSeeder` (data contoh).
- Jalankan `php artisan reverb:start` sebagai layanan (Supervisor di Linux, NSSM di Windows), dan teruskan WebSocket lewat reverse proxy.
- Ganti password admin default dan gunakan kunci Reverb baru.

## Pemecahan masalah

| Masalah | Penyebab dan solusi |
|---|---|
| `Vite manifest not found` | Jalankan `npm run dev` (pengembangan) atau `npm run build`. |
| Display tampil "Mode cadangan (polling)" | Server Reverb belum berjalan: `php artisan reverb:start`. Display tetap berfungsi lewat polling. |
| Suara tidak terdengar | Klik **Mulai Layar Display** dahulu, periksa volume di panel suara (`S`), dan pastikan voice bahasa Indonesia terpasang di sistem operasi (Windows: Settings > Time & language > Speech). |
| Perubahan `.env` atau `echo.js` tidak berlaku | Hentikan lalu jalankan ulang `npm run dev`, dan `php artisan optimize:clear`. |
| Peringatan `Unable to load dynamic library ...` | Tidak berbahaya. Beberapa ekstensi di `php.ini` sudah bawaan PHP; beri tanda `;` pada baris `extension=` yang bersangkutan. |
| Gambar logo tidak tampil | Jalankan `php artisan storage:link`. |

## Lisensi

Hak cipta BroSeven! Developer.
