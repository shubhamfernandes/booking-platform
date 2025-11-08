<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeeklyBookingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_bookings_for_the_given_calendar_week(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $bookingsInWeek = [
            ['start_time' => '2025-08-04 09:00:00', 'end_time' => '2025-08-04 10:00:00'],
            ['start_time' => '2025-08-05 14:00:00', 'end_time' => '2025-08-05 15:00:00'],
            ['start_time' => '2025-08-07 10:00:00', 'end_time' => '2025-08-07 11:00:00'],
            ['start_time' => '2025-08-10 16:00:00', 'end_time' => '2025-08-10 17:00:00'],
        ];

        foreach ($bookingsInWeek as $data) {
            Booking::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                ...$data,
            ]);
        }

        $response = $this->getJson('/api/bookings?week=2025-08-05');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'start_time',
                        'end_time',
                        'user' => ['id', 'name'],
                        'client' => ['id', 'name'],
                    ],
                ],
                'meta' => [
                    'week',
                    'total',
                    'week_start',
                    'week_end',
                ],
            ])
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.week', '2025-08-05');
    }

    #[Test]
    public function it_returns_empty_array_when_no_bookings_in_week(): void
    {
        // Arrange - No bookings created

        // Act
        $response = $this->getJson('/api/bookings?week=2025-08-05');

        // Assert
        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.week', '2025-08-05');
    }

    #[Test]
    public function it_handles_missing_week_parameter(): void
    {
        // Act
        $response = $this->getJson('/api/bookings');

        // Assert - Could return current week or error
        $response->assertStatus(400)
            ->assertJson(['error' => 'Week parameter is required']);
    }

    #[Test]
    public function it_includes_user_and_client_relationships(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $client = Client::factory()->create(['name' => 'Acme Corp']);

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => '2025-08-05 10:00:00',
            'end_time' => '2025-08-05 11:00:00',
        ]);

        // Act
        $response = $this->getJson('/api/bookings?week=2025-08-05');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.user.name', 'John Doe')
            ->assertJsonPath('data.0.client.name', 'Acme Corp');
    }

    #[Test]
    public function it_orders_bookings_by_start_time(): void
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $bookings = [
            ['start_time' => '2025-08-05 14:00:00', 'title' => 'Afternoon Meeting'],
            ['start_time' => '2025-08-05 09:00:00', 'title' => 'Morning Meeting'],
            ['start_time' => '2025-08-05 11:00:00', 'title' => 'Lunch Meeting'],
        ];

        foreach ($bookings as $bookingData) {
            Booking::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'start_time' => $bookingData['start_time'],
                'end_time' => Carbon::parse($bookingData['start_time'])->addHour(),
                'title' => $bookingData['title'],
            ]);
        }

        // Act
        $response = $this->getJson('/api/bookings?week=2025-08-05');

        // Assert - Should be ordered by start_time
        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Morning Meeting')
            ->assertJsonPath('data.1.title', 'Lunch Meeting')
            ->assertJsonPath('data.2.title', 'Afternoon Meeting');
    }

    #[Test]
    public function it_handles_week_boundaries_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();

        // Edge cases around week boundaries
        $edgeCases = [
            ['start_time' => '2025-08-03 23:59:59', 'end_time' => '2025-08-04 00:59:59', 'in_week' => true], // Spans from prev week
            ['start_time' => '2025-08-04 00:00:00', 'end_time' => '2025-08-04 01:00:00', 'in_week' => true],  // Start of week
            ['start_time' => '2025-08-10 23:00:00', 'end_time' => '2025-08-10 23:59:59', 'in_week' => true],  // End of week
            ['start_time' => '2025-08-10 23:59:59', 'end_time' => '2025-08-11 00:59:59', 'in_week' => false], // Spans to next week
        ];

        foreach ($edgeCases as $case) {
            Booking::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'start_time' => $case['start_time'],
                'end_time' => $case['end_time'],
            ]);
        }

        // Act
        $response = $this->getJson('/api/bookings?week=2025-08-05');

        // Assert - Should only include bookings that are in the week
        $expectedCount = collect($edgeCases)->where('in_week', true)->count();
        $response->assertOk()
            ->assertJsonCount($expectedCount, 'data');
    }
}
