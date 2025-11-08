<?php

namespace App\Domain\Bookings\Contracts;

use App\Domain\Bookings\DTOs\BookingData;
use App\Models\Booking;

interface BookingServiceInterface
{
    public function create(BookingData $data): Booking;
}
