<?php

namespace App\Domain\Bookings\Contracts;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface
{
    /**
     * Persist a new booking to the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Booking;

    /**
     * Check if the given time range overlaps with an existing booking for the same user.
     */
    public function overlaps(
        int $userId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $ignoreId = null
    ): bool;

    /**
     * Retrieve and lock all overlapping bookings for the given user.
     *
     * @return Collection<int, Booking>
     */
    public function lockOverlaps(
        int $userId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): Collection;

    /**
     * Retrieve all bookings within a given week.
     *
     * @return Collection<int, Booking>
     */
    public function forWeek(CarbonImmutable $start, CarbonImmutable $end): Collection;
}
