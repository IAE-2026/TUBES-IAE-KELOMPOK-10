# Log AI (Prompt) - Laporan Kelompok Keseluruhan
**Tugas Besar IAE - Penggajian Karyawan**
**Kelompok 10:** Dimas Muhammad Firizki, Dylan Naufal, M. Farhan Chanafi

Dokumen ini merangkum instruksi dan tindakan AI secara menyeluruh untuk fase **Tugas Besar (Sesi Integrasi Akhir Antar-Service, SSO, SOAP, dan RabbitMQ)**, sebagai representasi kolaborasi seluruh *microservices* (Employee, Absensi, dan Payroll) menjadi satu ekosistem yang kohesif.

---

### 1. Standarisasi Endpoint & Keamanan Terpusat
**Prompt Kelompok:**
"Bantu selaraskan dan lindungi *endpoint* dari ketiga microservice kami."
**Tindakan AI:**
- AI menyelaraskan struktur *endpoint* di setiap layanan (`/api/v1/employees`, `/api/v1/attendances`, `/api/v1/payroll-runs`) agar beroperasi dengan logika yang seragam.
- AI memastikan setiap titik akhir (*endpoint*) dikunci dengan proteksi keamanan ganda secara lokal maupun sentral:
  - Validasi lokal menggunakan NIM masing-masing lewat *Header HTTP* `X-IAE-KEY`.
  - Validasi sentral menggunakan verifikasi JWT dari *Cloud SSO Dosen*.

### 2. Resolusi Isu SSO & Distribusi Akun Warga
**Prompt Kelompok:**
"Ada update instruksi dari dosen mengenai parameter wajib `nim` dan pembagian akun warga. Bantu selesaikan konfigurasinya."
**Tindakan AI:**
- AI menyuntikkan tambahan parameter `nim` (sesuai spesifikasi masing-masing layanan) pada seluruh *method HTTP Request* yang menghubungi titik akhir `/api/v1/auth/token` milik dosen.
- AI secara cermat memandu penggunaan 3 akun federasi terpisah agar *role mapping* tidak mengalami tumpang tindih (*clash*):
  - **Employee Service:** Menerapkan akun `warga34@ktp.iae.id`.
  - **Absensi Service:** Menerapkan akun `warga24@ktp.iae.id`.
  - **Payroll Service:** Menerapkan akun `warga19@ktp.iae.id`.

### 3. Orchestrasi Komunikasi Antar-Microservices (E2E)
**Prompt Kelompok:**
"Bagaimana cara memastikan ketiganya dapat berinteraksi di *background*?"
**Tindakan AI:**
- AI menanamkan logika HTTP Client pada **Absensi Service** yang secara diam-diam memanggil **Employee Service** guna memverifikasi keabsahan ID Karyawan yang absen.
- AI membangun sistem agregasi di **Payroll Service** yang bertugas mengambil besaran *gaji pokok* (HTTP GET ke Employee Service) lalu mengalikannya dengan *rekapan absensi harian* (HTTP GET ke Absensi Service) untuk menghasilkan nominal gaji final.

### 4. Penyelarasan Central RabbitMQ Board (Identitas Tim)
**Prompt Kelompok:**
"Mengapa pesan kami ada yang gagal muncul di *board* pencarian RabbitMQ dosen?"
**Tindakan AI:**
- AI melakukan *code-review* kilat terhadap struktur JSON *Payload Publisher* dan menyadari bahwa beberapa *service* belum melampirkan identitas tim.
- AI memodifikasi sistem *Publisher* untuk mewajibkan pelampiran parameter `"team_id": "TEAM-10"` ke dalam struktur JSON sebelum *routing key* dikirimkan.
- AI menjalankan orkestrasi via `docker-compose up -d --build` untuk menerapkan konfigurasi ulang.

### 5. Final End-to-End System Testing
**Prompt Kelompok:**
"Bantu ujicoba aliran data dari penciptaan karyawan hingga penggajiannya."
**Tindakan AI:**
- AI menyusun struktur JSON *testing* (`EMP-7777`) untuk diuji coba berurutan di Postman.
- Proses pengujian sukses dari ujung ke ujung: Entitas karyawan tercipta, tercatat hadir, dan slip gaji terbit; di mana ketiga *event* tersebut secara *real-time* membangkitkan laporan SOAP Audit serta berbaris sempurna di dalam sistem RabbitMQ Dosen berlabel "TEAM-10".
