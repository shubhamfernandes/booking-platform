<?php

namespace App\Domain\Bookings\Services;

use App\Domain\Bookings\Contracts\BookingRepositoryInterface;
use App\Domain\Bookings\Contracts\BookingServiceInterface;
use App\Domain\Bookings\DTOs\BookingData;
use App\Domain\Bookings\Exceptions\BookingOverlapException;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

final class BookingService implements BookingServiceInterface
{
    public function __construct(private readonly BookingRepositoryInterface $repo) {}

    public function create(BookingData $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // Lock rows that would overlap, inside the transaction
            $conflicts = $this->repo->lockOverlaps($data->userId, $data->start, $data->end);

            if ($conflicts->isNotEmpty()) {
                throw new BookingOverlapException('This booking overlaps another booking for the selected user.');
            }

            return $this->repo->create($data->toArray());
        });
    }
}
