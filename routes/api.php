<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ReservationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/sessoes/{id}/assentos', [ReservationController::class, 'getSeats']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/reservar', [ReservationController::class, 'reserve']);
    Route::post('/reservas/{id}/confirmar', [ReservationController::class, 'confirm']);
});
