<?php

namespace App\Http\Requests;

use App\Domain\Bookings\Validation\Rules\NoOverlap;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // or gate/policy if needed
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'user_id' => ['required', 'exists:users,id', new NoOverlap(app('App\Domain\Bookings\Repositories\BookingRepository'))],
            'client_id' => ['required', 'exists:clients,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_time.after_or_equal' => 'The start time cannot be in the past.',
            'end_time.after' => 'End time must be after the start time.',
        ];
    }
}
