# Prompt Engineering Log - Tugas 3 Absensi Service

## Prompt 1

Saya menanyakan ulang maksud dari Tugas 3 karena tugas ini ternyata masih berkaitan dengan progress Tugas Besar. Saya memastikan bahwa service yang digunakan tetap Absensi Service dari Tugas 2, bukan membuat project baru dari awal.

## Prompt 2

Saya mendiskusikan transaksi apa yang paling cocok dijadikan transaksi kritis pada Absensi Service. Dari hasil pengecekan, endpoint `POST /api/v1/attendances` dipilih karena endpoint ini mencatat kehadiran harian yang langsung berpengaruh ke perhitungan gaji karyawan.

## Prompt 3

Saya meminta bantuan untuk menyusun alasan kenapa pencatatan absensi termasuk transaksi penting. Alasannya karena data kehadiran berpengaruh langsung ke potongan gaji, jadi jika data absensi salah maka gaji karyawan juga akan salah.

## Prompt 4

Saya mengecek bagian apa saja yang harus dihubungkan ke sistem pusat dosen. Dari situ saya memahami bahwa Tugas 3 berfokus pada SSO, SOAP Audit, dan RabbitMQ.

## Prompt 5

Saya meminta arahan awal untuk mulai progress dari service Tugas 2 yang sudah ada. Saya ingin progress terlihat bertahap, jadi dimulai dari koneksi ke SSO terlebih dahulu.

## Prompt 6

Saya menanyakan konfigurasi apa saja yang perlu ditambahkan di `.env` dan `.env.example` untuk kebutuhan Cloud Dosen, seperti IAE_CENTRAL_BASE_URL, IAE_CENTRAL_API_KEY, IAE_NIM, IAE_TEAM_ID, IAE_SERVICE_NAME, dan IAE_JWT_ISSUER.

## Prompt 7

Saya membuat service untuk koneksi ke Cloud SSO Dosen. Pada bagian ini saya mengecek cara request token menggunakan API key yang diberikan dosen. Dibuat class `CentralAuthService` dengan method `getMachineToken()` dan `loginUser()`.

## Prompt 8

Saya membuat middleware `CentralJwtMiddleware` untuk memverifikasi JWT dari SSO dosen. Middleware ini memeriksa token Bearer, memvalidasi signature via JWKS, dan memetakan email user ke role lokal.

## Prompt 9

Saya membuat `JwksJwtVerifier` untuk verifikasi JWT secara mandiri tanpa library pihak ketiga. Verifier ini mengambil public key dari endpoint JWKS dosen, lalu melakukan verifikasi RS256 signature, expiry, dan issuer.

## Prompt 10

Saya mengalami error saat verifikasi JWT karena format base64url berbeda dengan base64 biasa. Setelah dicek, saya menambahkan konversi karakter `-_` ke `+/` dan padding yang benar.

## Prompt 11

Saya membuat endpoint test SSO (`POST /api/v1/tugas-3/sso/login`) untuk memastikan Absensi Service bisa login ke Cloud Dosen. Endpoint ini digunakan hanya untuk memastikan koneksi awal berjalan dan mendapatkan token user.

## Prompt 12

Saya mengecek hasil payload JWT dari SSO. Token user berbeda dengan token M2M. Token user memiliki `token_type: "user"` dan berisi `profile` dengan email dan nama. Saya memetakan email `warga24@ktp.iae.id` ke role lokal `hr_staff`.

## Prompt 13

Saya lanjut mencoba SOAP Audit. Saya mengecek format SOAP yang diminta, terutama bagian `TeamID`, `ActivityName`, dan `LogContent`. Dibuat class `CentralAuditClient` yang membangun SOAP Envelope dan mem-parse response XML.

## Prompt 14

Saya membuat SOAP Envelope dengan format yang sesuai. Data absensi yang awalnya berbentuk JSON dimasukkan ke dalam CDATA di LogContent agar bisa dikirim ke endpoint audit dosen tanpa masalah escaping.

## Prompt 15

Saya mengetes SOAP Audit melalui Postman. Setelah berhasil, response menghasilkan `ReceiptNumber` dan `Status: SUCCESS`. Saya menyimpan receipt number ke kolom `audit_receipt_number` di tabel attendances.

## Prompt 16

Saya lanjut ke bagian RabbitMQ. Saya membuat class `CentralMessagePublisher` untuk publish event absensi ke Cloud Dosen. Event yang dikirim adalah `attendance.recorded` dengan routing key `absensi.attendance.recorded`.

## Prompt 17

Saat test RabbitMQ, saya sempat mendapat error karena format body belum sesuai. Setelah dicek, body harus memiliki field `exchange`, `routing_key`, dan `payload`. Format payload saya perbaiki sesuai spesifikasi.

## Prompt 18

Saya melakukan test RabbitMQ ulang dan hasilnya berhasil. Event `attendance.recorded` berhasil masuk ke exchange `iae.central.exchange` dosen dan muncul di board RabbitMQ.

## Prompt 19

Saya menggabungkan SSO, SOAP Audit, dan RabbitMQ ke endpoint baru `POST /api/v1/tugas-3/attendances`, supaya alur lengkap bisa dijalankan: login SSO → verifikasi JWT → validasi employee → SOAP audit → simpan absensi → publish RabbitMQ.

## Prompt 20

Saya menambahkan migration baru untuk kolom-kolom integrasi Tugas 3 di tabel attendances: `created_by_email`, `created_by_name`, `local_role`, `audit_status`, `audit_receipt_number`, `central_event_id`, `event_routing_key`, dan `event_published_at`.

## Prompt 21

Saya melakukan test ulang endpoint `POST /api/v1/tugas-3/attendances` secara end-to-end. Hasilnya absensi berhasil dicatat, role lokal terbaca `hr_staff`, SOAP menghasilkan receipt number, dan RabbitMQ berhasil publish event.

## Prompt 22

Saya meminta bantuan untuk menyusun analisis Tugas 3, terutama bagian transaksi kritis, alasan pemilihan transaksi, role lokal, alur integrasi, dan hasil pengujian.

## Prompt 23

Saya membuat sequence diagram untuk menggambarkan alur Absensi Service saat terhubung ke SSO, Employee Service, database, SOAP Audit, dan RabbitMQ.

---
