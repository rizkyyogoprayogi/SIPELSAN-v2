# Sistem Pelanggaran Santri (SIPELSAN)

Sistem Informasi Manajemen Pelanggaran Santriwati berbasis Web, dibuat menggunakan Native PHP 8.

## 🚀 Fitur Utama

- **Manajemen Santriwati**: CRUD data santri lengkap dengan foto profil (inisial).
- **Pencatatan Pelanggaran**: Input pelanggaran mudah dengan auto-complete search.
- **Poin Pelanggaran**: Sistem poin otomatis berdasarkan tingkat pelanggaran (Ringan/Sedang/Berat).
- **Activity Log**: Audit trail lengkap untuk memantau siapa yang menambah, mengedit, atau menghapus data.
- **Role Management**: Admin (Full Access) dan Guru (Input & View).
- **Dashboard Interaktif**: Statistik dan Grafik pelanggaran real-time.
- **Responsif**: Tampilan mobile-friendly dengan sidebar drawer.

## 🛠️ Teknologi

- **Backend**: PHP 8.2 (Native)
- **Frontend**: HTML5, Vanilla CSS (Custom Design System), JavaScript (SweetAlert2, Chart.js)
- **Database**: MySQL / MariaDB

## 📦 Cara Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/rizkyyogoprayogi/SIPELSAN-v2
   cd santri-violation-system
   ```

2. **Setup Database**
   - Buat database baru di MySQL (misal: `santri_db`).
   - Import file migration yang ada di folder `database/migrations/` (jalankan berurutan jika perlu, atau gunakan file schema utama jika disediakan).
   
   *Note: Pastikan tabel `users`, `santriwati`, `violations`, `classes`, dan `activity_logs` terbuat.*

3. **Konfigurasi**
   - Copy file konfigurasi:
     ```bash
     cp config/database.example.php config/database.php
     ```
   - Edit `config/database.php` dan sesuaikan credential database Anda:
     ```php
     $host = 'localhost';
     $dbname = 'santri_db';
     $username = 'root';
     $password = 'password';
     ```

4. **Jalankan Aplikasi**
   - Gunakan PHP Built-in Server untuk development:
     ```bash
     php -S localhost:8000
     ```
   - Buka browser dan akses `http://localhost:8000`.

5. **Akun Default**
   - Login menggunakan akun yang sudah Anda seed di `users` table.
   - Jika belum ada, Anda bisa insert manual ke database tabel `users` (password harus di-hash menggunakan `password_hash()`).

## 📂 Struktur Folder

- `/assets`: CSS, JS, Images
- `/auth`: Login/Logout logic
- `/config`: Database connection
- `/database`: Migration scripts
- `/includes`: Helper functions (UI, Logger)
- `/modules`: Modular features (Santri, Violations, Users, Logs)
- `/uploads`: User uploaded content (Evidence files)

## 🔒 Security

- Password Hashing (Bcrypt)
- Session Management
- CSRF Protection (Basic)
- XSS Prevention `htmlspecialchars`
- Audit Logging

---
*Dibuat dengan ❤️ untuk kedisiplinan pondok.*
