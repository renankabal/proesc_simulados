<?php

use App\Http\Controllers\API\LeituraApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/leituras', [LeituraApiController::class, 'store']);
    Route::post('/leituras/qr-info', [LeituraApiController::class, 'qrInfo']);
});
