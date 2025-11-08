<?php

use App\Http\Controllers\Web\BookingController as WebBooking;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebBooking::class, 'create'])->name('bookings.create');
Route::post('/bookings', [WebBooking::class, 'store'])->name('bookings.store');
