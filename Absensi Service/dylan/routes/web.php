<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service'     => 'Absensi-Service',
        'version'     => 'v1',
        'nim'         => '102022400074',
        'docs'        => '/api/documentation',
    ]);
});
