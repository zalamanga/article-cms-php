# Article CMS PHP

Demo CMS artikel PHP dan MySQL dengan halaman pembaca serta pengelolaan artikel oleh admin.

## Yang tersedia

- Daftar artikel terbit, detail artikel, kategori, dan jumlah pembacaan.
- Login admin dengan password hash dan timeout sesi.
- Form artikel, status draft/terbit, gambar utama, dan konten editor.

## Menjalankan demo

PHP 8 dengan ekstensi PDO MySQL, mbstring, DOM/XML, dan GD untuk pengolahan gambar, serta MySQL/MariaDB. Proyek ini tidak membutuhkan Composer.

1. Buat database demo MySQL/MariaDB dengan charset utf8mb4.
2. Impor `database/schema.sql` melalui Adminer/phpMyAdmin atau klien MySQL. Skema tidak berisi akun atau data operasional.
3. Salin `.env.example` menjadi `.env`, lalu isi DB_NAME, DB_USER, DB_PASSWORD, dan alamat database lokal.
4. Dari root proyek, jalankan `php -S 127.0.0.1:8000` dan buka `http://127.0.0.1:8000`. Server bawaan PHP hanya untuk latihan lokal.
5. Isi ADMIN_USERNAME dan ADMIN_PASSWORD pada environment lokal, jalankan `php tools/create-admin.php`, lalu kosongkan ADMIN_PASSWORD setelah berhasil. Login menggunakan akun yang baru dibuat.

## Struktur yang perlu dibaca

`config.php` atau `db.php` membuka koneksi database; `bootstrap_env.php` membaca konfigurasi lokal; `database/schema.sql` mendefinisikan tabel kosong. Halaman PHP berisi alur aplikasi dan HTML yang masih mengikuti struktur legacy.

## Batas implementasi

- Terdapat dua varian form artikel legacy dengan nama kolom author/publish_date dan author_name/published_date. Skema demo menampung keduanya, tetapi sinkronisasi tampilan antarform belum diuji.
- Penghapusan GET, sanitasi HTML editor, CSRF, dan validasi unggahan memerlukan peninjauan lanjutan. Publikasi ini mendokumentasikan prototype, bukan jaminan kesiapan produksi.
- register_admin.php masih merupakan alat pembuatan admin melalui web dari sumber lama. Batasi aksesnya setelah bootstrap; tersedia pula alat CLI untuk demo.
- Tidak ada upstream atau lisensi proyek yang dapat dipastikan dari metadata lokal. Nama dan aset asli dipertahankan sebagai konteks sumber.

## Pemeriksaan publikasi

- PHP lint: 15/15 berkas PHP non-Blade lolos menggunakan PHP 8.1.12.
- Salinan publik tidak menyertakan .env aktual, dependency Composer/Node, log, database dump, arsip, sesi, atau unggahan lokal.
- Tes framework, koneksi database, pengoperasian UI, build aset, dan API eksternal belum dijalankan pada salinan ini. Lint hanya memeriksa sintaks; tidak membuktikan seluruh fitur berfungsi.

## Asal source dan lisensi

Disiapkan dari salinan lokal article-cms-php. Tidak ada URL upstream yang dipastikan dari metadata Git untuk proyek ini. Jangan menganggap lisensi framework atau aset otomatis melisensikan seluruh kode proyek.

Aset UI statis yang dirujuk aplikasi tetap disertakan beserta lisensi yang tersedia. Konten merek dan gambar mengikuti hak pemilik aslinya. Perubahan untuk publikasi ini meliputi dokumentasi, pemisahan konfigurasi lokal, skema demo bila diperlukan, dan koreksi terbatas yang dicatat pada persiapan proyek.
