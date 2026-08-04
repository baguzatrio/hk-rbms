# 📊 HK-RBMS (Hutama Karya Room Booking Management System)
## Comprehensive Technical & Functional Project Analysis Document

---

## 📌 Executive Summary

**HK-RBMS** (Hutama Karya Room Booking Management System) adalah aplikasi manajemen pemesanan ruang rapat dan jadwal kegiatan internal berbasis web. Aplikasi ini dirancang untuk memfasilitasi pengguna (pegawai) dalam melakukan pengajuan booking ruangan secara mandiri, memberikan kendali penuh kepada Administrator untuk persetujuan (*approval*), penolakan (*rejection*), dan pengelolaan *master data*, serta menyediakan tampilan **Real-Time Room TV Display** untuk memantau agenda rapat di depan setiap ruangan.

---

## 🛠️ Technology Stack & Dependencies

### **1. Backend Framework & Libraries**
- **Core Framework**: [Laravel 13](https://laravel.com/) (PHP 8.2+)
- **SPA & Routing**: [Inertia.js v2](https://inertiajs.com/) + Vue Router (`vue-router` v4) & Axios REST API Bridge.
- **Authentication**: Laravel Fortify (Autentikasi, Password Reset, & Two-Factor Authentication / 2FA)
- **API Security**: Laravel Sanctum (Autentikasi Token & Session-based API Guard)
- **Role & Access Control**: Spatie Laravel Permission (`spatie/laravel-permission`)
- **Database ORM**: Eloquent ORM dengan relasi presisi & query optimization

### **2. Frontend Framework & UI Libraries**
- **Core UI Engine**: Vue 3 (Composition API `<script setup>` + TypeScript)
- **State Management**: Reactive Auth Store (`authStore` via Vue `reactive` & `computed`)
- **Styling**: Tailwind CSS v4 (Desain modern & responsif)
- **Component Library**: Shadcn Vue / Reka UI + Custom UI Primitives (`Card`, `Button`, `Badge`, `Input`, `Dialog`, `DropdownMenu`, `SearchableSelect`, `PaginationNav`)
- **Iconography**: Lucide Vue Next (`lucide-vue-next`)
- **Date & Time Picker**: VueDatePicker (`@vuepic/vue-datepicker`)
- **Alerts & Modals**: SweetAlert2 (`sweetalert2`)
- **Build Tool**: Vite 6 (dikombinasikan dengan `@laravel/vite-plugin-wayfinder` & `@vitejs/plugin-vue`)

---

## 🗄️ Database Architecture & Schema Analysis

Sistem menggunakan database relasional dengan tabel-tabel utama berikut:

```
[ users ] ───( belongsTo )───> [ organizations ]
   │
   ├───( hasMany )───> [ books (as requester) ]
   └───( morphMany )───> [ model_has_roles ] ───> [ roles ]

[ books ] ───( belongsTo )───> [ rooms ]
```

### **Detail Entitas Tables & Columns**

1. **`users`**
   - `id`: BigInteger (Primary Key)
   - `name`: String (Nama Lengkap)
   - `email`: String (Unique)
   - `password`: String (Hashed)
   - `role`: Enum ('admin', 'user')
   - `organization_id`: Foreign Key (`organizations.id`, nullable)
   - `two_factor_*`: Kolom pendukung 2FA Fortify

2. **`organizations`** (Divisi / Unit Kerja)
   - `id`: BigInteger (Primary Key)
   - `name`: String (Nama Divisi Lengkap)
   - `shortname`: String (Singkatan Divisi, misal: *IT*, *HC*, *FIN*, *CORSEC*)

3. **`rooms`** (Ruang Rapat)
   - `id`: BigInteger (Primary Key)
   - `name`: String (Nama Ruang Rapat)
   - `floor`: String (Lantai Lokasi Ruangan)
   - `capacity`: Integer (Kapasitas Orang)
   - `status`: Enum ('active', 'maintenance')
   - `description`: Text (Fasilitas & Catatan Ruangan)

4. **`books`** (Pemesanan / Booking Ruangan)
   - `id`: BigInteger (Primary Key)
   - `room_id`: Foreign Key (`rooms.id`)
   - `requester_id`: Foreign Key (`users.id`)
   - `title`: String (Agenda / Subjek Rapat)
   - `start_date`: Date (Tanggal Mulai Rapat)
   - `end_date`: Date (Tanggal Selesai Rapat - mendukung multi-hari)
   - `start_hour`: Time (Jam Mulai)
   - `finish_hour`: Time (Jam Selesai)
   - `status`: Enum ('pending', 'approved', 'rejected', 'cancelled')
   - `pic_name`: String (Nama PIC / Pemohon)
   - `participants`: Text (Daftar Peserta / Divisi Terkait)
   - `notes`: Text (Deskripsi Tambahan / Catatan)
   - `rejection_note`: Text (Alasan Penolakan Admin)

5. **`activity_logs`** (Audit Trail System)
   - Mencatat seluruh aktivitas penting (Create, Update, Delete, Approval/Rejection) oleh user & admin.

### **Seeder Modular (`database/seeders/`)**
- **`DatabaseSeeder`**: Memanggil seeder secara sekuensial:
  - `UserSeeder` (Akun Admin & User default)
  - `RoleSeeder` (Roles & Permissions Spatie)
  - `OrganizationSeeder` (Daftar Divisi Organisasi)
  - `RoomSeeder` (Master Ruang Rapat & Kapasitas)
  - `BookingSeeder` (Dummy Data Pemesanan Ruangan)

---

## 🔐 User Roles & Access Control (RBAC)

Aplikasi memiliki 3 tingkat akses utama:

| Fitur / Modul | Guest / Public | User (Pegawai) | Admin |
| :--- | :---: | :---: | :---: |
| **TV Display Ruangan (`/display/{room}`)** | ✅ | ✅ | ✅ |
| **User Dashboard & Kalender Agenda** | ❌ | ✅ | ✅ |
| **Pengajuan Booking Ruangan** | ❌ | ✅ (Otomatis Lock Pemohon & Divisi) | ✅ (Bisa Pilih User/Divisi Mana Saja) |
| **Ubah / Batalkan Booking Mandiri** | ❌ | ✅ (Status Pending) | ✅ |
| **Approval / Rejection (Individu & Bulk)** | ❌ | ❌ | ✅ |
| **Manajemen Master Ruangan** | ❌ | ❌ | ✅ |
| **Manajemen Master User & Reset Password** | ❌ | ❌ | ✅ |
| **Manajemen Master Divisi (Organization)** | ❌ | ❌ | ✅ |
| **Audit Logs & Export Laporan** | ❌ | ❌ | ✅ (Export PDF/Excel/Print) |

---

## 💡 Core Business Rules & Application Logic

1. **Validasi Konflik Jadwal Ruangan (Schedule Conflict Prevention)**:
   - Sistem secara otomatis memeriksa tumpang tindih (*overlap*) jadwal menggunakan method `Book::hasConflict()`.
   - Pemeriksaan mencakup gabungan filter: `room_id`, rentang tanggal (`start_date` - `end_date`), dan jam pelaksanaan (`start_hour` - `finish_hour`).

2. **Pembatasan Hari Kerja (Weekend Restriction)**:
   - Pemesanan ruangan hanya diperbolehkan pada hari kerja (**Senin – Jumat**).
   - Pemilihan hari Sabtu atau Minggu pada datepicker akan ditolak otomatis oleh sistem.

3. **Dukungan Rapat Multi-Hari (Multi-Day Meetings)**:
   - Rapat yang berlangsung selama beberapa hari (misalnya tanggal 25 s.d. 31) tetap ditampilkan secara dinamis di tab **Agenda Hari Ini** selama tanggal hari ini masih dalam rentang waktu rapat tersebut.

4. **Auto-Prefill & Auto-Lock Pemohon**:
   - Saat pengguna ber-role `user` membuat atau mengedit booking, sistem secara otomatis mengunci nama pemohon dan divisi sesuai akun pengguna yang sedang login.

5. **Perlindungan Akun Admin (Self-Protection Safety)**:
   - Administrator tidak dapat menghapus akunnya sendiri melalui panel manajemen user (`v-if="currentUser?.id !== user.id"`).

6. **Mass Action / Bulk Operations**:
   - Administrator dapat menyetujui (*bulk approve*) atau menolak (*bulk reject*) banyak pengajuan pemesanan secara bersamaan melalui fitur *checkbox selection*.

---

## 🚀 Key Modules & UI/UX Features

### 1. **User Dashboard (`resources/js/pages/Dashboard/UserDashboard.vue`)**
- **Card Agenda Rapat**: Toggle switcher antara tab **Hari Ini** (menampilkan rapat aktif hari ini) dan tab **Mendatang** (dengan paginasi item per halaman).
- **Interactive Calendar Grid**: Kalender bulanan yang menampilkan jadwal rapat per tanggal dengan modal detail rapat saat cell diklik.
- **Filter Ruangan & Divisi**: Filter interaktif real-time untuk menyaring agenda rapat berdasarkan divisi maupun ruangan.

### 2. **Admin Dashboard & Management (`resources/js/pages/Admin/`)**
- **Pengelolaan Approval**: Admin dapat menyetujui (*approve*) atau menolak (*reject*) booking dengan menyertakan alasan penolakan.
- **Master Ruangan & Divisi**: Manajemen status ruangan (Aktif vs Maintenance), kapasitas, dan daftar divisi organisasi.
- **Master User**: Penambahan, pengeditan profil, manajemen role pengguna, dan fitur *reset password*.
- **Export Laporan & Audit Log**: Fasilitas cetak/export rekapitulasi booking dan audit log aktivitas pengguna.

### 3. **Public TV Display (`resources/js/pages/Display/RoomDisplay.vue`)**
- Tampilan mode layar penuh (*full-screen TV Display*) di depan setiap ruang rapat yang menampilkan nama ruangan, rapat berjalan (*current meeting*), serta daftar rapat berikutnya (*next meetings*).

---

## 📁 Directory Structure Summary

```
hk-rbms/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/ (BookingController, RoomController, UserController, OrganizationController, ActivityLogController)
│   │   │   ├── BookingController.php (User side)
│   │   │   ├── DashboardController.php
│   │   │   └── ScheduleController.php
│   │   └── Middleware/ (HandleInertiaRequests.php, HandleAppearance.php)
│   └── Models/ (Book.php, Room.php, Organization.php, User.php, ActivityLog.php)
├── database/
│   ├── migrations/ (Skema tabel & relasi database)
│   └── seeders/ (DatabaseSeeder, UserSeeder, RoleSeeder, OrganizationSeeder, RoomSeeder, BookingSeeder)
├── public/
│   └── PROJECT_ANALYSIS.md (Dokumen Analisis Proyek Ini)
├── resources/
│   ├── js/
│   │   ├── components/ (SearchableSelect, PaginationNav, UserInfo, UserMenuContent, UI Primitives)
│   │   ├── composables/ (useInitials, useAppearance)
│   │   ├── layouts/ (AppLayout, AuthLayout, SettingsLayout)
│   │   ├── routes/ (Vue Router Route Definitions)
│   │   ├── stores/ (authStore - Reactive Store Authentication State)
│   │   ├── types/ (User, Book, Room, Organization, Navigation types)
│   │   └── pages/
│   │       ├── Admin/ (Bookings, Rooms, Users, Organizations, ActivityLogs)
│   │       ├── Bookings/ (Create.vue, Edit.vue, Index.vue)
│   │       ├── Dashboard/ (UserDashboard.vue, AdminDashboard.vue)
│   │       └── Display/ (RoomDisplay.vue)
│   └── views/
│       └── app.blade.php (Root HTML template)
└── routes/
    ├── web.php (Route terautentikasi & admin)
    ├── api.php (REST API endpoints Sanctum)
    └── settings.php (Profil & Security)
```

---

## 📌 Kesimpulan & Status Sistem
Proyek **HK-RBMS** telah berhasil diperbarui dan disempurnakan dengan arsitektur berbasis **Laravel 13 + Inertia.js v2 + Vue 3 (Vue Router & REST API)**, menyajikan performa SPA (*Single Page Application*) yang intuitif, UI/UX responsif dengan Tailwind CSS v4, serta keamanan berbasis *Role-Based Access Control* (RBAC) yang solid.
