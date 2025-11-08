<?php

namespace App\Domain\Bookings\Repositories;

use App\Domain\Bookings\Contracts\BookingRepositoryInterface;
use App\Domain\Bookings\Exceptions\BookingPersistenceException;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

final class BookingRepository implements BookingRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Booking
    {
        try {
            return Booking::query()->create($attributes);
        } catch (QueryException $e) {
            throw new BookingPersistenceException('Failed to create booking', 0, $e);
        }
    }

    /**
     * Build the base query for overlapping bookings.
     */
    /**
     * @return Builder<Booking>
     */
    private function overlapQuery(int $userId, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Booking::query()
            ->where('user_id', $userId)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    public function overlaps(
        int $userId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $ignoreId = null
    ): bool {
        return $this->overlapQuery($userId, $start, $end)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * @return Collection<int, Booking>
     */
    public function lockOverlaps(
        int $userId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): Collection {
        return $this->overlapQuery($userId, $start, $end)
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Collection<int, Booking>
     */
    public function forWeek(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Booking::with(['user:id,name', 'client:id,name'])
            ->where('start_time', '<', $end) // overlaps if (start < weekEnd)
            ->where('end_time', '>', $start) // and (end > weekStart)
            ->orderBy('start_time')
            ->get();
    }
}
