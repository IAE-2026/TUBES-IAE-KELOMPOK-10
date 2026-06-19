#!/bin/bash
# ============================================================
# TEST SCRIPT - Tugas 3 IAE - Absensi Service
# Jalankan: bash test_tugas3.sh
# Pastikan service sudah jalan dulu (docker compose up)
# ============================================================

BASE_URL="http://localhost:8002"
SSO_URL="https://iae-sso.virtualfri.id"
SSO_EMAIL="warga24@ktp.iae.id"
SSO_PASS="KtpDigital2026!"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASS=0
FAIL=0

log_ok()   { echo -e "${GREEN}[PASS]${NC} $1"; ((PASS++)); }
log_fail() { echo -e "${RED}[FAIL]${NC} $1"; ((FAIL++)); }
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

echo ""
echo "============================================================"
echo "   TUGAS 3 IAE - ABSENSI SERVICE - FULL INTEGRATION TEST"
echo "============================================================"
echo ""

# ─── STEP 0: Health Check Service Lokal ─────────────────────
log_info "STEP 0: Health check service lokal di $BASE_URL"
HEALTH=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/health" 2>/dev/null)
if [ "$HEALTH" = "200" ] || [ "$HEALTH" = "404" ]; then
    # 404 masih ok, berarti nginx hidup (route /health mungkin tidak ada)
    log_ok "Service lokal hidup (HTTP $HEALTH)"
elif [ "$HEALTH" = "000" ]; then
    log_fail "Service lokal TIDAK bisa diakses. Pastikan 'docker compose up' sudah jalan!"
    echo ""
    echo "  Cara start service:"
    echo "  1. Pastikan network payroll-network sudah ada:"
    echo "     docker network create payroll-network"
    echo "  2. Build dan start:"
    echo "     docker compose up --build -d"
    echo "  3. Tunggu ~30 detik, lalu jalankan script ini lagi."
    exit 1
else
    log_warn "Service lokal return HTTP $HEALTH (mungkin masih booting)"
fi

echo ""

# ─── STEP 1: SSO Health Check ────────────────────────────────
log_info "STEP 1: Health check SSO dosen"
SSO_HEALTH=$(curl -s "$SSO_URL/health" 2>/dev/null)
echo "  Response: $SSO_HEALTH"
if echo "$SSO_HEALTH" | grep -q "ok\|healthy\|status"; then
    log_ok "SSO dosen hidup"
else
    log_warn "SSO response tidak seperti biasanya, lanjut test..."
fi

echo ""

# ─── STEP 2: JWKS Endpoint ───────────────────────────────────
log_info "STEP 2: Cek JWKS endpoint SSO"
JWKS=$(curl -s "$SSO_URL/api/v1/auth/jwks" 2>/dev/null)
if echo "$JWKS" | grep -q '"keys"'; then
    log_ok "JWKS endpoint OK — public keys tersedia"
    KEY_COUNT=$(echo "$JWKS" | python3 -c "import sys,json; d=json.load(sys.stdin); print(len(d.get('keys',[])))" 2>/dev/null)
    log_info "Jumlah keys di JWKS: $KEY_COUNT"
else
    log_fail "JWKS endpoint gagal. Response: ${JWKS:0:200}"
fi

echo ""

# ─── STEP 3: SSO Login via Endpoint Lokal ────────────────────
log_info "STEP 3: Login SSO via endpoint lokal (Modul 1 - Federated SSO)"
log_info "  POST $BASE_URL/api/v1/tugas-3/sso/login"

LOGIN_RESP=$(curl -s -X POST "$BASE_URL/api/v1/tugas-3/sso/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$SSO_EMAIL\",\"password\":\"$SSO_PASS\"}" 2>/dev/null)

echo "  Response: $(echo "$LOGIN_RESP" | python3 -m json.tool 2>/dev/null || echo "$LOGIN_RESP")"

JWT_TOKEN=$(echo "$LOGIN_RESP" | python3 -c "
import sys, json
try:
    d = json.load(sys.stdin)
    # Coba berbagai field token
    data = d.get('data', {})
    t = data.get('token') or data.get('access_token') or d.get('token') or d.get('access_token')
    print(t or '')
except:
    print('')
" 2>/dev/null)

if [ -n "$JWT_TOKEN" ] && [ "$JWT_TOKEN" != "None" ]; then
    log_ok "Login SSO berhasil — JWT token diterima"
    log_info "  Token (50 char pertama): ${JWT_TOKEN:0:50}..."
else
    log_fail "Login SSO gagal — tidak ada token di response"
    log_warn "  Cek apakah service sudah running dan bisa akses internet"
    JWT_TOKEN=""
fi

echo ""

# ─── STEP 4: Test JWT Middleware (tanpa token = 401) ─────────
log_info "STEP 4: Test middleware — request tanpa token harus 401"
NO_AUTH=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/v1/tugas-3/attendances" \
  -H "Content-Type: application/json" \
  -d '{"employee_id":"EMP-001","date":"2026-06-12","status":"hadir"}' 2>/dev/null)

if [ "$NO_AUTH" = "401" ]; then
    log_ok "Middleware benar — request tanpa token return 401"
else
    log_fail "Middleware bermasalah — return $NO_AUTH (harusnya 401)"
fi

echo ""

# ─── STEP 5: Test Record Attendance (Modul 1+2+3 Sekaligus) ──
if [ -n "$JWT_TOKEN" ]; then
    log_info "STEP 5: Record attendance (SSO + SOAP Audit + RabbitMQ)"
    log_info "  POST $BASE_URL/api/v1/tugas-3/attendances"

    # Generate tanggal hari ini untuk hindari duplikat
    TODAY=$(date +%Y-%m-%d)
    log_info "  Tanggal absensi: $TODAY"

    RECORD_RESP=$(curl -s -X POST "$BASE_URL/api/v1/tugas-3/attendances" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -d "{
        \"employee_id\": \"EMP-001\",
        \"date\": \"$TODAY\",
        \"status\": \"hadir\",
        \"note\": \"Test Tugas 3 - Integrasi IAE\"
      }" 2>/dev/null)

    echo ""
    echo "  Response:"
    echo "$RECORD_RESP" | python3 -m json.tool 2>/dev/null || echo "  $RECORD_RESP"
    echo ""

    HTTP_STATUS_RECORD=$(echo "$RECORD_RESP" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('status',''))" 2>/dev/null)
    SOAP_RECEIPT=$(echo "$RECORD_RESP" | python3 -c "
import sys, json
try:
    d = json.load(sys.stdin)
    c = d.get('central', {})
    print(c.get('soap_receipt_number') or '')
except:
    print('')
" 2>/dev/null)
    MQ_EXCHANGE=$(echo "$RECORD_RESP" | python3 -c "
import sys, json
try:
    d = json.load(sys.stdin)
    c = d.get('central', {})
    print(c.get('rabbitmq_exchange') or '')
except:
    print('')
" 2>/dev/null)

    if [ "$HTTP_STATUS_RECORD" = "success" ]; then
        log_ok "Record attendance BERHASIL (status: success)"
    else
        log_fail "Record attendance GAGAL (status: $HTTP_STATUS_RECORD)"
    fi

    if [ -n "$SOAP_RECEIPT" ] && [ "$SOAP_RECEIPT" != "None" ]; then
        log_ok "Modul 2 SOAP Audit OK — ReceiptNumber: $SOAP_RECEIPT"
    else
        log_fail "Modul 2 SOAP Audit — TIDAK ada receipt number di response"
    fi

    if [ -n "$MQ_EXCHANGE" ] && [ "$MQ_EXCHANGE" != "None" ]; then
        log_ok "Modul 3 RabbitMQ OK — Exchange: $MQ_EXCHANGE"
    else
        log_fail "Modul 3 RabbitMQ — TIDAK ada exchange info di response"
    fi

    echo ""

    # ─── STEP 6: Test Duplikasi (harus 422) ──────────────────
    log_info "STEP 6: Test duplikasi absensi hari yang sama (harus 422)"
    DUPE_RESP=$(curl -s -X POST "$BASE_URL/api/v1/tugas-3/attendances" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -d "{
        \"employee_id\": \"EMP-001\",
        \"date\": \"$TODAY\",
        \"status\": \"izin\"
      }" 2>/dev/null)

    DUPE_STATUS=$(echo "$DUPE_RESP" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('status',''))" 2>/dev/null)
    if [ "$DUPE_STATUS" = "error" ]; then
        log_ok "Duplikasi dicegah dengan benar (status: error)"
    else
        log_fail "Duplikasi tidak tertangani (harusnya error)"
    fi
else
    log_warn "STEP 5 & 6 di-skip karena login SSO gagal di STEP 3"
fi

echo ""

# ─── STEP 7: Test Endpoint Lama (Tugas 2) Masih Jalan ────────
log_info "STEP 7: Test endpoint lama Tugas 2 masih jalan"
OLD_RESP=$(curl -s -o /dev/null -w "%{http_code}" \
  -H "X-IAE-KEY: 102022400074" \
  "$BASE_URL/api/v1/attendances" 2>/dev/null)

if [ "$OLD_RESP" = "200" ]; then
    log_ok "Endpoint lama GET /api/v1/attendances masih OK (200)"
else
    log_warn "Endpoint lama return $OLD_RESP (bisa jadi normal jika DB kosong = 200 dengan empty array)"
fi

echo ""

# ─── STEP 8: Cek RabbitMQ Board Dosen ────────────────────────
log_info "STEP 8: Cek board RabbitMQ dosen"
BOARD=$(curl -s "$SSO_URL/board" 2>/dev/null)
if echo "$BOARD" | grep -qi "absensi\|attendance\|TEAM-10"; then
    log_ok "Event absensi terlihat di RabbitMQ board dosen!"
    log_info "  Buka $SSO_URL/board di browser untuk lihat detail"
else
    log_warn "Event belum terlihat di board (mungkin delayed, buka manual: $SSO_URL/board)"
fi

echo ""
echo "============================================================"
echo "   HASIL AKHIR"
echo "============================================================"
echo -e "  ${GREEN}PASS: $PASS${NC}"
echo -e "  ${RED}FAIL: $FAIL${NC}"
TOTAL=$((PASS + FAIL))
if [ $FAIL -eq 0 ]; then
    echo -e "  ${GREEN}SEMUA TEST LULUS — SERVICE 100% SIAP!${NC}"
elif [ $FAIL -le 2 ]; then
    echo -e "  ${YELLOW}Ada $FAIL issue kecil, cek log di atas${NC}"
else
    echo -e "  ${RED}Ada $FAIL issue — perlu dicek lebih lanjut${NC}"
fi
echo "============================================================"
echo ""
echo "  Kalau semua hijau, tunjukkan ke dosen:"
echo "  → RabbitMQ board: $SSO_URL/board"
echo "  → Admin dashboard: $SSO_URL/api/admin/dashboard (butuh X-Admin-Key)"
echo ""
