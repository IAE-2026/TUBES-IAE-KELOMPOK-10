# Log AI (Prompt) - Employee Service
**Tugas Besar IAE - Penggajian Karyawan**
**Nama Anggota:** Dimas
**Service:** Employee Service

Dokumen ini merangkum instruksi dan tindakan AI yang secara khusus berfokus pada pengerjaan **Tugas Besar (Integrasi Microservices, SSO, SOAP, dan RabbitMQ)** untuk Employee Service, mengabaikan proses pembuatan dasar REST API yang sudah dilakukan pada Tugas 2.

### 1. Integrasi Autentikasi SSO (Machine-to-Machine)
**Prompt/Arahan:**
"Bantu implementasikan pengambilan Token M2M dari Central SSO Dosen untuk aplikasi Employee Service agar bisa mengakses RabbitMQ dan SOAP."
**Tindakan AI:**
- Memandu penulisan kode di `SsoService.php` untuk memanggil API `/api/v1/auth/token`.
- Mengimplementasikan penambahan parameter wajib `"nim": "102022400197"` pada saat request token M2M berdasarkan pembaruan aturan dari dosen.
- Menetapkan penggunaan akun federasi khusus `warga34@ktp.iae.id` yang di-*hardcode* untuk proses autentikasi sistem.

### 2. Implementasi SOAP Audit
**Prompt/Arahan:**
"Bagaimana cara mengirim log transaksi pembuatan karyawan baru ke SOAP Audit milik dosen?"
**Tindakan AI:**
- Membantu pembuatan `SoapAuditService.php` untuk menghasilkan format XML request yang sesuai standar SOAP dosen.
- Menyuntikkan pemanggilan SOAP tersebut pada *controller* saat ada entitas karyawan (Employee) baru yang di-*create*.

### 3. Publikasi Notifikasi ke RabbitMQ Board
**Prompt/Arahan:**
"Bantu publikasikan pesan ke RabbitMQ Board dengan label TEAM-10 agar terlihat dosen saat karyawan baru berhasil dibuat."
**Tindakan AI:**
- Mendebug isu *payload* pesan yang sebelumnya gagal ditemukan di RabbitMQ Board.
- Menyisipkan kunci `team_id: config('iae.team_id')` ke dalam data JSON yang dikirimkan melalui `RabbitMqPublisher.php` agar sesuai dengan parameter pencarian *board* dosen (TEAM-10).

### 4. Proteksi Keamanan Lokal (API Key)
**Prompt/Arahan:**
"Amankan endpoint menggunakan custom API Key sesuai penugasan."
**Tindakan AI:**
- Mengarahkan implementasi `EnsureIaeApiKey` middleware yang mewajibkan `X-IAE-KEY` berisi `102022400197` pada *header* HTTP untuk setiap request yang masuk ke Employee Service.
