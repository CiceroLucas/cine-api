<?php

use App\Http\Controllers\API\ReservationController;
use Illuminate\Support\Facades\Route;


Route::post('/reservar', [ReservationController::class, 'reserve']);
