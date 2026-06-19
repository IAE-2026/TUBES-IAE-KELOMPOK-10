<?php

return [
    'central_base_url' => env('IAE_CENTRAL_BASE_URL', 'https://iae-sso.virtualfri.id'),
    'central_api_key' => env('IAE_CENTRAL_API_KEY', 'KEY-MHS-149'),
    'nim'             => env('IAE_NIM', '102022400074'),
    'team_id' => env('IAE_TEAM_ID', 'TEAM-10'),
    'service_name' => env('IAE_SERVICE_NAME', 'Absensi-Service'),

    'jwt_issuer' => env('IAE_JWT_ISSUER', 'iae-central-mock'),
    'jwks_cache_seconds' => (int) env('IAE_JWKS_CACHE_SECONDS', 3600),
    'token_cache_seconds_margin' => (int) env('IAE_TOKEN_CACHE_MARGIN', 60),

    'rabbit_exchange' => env('IAE_RABBIT_EXCHANGE', 'iae.central.exchange'),
    'attendance_recorded_routing_key' => env('IAE_ATTENDANCE_RECORDED_ROUTING_KEY', 'absensi.attendance.recorded'),

    'local_roles' => [
        'warga24@ktp.iae.id' => 'hr_staff',
    ],

    'allowed_attendance_roles' => [
        'hr_staff',
    ],
];
