<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Service — Dylan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #0a0e1a;
            color: #e2e8f0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated gradient background */
        .bg-glow {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        .bg-glow::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            top: -100px; left: -100px;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
            animation: float1 8s ease-in-out infinite;
        }
        .bg-glow::after {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            bottom: -100px; right: -100px;
            background: radial-gradient(circle, rgba(14,165,233,0.12) 0%, transparent 70%);
            animation: float2 10s ease-in-out infinite;
        }
        @keyframes float1 { 0%,100%{ transform: translate(0,0); } 50%{ transform: translate(80px,60px); } }
        @keyframes float2 { 0%,100%{ transform: translate(0,0); } 50%{ transform: translate(-60px,-80px); } }

        .container {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 24px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 48px;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            background: rgba(34,197,94,0.12);
            color: #4ade80;
            border: 1px solid rgba(34,197,94,0.2);
            margin-bottom: 20px;
            animation: pulse-badge 2s ease-in-out infinite;
        }
        @keyframes pulse-badge {
            0%,100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.3); }
            50% { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
        }
        .header h1 {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8 0%, #38bdf8 50%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            margin-bottom: 12px;
        }
        .header p {
            font-size: 16px;
            color: #94a3b8;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Info cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }
        .info-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .info-card:hover {
            border-color: rgba(129,140,248,0.3);
            background: rgba(255,255,255,0.05);
            transform: translateY(-2px);
        }
        .info-card .label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }
        .info-card .value {
            font-size: 18px;
            font-weight: 700;
            color: #f1f5f9;
        }
        .info-card .value.accent { color: #818cf8; }
        .info-card .value.green { color: #4ade80; }
        .info-card .value.cyan { color: #38bdf8; }

        /* Endpoints section */
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title::before {
            content: '';
            width: 4px; height: 24px;
            background: linear-gradient(180deg, #818cf8, #38bdf8);
            border-radius: 4px;
        }

        .endpoint-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 40px;
        }
        .endpoint {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            transition: all 0.25s ease;
        }
        .endpoint:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            transform: translateX(4px);
        }
        .method {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            min-width: 52px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .method.get { background: rgba(34,197,94,0.15); color: #4ade80; }
        .method.post { background: rgba(99,102,241,0.15); color: #a5b4fc; }
        .path {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 13px;
            color: #cbd5e1;
            flex: 1;
        }
        .endpoint .desc {
            font-size: 12px;
            color: #64748b;
            text-align: right;
        }

        /* Alur section */
        .flow-section {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 40px;
        }
        .flow-steps {
            display: flex;
            flex-direction: column;
            gap: 0;
            margin-top: 20px;
        }
        .flow-step {
            display: flex;
            gap: 16px;
            position: relative;
        }
        .flow-step .dot-line {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 28px;
        }
        .flow-step .dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #818cf8;
            border: 2px solid #0a0e1a;
            box-shadow: 0 0 0 3px rgba(129,140,248,0.2);
            z-index: 1;
            flex-shrink: 0;
        }
        .flow-step .line {
            width: 2px;
            flex: 1;
            background: rgba(129,140,248,0.15);
        }
        .flow-step:last-child .line { display: none; }
        .flow-step .content {
            padding-bottom: 24px;
        }
        .flow-step .step-title {
            font-size: 14px;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 4px;
        }
        .flow-step .step-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }
        .flow-step .step-owner {
            font-size: 11px;
            font-weight: 600;
            color: #818cf8;
            margin-top: 4px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 32px;
            border-top: 1px solid rgba(255,255,255,0.05);
            color: #475569;
            font-size: 13px;
        }
        .footer a {
            color: #818cf8;
            text-decoration: none;
        }
        .footer a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .header h1 { font-size: 28px; }
            .info-grid { grid-template-columns: 1fr 1fr; }
            .endpoint .desc { display: none; }
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="badge">● Service Online</div>
            <h1>Absensi Service</h1>
            <p>Subprocess Rekap Kehadiran Karyawan — Sistem Penggajian Karyawan IAE</p>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="label">Nama</div>
                <div class="value">Muhammad Dylan N.M.</div>
            </div>
            <div class="info-card">
                <div class="label">NIM</div>
                <div class="value accent">102022400074</div>
            </div>
            <div class="info-card">
                <div class="label">Version</div>
                <div class="value cyan">v1.0</div>
            </div>
            <div class="info-card">
                <div class="label">Status</div>
                <div class="value green">✓ Running</div>
            </div>
        </div>

        <!-- Endpoints -->
        <div class="section-title">API Endpoints — Absensi</div>
        <div class="endpoint-list">
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="path">/api/v1/attendances</span>
                <span class="desc">Semua data absensi</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="path">/api/v1/attendances/{start}/{end}</span>
                <span class="desc">Absensi per periode</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="path">/api/v1/attendances/summary/{id}/{year}/{month}</span>
                <span class="desc">Rekap bulanan</span>
            </div>
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="path">/api/v1/attendances</span>
                <span class="desc">Catat absensi harian</span>
            </div>
        </div>

        <div class="section-title">API Endpoints — Tugas 3 (SSO + Audit + MQ)</div>
        <div class="endpoint-list">
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="path">/api/v1/tugas-3/sso/login</span>
                <span class="desc">Login via SSO Dosen</span>
            </div>
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="path">/api/v1/tugas-3/attendances</span>
                <span class="desc">Absensi + SOAP + RabbitMQ</span>
            </div>
        </div>

        <!-- Alur Sistem -->
        <div class="flow-section">
            <div class="section-title">Alur Sistem Penggajian</div>
            <div class="flow-steps">
                <div class="flow-step">
                    <div class="dot-line"><div class="dot"></div><div class="line"></div></div>
                    <div class="content">
                        <div class="step-title">1. Pendataan Karyawan</div>
                        <div class="step-desc">HR Admin input data karyawan → sistem generate employee_id</div>
                        <div class="step-owner">Service: Data Karyawan — Dimas</div>
                    </div>
                </div>
                <div class="flow-step">
                    <div class="dot-line"><div class="dot" style="background:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,0.2)"></div><div class="line"></div></div>
                    <div class="content">
                        <div class="step-title">2. Rekap Kehadiran Karyawan</div>
                        <div class="step-desc">Catat absensi harian → validasi employee_id → rekap bulanan untuk payroll</div>
                        <div class="step-owner" style="color:#38bdf8">Service: Absensi Karyawan — Dylan ← Kamu di sini</div>
                    </div>
                </div>
                <div class="flow-step">
                    <div class="dot-line"><div class="dot" style="background:#34d399;box-shadow:0 0 0 3px rgba(52,211,153,0.2)"></div></div>
                    <div class="content">
                        <div class="step-title">3. Proses Payroll</div>
                        <div class="step-desc">Hitung gaji berdasarkan kehadiran → generate slip gaji</div>
                        <div class="step-owner" style="color:#34d399">Service: Payroll Karyawan — Farhan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Absensi Service &copy; 2026 — Tugas Besar IAE &middot; TEAM-10 &middot;
            SSO: <a href="https://iae-sso.virtualfri.id" target="_blank">iae-sso.virtualfri.id</a>
        </div>
    </div>
</body>
</html>
