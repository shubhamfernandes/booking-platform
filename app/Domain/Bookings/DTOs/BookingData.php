<?php

namespace App\Domain\Bookings\DTOs;

use App\Models\Booking;
use Carbon\CarbonImmutable;

/**
 * Immutable Data Transfer Object representing a Booking entity.
 */
final class BookingData
{
    public function __construct(
        public readonly int $userId,
        public readonly int $clientId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            clientId: (int) $data['client_id'],
            title: trim($data['title']),
            description: isset($data['description']) ? trim($data['description']) : null,
            start: new CarbonImmutable($data['start_time']),
            end: new CarbonImmutable($data['end_time']),
        );
    }

    public static function fromModel(Booking $booking): self
    {
        return new self(
            userId: $booking->user_id,
            clientId: $booking->client_id,
            title: $booking->title,
            description: $booking->description,
            start: new CarbonImmutable($booking->start_time),
            end: new CarbonImmutable($booking->end_time),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'client_id' => $this->clientId,
            'title' => $this->title,
            'description' => $this->description,
            'start_time' => $this->start->toDateTimeString(),
            'end_time' => $this->end->toDateTimeString(),
        ];
    }
}
