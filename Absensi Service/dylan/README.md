# 📋 Absensi Service

**IAE Tugas 2 — BBK2HAB3 Integrasi Aplikasi Enterprise**

Service untuk pencatatan kehadiran (absensi) karyawan sebagai bagian dari ekosistem **Penggajian Karyawan**.

| Info | Value |
|------|-------|
| **Mahasiswa** | Muhammad Dylan Naufal Maulana |
| **NIM** | 102022400074 |
| **Service** | Absensi-Service |
| **Framework** | Laravel 11 |
| **Database** | PostgreSQL 15 |
| **Port** | 8002 |

---

## 🚀 Cara Menjalankan

### Prerequisites
- Docker & Docker Compose
- (Opsional) `payroll-network` Docker network jika running bersama service lain

### 1. Clone & Setup

```bash
git clone https://github.com/[org]/102022400074_Absensi-Service.git
cd 102022400074_Absensi-Service
cp .env.example .env
```

### 2. Konfigurasi .env

```env
IAE_API_KEY=102022400074          # API Key service ini
EMPLOYEE_SERVICE_URL=http://employee-service:8000   # URL service Dimas
EMPLOYEE_SERVICE_KEY=102022400197                   # NIM/API Key service Dimas
```

### 3. Jalankan dengan Docker

```bash
# Buat shared network (jika belum ada)
docker network create payroll-network

docker-compose up -d --build
```

Service akan tersedia di `http://localhost:8002`

### 4. (Opsional) Seed data contoh

```bash
docker exec absensi-service php artisan db:seed
```

---

## 📡 REST API Endpoints

**Base URL:** `http://localhost:8002/api/v1`  
**Authentication:** Header `X-IAE-KEY: 102022400074`

### GET /api/v1/attendances
Menampilkan seluruh data absensi karyawan.

```bash
curl -X GET http://localhost:8002/api/v1/attendances \
  -H "X-IAE-KEY: 102022400074"
```

**Response 200:**
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [
    {
      "id": 1,
      "employee_id": "EMP-001",
      "date": "2025-05-15",
      "status": "hadir",
      "note": null,
      "created_at": "2025-05-15T08:00:00.000000Z",
      "updated_at": "2025-05-15T08:00:00.000000Z"
    }
  ],
  "meta": {
    "service_name": "Absensi-Service",
    "api_version": "v1",
    "total": 1
  }
}
```

---

### GET /api/v1/attendances/{start_date}/{end_date}
Menampilkan rekap absensi dalam rentang tanggal tertentu.

```bash
curl -X GET http://localhost:8002/api/v1/attendances/2025-05-01/2025-05-31 \
  -H "X-IAE-KEY: 102022400074"
```

**Response 200:**
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [...],
  "meta": {
    "service_name": "Absensi-Service",
    "api_version": "v1",
    "period_start": "2025-05-01",
    "period_end": "2025-05-31",
    "total": 20
  }
}
```

---

### POST /api/v1/attendances
Mencatat absensi harian karyawan. Sistem akan memvalidasi `employee_id` ke Employee Service.

```bash
curl -X POST http://localhost:8002/api/v1/attendances \
  -H "X-IAE-KEY: 102022400074" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": "EMP-001",
    "date": "2025-05-15",
    "status": "hadir",
    "note": null
  }'
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| employee_id | string | ✅ | ID karyawan dari Employee Service |
| date | date (Y-m-d) | ✅ | Tanggal absensi |
| status | enum | ✅ | `hadir`, `izin`, `sakit`, `alpha` |
| note | string | ❌ | Catatan opsional |

**Response 201:**
```json
{
  "status": "success",
  "message": "Attendance recorded successfully",
  "data": {
    "id": 1,
    "employee_id": "EMP-001",
    "date": "2025-05-15",
    "status": "hadir",
    "note": null,
    "created_at": "2025-05-15T08:00:00.000000Z",
    "updated_at": "2025-05-15T08:00:00.000000Z"
  },
  "meta": {
    "service_name": "Absensi-Service",
    "api_version": "v1"
  }
}
```

---

## 🔐 Security

Semua endpoint dilindungi dengan API Key melalui request header:

```
X-IAE-KEY: 102022400074
```

Response jika API Key salah atau tidak ada:
```json
{
  "status": "error",
  "message": "Unauthorized: Invalid or missing X-IAE-KEY header.",
  "errors": null
}
```

---

## 📚 Dokumentasi Swagger

Swagger UI tersedia di:  
👉 `http://localhost:8002/api/documentation`

---

## 🔷 GraphQL

**Endpoint:** `http://localhost:8002/graphql`  
**GraphiQL:** `http://localhost:8002/graphiql`

### Query Examples

**Semua absensi (semua field):**
```graphql
query {
  attendances {
    id
    employee_id
    date
    status
    note
    created_at
  }
}
```

**Filter by employee dan date range:**
```graphql
query {
  attendances(
    employee_id: "EMP-001"
    start_date: "2025-05-01"
    end_date: "2025-05-31"
  ) {
    date
    status
  }
}
```

**Filter by status:**
```graphql
query {
  attendances(status: alpha) {
    employee_id
    date
    note
  }
}
```

**Single attendance by ID:**
```graphql
query {
  attendance(id: 1) {
    id
    employee_id
    date
    status
  }
}
```

---

## 🧪 Menjalankan Tests

```bash
docker exec absensi-service php artisan test
```

Atau dari lokal (butuh SQLite):
```bash
php artisan test
```

Test coverage:
- ✅ API Key protection (tanpa key → 401, key salah → 401)
- ✅ GET all attendances (200, struktur response)
- ✅ GET by date range (filter, validasi tanggal)
- ✅ POST attendance (201, validasi field, duplicate prevention)
- ✅ Employee validation (inactive → 404, not found → 404)
- ✅ Standard Integration Contract response format
- ✅ Swagger UI accessibility
- ✅ GraphQL endpoint accessibility

---

## 🔗 Inter-Service Communication

Service ini berkomunikasi dengan **Employee Service** (milik Dimas, NIM `102022400197`) untuk memvalidasi `employee_id` sebelum menyimpan data absensi.

**Alur validasi:**
```
POST /api/v1/attendances
    ↓
Cek employee_id ke Employee Service:
GET {EMPLOYEE_SERVICE_URL}/api/v1/employees/{employee_id}
Header: X-IAE-KEY: 102022400197
    ↓
Jika 404 → tolak (employee tidak ditemukan)
Jika status != 'active' → tolak (employee tidak aktif)
Jika service down → lanjut (graceful degradation)
    ↓
Simpan attendance
```

### Setup Koneksi ke Employee Service Dimas

**Kasus 1: Satu laptop, beda docker-compose (paling umum)**

Pastikan dua service join ke network yang sama:
```bash
# Jalankan Employee Service Dimas dulu
cd ../102022400197_Dimas-Muhammad-Firizki-Data-Karyawan-Service-main
docker-compose up -d

# Jalankan Absensi Service
cd ../102022400074_Absensi-Service
# Employee Service Dimas memakai container name employee-service dan port internal 8000.
# Set EMPLOYEE_SERVICE_URL=http://employee-service:8000
# Set EMPLOYEE_SERVICE_KEY=102022400197
docker-compose up -d
```

Sebelum membuat absensi untuk `EMP-001`, pastikan data karyawannya sudah ada di Data Karyawan Service:

```bash
curl -X POST http://localhost:8001/api/v1/employees \
  -H "X-IAE-KEY: 102022400197" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": "EMP-001",
    "nik": "3276010101010001",
    "name": "Dimas Pratama",
    "email": "dimas@example.com",
    "position": "Staff HR",
    "department": "Human Resource",
    "base_salary": 5500000,
    "fixed_allowance": 750000,
    "status": "active"
  }'
```

**Kasus 2: Beda laptop**
```env
# Di .env Absensi Service, set ke IP laptop Dimas
EMPLOYEE_SERVICE_URL=http://192.168.x.x:8000
EMPLOYEE_SERVICE_KEY=102022400197
```

**Kasus 3: Testing lokal tanpa Employee Service**
```env
# Kosongkan EMPLOYEE_SERVICE_URL → validasi dilewati (graceful degradation)
EMPLOYEE_SERVICE_URL=
```

> 💡 **Cek docker container name Dimas:** jalankan `docker ps` setelah Dimas menjalankan service-nya, lalu lihat kolom `NAMES`. Itu yang dipakai sebagai hostname di `EMPLOYEE_SERVICE_URL`.

---

## 🗂️ Struktur Project

```
102022400074_Absensi-Service/
├── app/
│   ├── Http/
│   │   ├── Controllers/API/AttendanceController.php
│   │   └── Middleware/ApiKeyMiddleware.php
│   ├── Models/Attendance.php
│   └── GraphQL/Queries/AttendanceQuery.php
├── bootstrap/app.php
├── config/
│   ├── services.php
│   ├── l5-swagger.php
│   └── lighthouse.php
├── database/
│   ├── factories/AttendanceFactory.php
│   ├── migrations/..._create_attendances_table.php
│   └── seeders/DatabaseSeeder.php
├── docker/
│   ├── nginx.conf
│   └── entrypoint.sh
├── graphql/schema.graphql
├── public/index.php
├── routes/api.php
├── tests/Feature/AttendanceApiTest.php
├── .env.example
├── AI_PROMPTING_LOG.md
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## 📊 Standard Integration Contract

Service ini mengikuti **Standard Integration Contract IAE-T2**:

| Standar | Implementasi |
|---------|-------------|
| Protokol | HTTP/1.1 |
| Format | JSON, UTF-8 |
| Content-Type | application/json |
| Security | X-IAE-KEY header |
| Versioning | /api/v1/... |
| Response wrapper | status, message, data, meta |
| Error format | status, message, errors |
