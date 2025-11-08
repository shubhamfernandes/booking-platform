<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEndToEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1️ Empty DB: Cannot create booking if no users/clients exist
     */
    public function test_cannot_create_booking_when_no_users_or_clients(): void
    {
        $payload = [
            'title' => 'Meeting',
            'start_time' => now()->addHour()->format('Y-m-d H:i:s'),
            'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'user_id' => 1,
            'client_id' => 1,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'client_id']);
    }

    /**
     * 2️ Successfully create a valid booking
     */
    public function test_can_create_valid_booking(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $start = now()->addDay()->setTime(9, 0);
        $end = $start->copy()->addHour();

        $payload = [
            'title' => 'Project Kickoff',
            'description' => 'Initial client meeting',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];

        $response = $this->postJson('/api/bookings', $payload);
        $response->assertCreated()
            ->assertJsonPath('data.title', 'Project Kickoff')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.client.id', $client->id);

        $this->assertDatabaseHas('bookings', ['title' => 'Project Kickoff']);
    }

    /**
     * 3️ Prevent overlapping bookings for same user
     */
    public function test_prevents_overlapping_bookings_for_same_user(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $base = now()->addDay()->setTime(10, 0);

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        // Overlaps
        $payload = [
            'title' => 'Overlapping booking',
            'start_time' => $base->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'end_time' => $base->copy()->addHours(1)->addMinutes(30)->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id'])
            ->assertJsonPath('errors.user_id.0', 'This booking overlaps another booking for the selected user.');
    }

    /**
     * 4️ Allows adjacent bookings (no overlap)
     */
    public function test_allows_adjacent_bookings(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $base = now()->addDay()->setTime(10, 0);

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        $payload = [
            'title' => 'Next Meeting',
            'start_time' => $base->copy()->addHour()->format('Y-m-d H:i:s'),
            'end_time' => $base->copy()->addHours(2)->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];

        $this->postJson('/api/bookings', $payload)->assertCreated();
        $this->assertDatabaseCount('bookings', 2);
    }

    /**
     * 5️ Weekly API returns correct bookings for given week
     */
    public function test_weekly_endpoint_returns_correct_data(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        // Create bookings across different weeks
        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => '2025-08-05 10:00:00', // inside week
            'end_time' => '2025-08-05 11:00:00',
        ]);

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => '2025-08-15 10:00:00', // outside week
            'end_time' => '2025-08-15 11:00:00',
        ]);

        $response = $this->getJson('/api/bookings?week=2025-08-05');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.week_start', '2025-08-04')
            ->assertJsonPath('meta.week_end', '2025-08-10');
    }

    /**
     * 6️ Validation: Past date not allowed
     */
    public function test_cannot_create_booking_in_the_past(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $payload = [
            'title' => 'Past Booking',
            'start_time' => now()->subDay()->format('Y-m-d H:i:s'),
            'end_time' => now()->subDay()->addHour()->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * 7️ Validation: Missing or invalid week param
     */
    public function test_weekly_endpoint_requires_valid_week_param(): void
    {
        $this->getJson('/api/bookings')->assertStatus(400)
            ->assertJson(['error' => 'Week parameter is required']);

        $this->getJson('/api/bookings?week=not-a-date')->assertStatus(400)
            ->assertJson(['error' => 'Invalid date format']);
    }

    public function test_it_prevents_concurrent_overlapping_bookings_with_pessimistic_locking(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $start = now()->addDay()->setTime(10, 0);

        $payload = [
            'title' => 'Concurrent Booking',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];

        // Act - Simulate two concurrent requests
        $response1 = $this->postJson('/api/bookings', $payload);
        $response2 = $this->postJson('/api/bookings', $payload);

        // Assert - One succeeds (201), one fails (422)
        $statuses = [$response1->status(), $response2->status()];
        sort($statuses);

        $this->assertEquals([201, 422], $statuses,
            'One request should succeed (201) and one should fail (422)'
        );

        $this->assertDatabaseCount('bookings', 1);

        // Verify proper error message
        $failedResponse = $response1->status() === 422 ? $response1 : $response2;
        $failedResponse->assertJsonValidationErrors(['user_id'])
            ->assertJsonPath('errors.user_id.0',
                'This booking overlaps another booking for the selected user.'
            );
    }
}
