<?php

use App\Http\Controllers\Api\BookingController as ApiBooking;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/bookings', [ApiBooking::class, 'index']);   // ?week=YYYY-MM-DD
Route::post('/bookings', [ApiBooking::class, 'store']);   // optional (same rules)

Route::get('/users', fn () => User::select('id', 'name')->get());
Route::get('/clients', fn () => Client::select('id', 'name')->get());
