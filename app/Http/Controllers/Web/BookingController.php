<?php

namespace App\Http\Controllers\Web;

use App\Domain\Bookings\Contracts\BookingServiceInterface;
use App\Domain\Bookings\DTOs\BookingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class BookingController extends Controller
{
    public function __construct(private BookingServiceInterface $service) {}

    public function create(): View
    {
        return view('bookings.create', [
            'users' => User::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $this->service->create(BookingData::fromArray($request->validated()));

        return redirect()->back()->with('success', 'Booking created successfully.');
    }
}
