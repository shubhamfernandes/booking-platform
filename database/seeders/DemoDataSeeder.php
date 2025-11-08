<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Booking;

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
        Booking::factory()
            ->count(10)
            ->make()
            ->each(function (Booking $booking) use ($users, $clients) {
                $booking->user_id = $users->random()->id;
                $booking->client_id = $clients->random()->id;
                $booking->save();
            });

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('Users: ' . implode(', ', $users->pluck('name')->toArray()));
        $this->command->info('Clients: ' . implode(', ', $clients->pluck('name')->toArray()));
    }
}
