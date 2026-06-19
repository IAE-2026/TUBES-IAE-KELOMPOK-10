# Log AI (Prompt) - Absensi Service
**Tugas Besar IAE - Penggajian Karyawan**
**Nama Anggota:** Dylan Naufal
**Service:** Absensi Service

Dokumen ini merangkum instruksi dan tindakan AI yang secara khusus berfokus pada pengerjaan **Tugas Besar (Integrasi Microservices, SSO, SOAP, dan RabbitMQ)** untuk Absensi Service, mengabaikan proses pembuatan dasar REST API yang sudah dilakukan pada Tugas 2.

### 1. Sinkronisasi Rute API (Endpoint Unification)
**Prompt/Arahan:**
"Bantu perbaiki struktur routing agar endpoint absensi terintegrasi sepenuhnya dengan SSO dan RabbitMQ."
**Tindakan AI:**
- Menganalisis masalah pada `routes/api.php` di mana rute Tugas 3 terpisah secara fungsi.
- Menghapus *prefix* `/tugas-3/` dan menggabungkan fungsi SOAP dan RabbitMQ langsung ke *endpoint* utama `POST /api/v1/attendances`.
- Menerapkan pengamanan ganda (*double-layer protection*) pada rute menggunakan `ApiKeyMiddleware` dan `CentralJwtMiddleware`.

### 2. Komunikasi Antar-Microservice (Employee Validation)
**Prompt/Arahan:**
"Pastikan setiap data absensi divalidasi ke Employee Service terlebih dahulu."
**Tindakan AI:**
- Membantu pembuatan logika panggilan HTTP secara *backend* dari Absensi Service menuju Employee Service untuk memvalidasi apakah ID Karyawan terdaftar dan memiliki status "aktif".
- Menangani *error handling* jika Employee Service sedang *down* atau ID Karyawan tidak ditemukan.

### 3. Integrasi SSO M2M & Akun Spesifik
**Prompt/Arahan:**
"Sesuaikan autentikasi Absensi Service dengan sistem SSO Dosen menggunakan akun yang diwajibkan."
**Tindakan AI:**
- Menyesuaikan *logic* *Machine-to-Machine* (M2M) dengan menyisipkan parameter tambahan `nim` saat menghubungi SSO.
- Membantu *setup* agar interaksi JWT berjalan eksklusif dengan akun `warga24@ktp.iae.id` dan dipetakan secara lokal ke *role* `hr_staff`.

### 4. SOAP Audit & RabbitMQ
**Prompt/Arahan:**
"Kirim pencatatan absensi ke SOAP Dosen dan tampilkan notifikasinya di RabbitMQ Board."
**Tindakan AI:**
- Membimbing integrasi SOAP Client agar dapat menangkap *ReceiptNumber* setiap kali absensi direkam.
- Melengkapi *payload* JSON pada *RabbitMQ Publisher* dan memastikan tag kelomok (TEAM-10) tersisip sehingga dosen dapat melacaknya dengan mudah.

### 5. Proteksi Keamanan Lokal (API Key)
**Prompt/Arahan:**
"Pastikan rute terlindungi oleh NIM lokal."
**Tindakan AI:**
- Memastikan pengiriman `X-IAE-KEY` yang bernilai `102022400074` di Postman tervalidasi dengan baik oleh `ApiKeyMiddleware` sebelum pemrosesan diizinkan.
