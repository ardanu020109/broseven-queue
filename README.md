<div align="center">
Broseven Queue
Modern Queue Management System
Aplikasi manajemen antrean berbasis web untuk membantu pengelolaan layanan, penerbitan nomor antrean, pemanggilan pelanggan, dan pemantauan antrean secara real-time.
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-blue.svg)
Repository: github.com/ardanu020109/broseven-queue
</div>
---
Tentang Broseven Queue
Broseven Queue adalah aplikasi Queue Management System yang dibangun menggunakan Laravel 13. Aplikasi ini dirancang untuk mempermudah proses pengambilan dan pengelolaan nomor antrean, mulai dari pengunjung memilih layanan hingga petugas memanggil, melewati, memanggil ulang, atau menyelesaikan antrean.
Aplikasi menyediakan halaman publik untuk pengunjung, halaman display antrean yang dapat ditampilkan pada monitor/TV, serta dashboard admin untuk mengelola layanan dan operasional antrean.
Fitur
Halaman publik
Landing page informasi aplikasi.
Melihat layanan antrean yang tersedia.
Mengambil nomor antrean tanpa perlu login.
Mengisi informasi pengunjung yang disediakan pada formulir.
Melihat dan mencetak tiket antrean.
Membuka display antrean.
Dashboard dan autentikasi admin
Login dan logout admin.
Dashboard ringkasan antrean.
Halaman admin dilindungi autentikasi dan middleware admin aktif.
Pengelolaan profil admin.
Manajemen akun admin.
Master dan pengelolaan antrean
Menambah, mengubah, dan menghapus item antrean.
Mengaktifkan atau menonaktifkan layanan.
Mengatur urutan layanan.
Memanggil antrean berikutnya.
Memanggil ulang antrean aktif.
Memanggil antrean tertentu.
Melewati (skip) antrean.
Memanggil kembali antrean yang di-skip.
Menyelesaikan antrean.
Display dan konfigurasi
Halaman display antrean publik.
Endpoint data display.
Broadcasting menggunakan Laravel Reverb.
Pengaturan website.
Pengaturan warna aplikasi.
> Catatan: daftar di atas merangkum tujuan aplikasi dan bagian yang terlihat pada struktur repository. Detail perilaku, konfigurasi, serta fitur yang tersedia dapat berubah mengikuti versi kode pada branch `main`.
Teknologi
Teknologi	Kegunaan
PHP 8.3 atau lebih baru	Bahasa pemrograman backend
Laravel 13	Framework aplikasi
MySQL	Database
Blade	Template halaman
HTML5 & CSS3	Struktur dan styling antarmuka
Vanilla JavaScript	Interaksi pada sisi browser
Vite	Build asset frontend
Laravel Reverb	WebSocket/broadcasting real-time
Laravel Echo	Menerima event broadcasting di browser
Pusher JS	Transport WebSocket untuk Echo
Bootstrap Icons	Ikon antarmuka (bila digunakan pada halaman)
Browser Print API	Mencetak tiket antrean
Tampilan aplikasi
Tambahkan screenshot aktual aplikasi pada folder `docs/images/`, lalu ubah path gambar di bawah sesuai nama file screenshot.
Beranda	Dashboard Admin
![Beranda](docs/images/home.png)	![Dashboard](docs/images/dashboard.png)

Pengambilan Antrean	Display Antrean
![Ambil Antrean](docs/images/take-queue.png)	![Display Antrean](docs/images/display.png)
Persyaratan sistem
Pastikan perangkat pengembangan sudah memiliki:
PHP 8.3+.
Composer.
Node.js dan NPM.
MySQL.
Ekstensi PHP yang dibutuhkan Laravel dan driver MySQL (`pdo_mysql`).
Git.
Instalasi
1. Clone repository
```bash
git clone https://github.com/ardanu020109/broseven-queue.git
cd broseven-queue
```
2. Install dependency PHP dan JavaScript
```bash
composer install
npm install
```
3. Siapkan file environment
Windows PowerShell:
```powershell
Copy-Item .env.example .env
```
Linux/macOS:
```bash
cp .env.example .env
```
Buat application key:
```bash
php artisan key:generate
```
4. Buat database MySQL
Jalankan perintah berikut melalui MySQL:
```sql
CREATE DATABASE broseven_queue
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```
5. Atur koneksi database
Sesuaikan konfigurasi berikut di file `.env`:
```env
APP_NAME="Broseven Queue"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Makassar
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=broseven_queue
DB_USERNAME=root
DB_PASSWORD=
```
Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan konfigurasi MySQL lokal.
6. Jalankan migration dan seeder
```bash
php artisan migrate
php artisan db:seed
```
Jika ingin menjalankan migration sekaligus seeder pada database pengembangan yang masih kosong:
```bash
php artisan migrate:fresh --seed
```
Peringatan: `migrate:fresh` akan menghapus seluruh tabel beserta datanya. Jangan jalankan pada database yang berisi data penting.
Jika seeder membuat akun admin awal, periksa `database/seeders` untuk mengetahui email dan password yang benar. Jangan mengasumsikan kredensial default tanpa memeriksa kode seeder.
7. Buat symbolic link storage
```bash
php artisan storage:link
```
8. Build asset frontend
Untuk development:
```bash
npm run dev
```
Untuk build production:
```bash
npm run build
```
9. Jalankan aplikasi
Buka terminal baru:
```bash
php artisan serve
```
Akses aplikasi melalui:
```text
http://127.0.0.1:8000
```
Menjalankan fitur real-time (Laravel Reverb)
Pastikan konfigurasi broadcasting pada `.env` menggunakan Reverb. Contoh konfigurasi lokal:
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```
Gunakan nilai kredensial Reverb milik environment sendiri. Jangan menggunakan atau mengunggah secret dari environment orang lain.
Jalankan server Reverb pada terminal terpisah:
```bash
php artisan reverb:start
```
Jika aplikasi menggunakan Vite untuk Echo, jalankan juga:
```bash
npm run dev
```
Dengan demikian, proses development minimal berjalan pada terminal terpisah untuk Laravel, Reverb, dan Vite. Jika konfigurasi project menggunakan queue worker untuk pekerjaan asynchronous, jalankan worker:
```bash
php artisan queue:work
```
Perintah pengembangan
Perintah	Keterangan
`php artisan serve`	Menjalankan server Laravel
`php artisan migrate`	Menjalankan migration
`php artisan db:seed`	Menjalankan database seeder
`php artisan storage:link`	Membuat symbolic link storage publik
`php artisan reverb:start`	Menjalankan server Reverb
`php artisan queue:work`	Menjalankan queue worker
`npm run dev`	Menjalankan Vite development server
`npm run build`	Build asset untuk production
`php artisan route:list`	Melihat daftar route
`php artisan test`	Menjalankan automated test
Struktur direktori
Berikut gambaran direktori utama Laravel pada repository:
```text
broseven-queue/
├── app/
│   ├── Events/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Auth/
│   │   │   └── Public/
│   │   └── Middleware/
│   ├── Models/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── channels.php
│   ├── console.php
│   └── web.php
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```
Route utama
Route publik yang tersedia pada aplikasi:
Method	URL	Keterangan
GET	`/`	Beranda
GET	`/ambil-antrean`	Form pengambilan antrean
POST	`/ambil-antrean`	Membuat antrean
GET	`/antrean/tiket/{queue}`	Menampilkan tiket antrean
GET	`/display`	Display antrean
GET	`/display/data`	Data untuk display
GET	`/login`	Halaman login admin
POST	`/login`	Proses login
POST	`/logout`	Logout
Route admin menggunakan prefix `/admin` dan middleware autentikasi serta middleware admin. Beberapa kelompok route admin meliputi dashboard, profil, item antrean, pengelolaan antrean, manajemen user, pengaturan website, dan pengaturan warna.
Untuk melihat seluruh route sesuai kode yang sedang digunakan:
```bash
php artisan route:list
```
Keamanan
Jangan mengunggah file `.env` ke repository.
Jangan membagikan `APP_KEY`, password database, atau secret Reverb.
Gunakan password admin yang kuat.
Nonaktifkan debug pada environment production (`APP_DEBUG=false`).
Gunakan HTTPS pada deployment.
Lakukan backup database secara berkala.
Jangan menjalankan `migrate:fresh` pada database production.
Penting untuk maintainer: pastikan `.env.example` hanya berisi placeholder. Jika secret asli pernah terlanjur masuk ke Git, hapus dari file, rotasi secret tersebut, dan pertimbangkan riwayat Git karena menghapusnya dari commit terbaru tidak menghilangkan salinan pada commit sebelumnya.
Testing
Jalankan test aplikasi dengan:
```bash
php artisan test
```
Sebelum deployment, lakukan pengujian alur pengambilan antrean, pemanggilan antrean, pemanggilan ulang, skip, restore, penyelesaian antrean, pencetakan tiket, serta pembaruan display real-time.
Kontribusi
Kontribusi, saran, dan pelaporan bug sangat terbuka. Silakan buat Issue untuk melaporkan masalah atau Pull Request untuk mengusulkan perubahan.
Fork repository.
Buat branch fitur: `git checkout -b feature/nama-fitur`.
Commit perubahan: `git commit -m "Add: nama fitur"`.
Push branch: `git push origin feature/nama-fitur`.
Buat Pull Request.
Lisensi
Lisensi project perlu mengikuti file `LICENSE` yang disertakan pada repository. Jika belum tersedia, tentukan lisensi project sebelum mendistribusikan aplikasi.
---
<div align="center">
Broseven Queue  
Manage queues. Improve service.
Developed by Ardanu
</div>