<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create exactly 2 users
        $users = User::factory()->count(2)->create([
            'password' => bcrypt('password'),
        ]);

        // Create exactly 2 clients
        $clients = Client::factory()->count(2)->create();

        // Create 10 demo bookings assigned randomly
        Booking::factory(10)
            ->make()
            ->each(function ($booking) use ($users, $clients) {
                $booking->user_id = $users->random()->id;
                $booking->client_id = $clients->random()->id;

                // Skip if overlap for that user
                $exists = Booking::where('user_id', $booking->user_id)
                    ->where('start_time', '<', $booking->end_time)
                    ->where('end_time', '>', $booking->start_time)
                    ->exists();

                if (! $exists) {
                    $booking->save();
                }
            });

    }
}
