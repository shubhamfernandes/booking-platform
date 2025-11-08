<?php

namespace App\Domain\Bookings\Validation\Rules;

use App\Domain\Bookings\Contracts\BookingRepositoryInterface;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class NoOverlap implements ValidationRule
{
    public function __construct(
        private BookingRepositoryInterface $repo,
        private ?int $ignoreId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->only(['start_time', 'end_time', 'user_id']);

        try {
            $start = new CarbonImmutable($data['start_time']);
            $end = new CarbonImmutable($data['end_time']);
        } catch (\Exception $e) {
            $fail('Invalid start or end date.');

            return;
        }

        if ($data['user_id'] && $this->repo->overlaps((int) $data['user_id'], $start, $end, $this->ignoreId)) {
            $fail('This booking overlaps another booking for the selected user.');
        }
    }
}
