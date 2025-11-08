<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class WeeklyBookingsRequest extends FormRequest
{
    private ?CarbonImmutable $validatedWeek = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // We’ll handle validation manually — no need for Laravel’s 'date' rule.
        return [];
    }

    protected function prepareForValidation(): void
    {
        $week = trim((string) $this->query('week', ''));

        // Case 1: Missing or empty 'week' query
        if ($week === '') {
            throw new HttpResponseException(
                response()->json(['error' => 'Week parameter is required'], 400)
            );
        }

        // Case 2: Invalid date format (e.g. "abc", "2025-13-45")
        try {
            $this->validatedWeek = CarbonImmutable::parse($week);
        } catch (\Throwable $e) {
            throw new HttpResponseException(
                response()->json(['error' => 'Invalid date format'], 400)
            );
        }
    }

    public function week(): CarbonImmutable
    {
        return $this->validatedWeek;
    }
}
