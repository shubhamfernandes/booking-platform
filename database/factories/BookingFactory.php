<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        // Generate a start time between 8 AM and 4 PM within next 5 days
        $start = now()
            ->addDays(rand(0, 5))
            ->setTime(rand(8, 16), [0, 30][rand(0, 1)]);

        $end = (clone $start)->addHour();

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'start_time' => $start,
            'end_time' => $end,
        ];
    }
}
