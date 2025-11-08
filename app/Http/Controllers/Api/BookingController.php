<?php

namespace App\Http\Controllers\Api;

use App\Domain\Bookings\Contracts\BookingRepositoryInterface;
use App\Domain\Bookings\Contracts\BookingServiceInterface;
use App\Domain\Bookings\DTOs\BookingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\WeeklyBookingsRequest;
use App\Http\Resources\BookingResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class BookingController extends Controller
{
    public function __construct(
        private BookingServiceInterface $service,
        private BookingRepositoryInterface $repo
    ) {}

    /**
     * POST /api/bookings
     * Create a new booking.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->service->create(
            BookingData::fromArray($request->validated())
        );

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/bookings?week=YYYY-MM-DD
     * Retrieve all bookings for the given calendar week (Mon–Sun).
     */
    public function index(WeeklyBookingsRequest $request): JsonResponse
    {
        $week = $request->week();

        $startOfWeek = $week->startOfWeek(CarbonImmutable::MONDAY);
        $endOfWeek = $week->endOfWeek(CarbonImmutable::SUNDAY);

        $bookings = $this->repo->forWeek($startOfWeek, $endOfWeek);

        return response()->json([
            'data' => BookingResource::collection($bookings)->resolve(),
            'meta' => [
                'week' => $week->toDateString(),
                'total' => $bookings->count(),
                'week_start' => $startOfWeek->toDateString(),
                'week_end' => $endOfWeek->toDateString(),
            ],
        ]);
    }
}
