# Resume Kontribusi — Employee Service

**Nama:** Dimas Muhammad Firizki
**NIM:** 102022400197  
**Service:** Data Karyawan (Employee Service)  
**Project:** Tugas Besar IAE — Penggajian Karyawan (Kelompok 10)

---

Employee Service berperan sebagai sumber data master karyawan yang dibangun menggunakan Laravel. Service lain seperti Absensi dan Payroll bergantung pada service ini untuk mengambil data karyawan melalui REST API.

Endpoint yang tersedia mencakup operasi GET, POST, PUT, dan DELETE pada `/api/v1/employees`. Setiap request wajib menyertakan header `X-IAE-KEY` sebagai autentikasi berbasis API key lokal.

Untuk Tugas 3, service ini diintegrasikan dengan tiga layanan eksternal. Pertama, Central SSO digunakan untuk memverifikasi JWT token pengguna menggunakan JWKS, serta mengambil Machine Token (M2M) yang dipakai saat service berkomunikasi ke layanan pusat secara otomatis. Kedua, setiap operasi create, update, dan delete karyawan dikirimkan sebagai log ke layanan SOAP Audit, dan `ReceiptNumber` yang diterima disimpan ke tabel `audit_logs` sebagai bukti transaksi. Ketiga, setiap perubahan data karyawan dipublikasikan ke RabbitMQ dengan routing key seperti `employee.created` agar service lain bisa menerima notifikasi secara real-time tanpa perlu polling.

Seluruh alur tersebut dibungkus dalam satu database transaction, sehingga jika salah satu integrasi gagal, perubahan data di database otomatis dibatalkan.

Selain REST, service ini juga menyediakan endpoint GraphQL di `/graphql` menggunakan Lighthouse untuk kebutuhan query data karyawan yang lebih fleksibel.
