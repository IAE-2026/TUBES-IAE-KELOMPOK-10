# Log AI (Prompt) - Payroll Service
**Tugas Besar IAE - Penggajian Karyawan**
**Nama Anggota:** M. Farhan Chanafi
**Service:** Payroll Service

Dokumen ini merangkum instruksi dan tindakan AI yang secara khusus berfokus pada pengerjaan **Tugas Besar (Integrasi Microservices, SSO, SOAP, dan RabbitMQ)** untuk Payroll Service, mengabaikan proses pembuatan dasar REST API yang sudah dilakukan pada Tugas 2.

### 1. Orkestrasi Microservices Lanjutan
**Prompt/Arahan:**
"Bantu buat alur Payroll yang otomatis menarik data gaji dari Employee Service dan data absensi dari Absensi Service."
**Tindakan AI:**
- Mengimplementasikan `EmployeeServiceClient` untuk melakukan HTTP GET *request* ke Employee Service, guna mengambil atribut `base_salary` dan `fixed_allowance` milik karyawan terkait.
- Mengimplementasikan `AbsensiServiceClient` untuk melakukan HTTP GET *request* ke Absensi Service, guna menghitung kalkulasi kehadiran (Hadir, Sakit, Izin, Alpha) yang kemudian menjadi pengurang gaji.
- Memadukan kedua data tersebut ke dalam sebuah proses kalkulasi tunggal yang menghasilkan Slip Gaji akhir.

### 2. Integrasi Cloud SSO M2M
**Prompt/Arahan:**
"Integrasikan layanan dengan M2M SSO Token dari Dosen."
**Tindakan AI:**
- Memodifikasi logika autentikasi M2M untuk mencantumkan `"nim": "102022400032"` secara dinamis sesuai pembaruan persyaratan dari dosen.
- Mengonfigurasi penggunaan akun khusus `warga19@ktp.iae.id` yang di-*hardcode* untuk operasional *background* Payroll.

### 3. SOAP Audit & Distribusi RabbitMQ
**Prompt/Arahan:**
"Amankan slip gaji dengan laporan ke SOAP Dosen dan broadcast ke RabbitMQ."
**Tindakan AI:**
- Memandu transmisi XML ke SOAP Dosen yang mengembalikan `ReceiptNumber` (bukti audit valid).
- Mengonstruksi struktur JSON lengkap (termasuk `'team_id' => env('TEAM_ID')`) untuk disiarkan (*broadcast*) ke `iae.central.exchange` RabbitMQ.

### 4. Proteksi Keamanan Lokal (API Key) & Debugging Postman
**Prompt/Arahan:**
"Pastikan endpoint aman dari akses luar."
**Tindakan AI:**
- Menghubungkan *middleware* `CheckIaeKey` untuk mengunci endpoint `POST /api/v1/payroll-runs` menggunakan `X-IAE-KEY: 102022400032`.
- Membantu proses identifikasi dan koreksi kesalahan input (Payload JSON Postman) saat *End-to-End Testing* agar parameter `"nip"`, `"tahun"`, dan `"bulan"` dapat diproses dengan benar tanpa bentrok dengan parameter *service* lain.