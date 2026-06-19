Resume Kontribusi Tim Payroll Service
Nama : M farhan chanafi 
Nim : 102022400032

Service ini menangani perhitungan dan penerbitan slip gaji, juga dibangun dengan Laravel.

Fitur dasar

Model PayrollSlip menyimpan slip gaji per karyawan per periode: gaji pokok, tunjangan tetap, rekap kehadiran, potongan absensi, dan total gaji.
Endpoint GET /api/v1/payroll-slips untuk melihat seluruh slip gaji, dan GET /api/v1/payroll-slips/{nip}/{tahun}/{bulan} untuk detail slip per periode tertentu.
Endpoint utama POST /api/v1/payroll-runs yang menjalankan proses payroll secara otomatis:

Mengambil data karyawan (gaji pokok dan tunjangan tetap) dari Employee Service.
Mengambil rekap kehadiran bulan tersebut dari Absensi Service.
Menghitung gaji dengan rumus: total_gaji = gaji_pokok + tunjangan_tetap − (jumlah_alpha × Rp100.000).


Kalau salah satu service tetangga tidak bisa dihubungi, atau karyawan yang bersangkutan berstatus tidak aktif, proses payroll dibatalkan dan mengembalikan pesan error yang jelas (kode 502 atau 422) supaya mudah ditelusuri penyebabnya.
Seluruh endpoint dikunci dengan API key lewat middleware.


Integrasi ke sistem pusat (tahap lanjutan)

POST /api/v1/payroll-runs dipilih sebagai transaksi kritis karena langsung berkaitan dengan data keuangan dan penerbitan slip gaji. Pada tahap ini saya menambahkan:

Login ke SSO pusat menggunakan API key dan NIM, lalu payload JWT-nya didekode untuk mengambil subject. Subject ini dipetakan ke role lokal HR_ADMIN; kalau role yang terbaca bukan HR_ADMIN, proses payroll ditolak dengan kode 403.
Sempat mengalami timeout saat menghubungkan Docker container ke server SSO dosen. Setelah dicek lewat Postman, ternyata server pusatnya aktif dan masalahnya ada di sisi konfigurasi jaringan Docker — setelah diperbaiki, koneksi berjalan normal.
Setiap proses payroll yang selesai dikirim sebagai audit ke sistem SOAP pusat, dan nomor resinya (soap_receipt_number) disimpan ke tabel payroll_slips.
Setelah audit berhasil, event payroll.processed dipublish ke RabbitMQ pusat lengkap dengan team_id, supaya event tersebut tercatat sebagai milik kelompok di board RabbitMQ dosen.
Sempat ada bug di mana kode integrasi SSO/SOAP/RabbitMQ tertulis setelah statement return, sehingga tidak pernah benar-benar dijalankan. Setelah ditemukan, urutan kodenya diperbaiki sampai seluruh alur berjalan dari awal hingga akhir.
Response akhir dari payroll-runs dilengkapi data sources (data Employee Service dan Absensi Service yang dipakai untuk perhitungan) dan cloud_integration (status SSO, SOAP, RabbitMQ), supaya satu transaksi bisa langsung diverifikasi sudah melewati seluruh rantai integrasi atau belum.

3. Pengujian End-to-End

Alur lengkap diuji mulai dari pembuatan data karyawan baru di Employee Service, pencatatan absensinya di Absensi Service, sampai penerbitan slip gajinya di Payroll Service. Ketiga proses tersebut dipastikan memunculkan audit SOAP dan tercatat di board RabbitMQ dosen dengan label tim (TEAM-10). Dokumentasi API kedua service juga dilengkapi Swagger/OpenAPI, sehingga endpoint, parameter, dan contoh response bisa dicek tanpa harus membaca kode satu per satu.

4. Kendala yang Dihadapi

Format base64url pada JWT berbeda dari base64 standar, sehingga verifikasi token sempat gagal sebelum konversi karakter dan padding-nya diperbaiki.
Koneksi dari container Docker ke server SSO dosen sempat timeout, padahal server pusatnya aktif — ternyata penyebabnya ada di konfigurasi jaringan Docker.
Format body untuk publish RabbitMQ sempat tidak sesuai spesifikasi dosen, sehingga event gagal masuk ke exchange sebelum strukturnya disesuaikan.
Bug logika: kode integrasi sempat diletakkan setelah return di Payroll Service, sehingga SSO, SOAP Audit, dan RabbitMQ tidak ikut berjalan meskipun payroll tetap tersimpan.