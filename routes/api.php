<?php

use App\Http\Controllers\API\ReservationController;
use Illuminate\Support\Facades\Route;


Route::post('/reservar', [ReservationController::class, 'reserve']);
Route::post('/reservas/{id}/confirmar', [ReservationController::class, 'confirm']);
Route::get('/sessoes/{id}/assentos', [ReservationController::class, 'getSeats']);