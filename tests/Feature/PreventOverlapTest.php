<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreventOverlapTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prevents_overlapping_bookings_for_the_same_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $base = $this->baseDate(); // Tomorrow 10 AM

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        $overlaps = [
            ['-30 minutes', '+30 minutes', 'starts before, ends during'],
            ['+30 minutes', '+90 minutes', 'starts during, ends after'],
            ['-30 minutes', '+90 minutes', 'completely overlaps'],
            ['0 minutes', '+60 minutes', 'exact same time'],
            ['+15 minutes', '+45 minutes', 'completely within'],
        ];

        foreach ($overlaps as [$startOffset, $endOffset, $desc]) {
            $payload = [
                'title' => "Overlapping - $desc",
                'start_time' => $base->copy()->modify($startOffset)->format('Y-m-d H:i:s'),
                'end_time' => $base->copy()->modify($endOffset)->format('Y-m-d H:i:s'),
                'user_id' => $user->id,
                'client_id' => $client->id,
            ];

            $this->postJson('/api/bookings', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['user_id'])
                ->assertJsonPath('errors.user_id.0', 'This booking overlaps another booking for the selected user.');

        }

        $this->assertDatabaseCount('bookings', 1);
    }

    #[Test]
    public function it_allows_non_overlapping_bookings_for_same_user(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $base = $this->baseDate();

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        $scenarios = [
            ['-2 hours', '-1 hour', 'Before existing'],
            ['+1 hour', '+2 hours', 'After existing'],
            ['+2 hours', '+3 hours', 'Far after existing'],
            ['+1 day', '+1 day +1 hour', 'Next day'],
        ];

        foreach ($scenarios as [$startOffset, $endOffset, $desc]) {
            $payload = [
                'title' => "Non-overlapping - $desc",
                'start_time' => $base->copy()->modify($startOffset)->format('Y-m-d H:i:s'),
                'end_time' => $base->copy()->modify($endOffset)->format('Y-m-d H:i:s'),
                'user_id' => $user->id,
                'client_id' => $client->id,
            ];

            $this->postJson('/api/bookings', $payload)
                ->assertCreated();
        }

        $this->assertDatabaseCount('bookings', 1 + count($scenarios));
    }

    #[Test]
    public function it_allows_overlapping_bookings_for_different_users(): void
    {
        $base = $this->baseDate();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $client = Client::factory()->create();

        Booking::factory()->create([
            'user_id' => $user1->id,
            'client_id' => $client->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        $payload = [
            'title' => 'Same time, different user',
            'start_time' => $base->format('Y-m-d H:i:s'),
            'end_time' => $base->copy()->addHour()->format('Y-m-d H:i:s'),
            'user_id' => $user2->id,
            'client_id' => $client->id,
        ];

        $this->postJson('/api/bookings', $payload)->assertCreated();
        $this->assertDatabaseCount('bookings', 2);
    }

    #[Test]
    public function it_allows_overlapping_bookings_for_same_user_different_clients(): void
    {
        $user = User::factory()->create();
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $base = $this->baseDate();

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client1->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        $payload = [
            'title' => 'Same time, different client',
            'start_time' => $base->format('Y-m-d H:i:s'),
            'end_time' => $base->copy()->addHour()->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client2->id,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id'])
            ->assertJsonPath('errors.user_id.0', 'This booking overlaps another booking for the selected user.');

        $this->assertDatabaseCount('bookings', 1);
    }

    #[Test]
    public function it_handles_adjacent_bookings_correctly(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $base = $this->baseDate();

        Booking::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'start_time' => $base,
            'end_time' => $base->copy()->addHour(),
        ]);

        $scenarios = [
            ['-1 hour', '0 minutes', 'Ends when next starts'],
            ['+1 hour', '+2 hours', 'Starts when previous ends'],
        ];

        foreach ($scenarios as [$startOffset, $endOffset, $desc]) {
            $payload = [
                'title' => "Adjacent - $desc",
                'start_time' => $base->copy()->modify($startOffset)->format('Y-m-d H:i:s'),
                'end_time' => $base->copy()->modify($endOffset)->format('Y-m-d H:i:s'),
                'user_id' => $user->id,
                'client_id' => $client->id,
            ];

            $this->postJson('/api/bookings', $payload)->assertCreated();
        }

        $this->assertDatabaseCount('bookings', 1 + count($scenarios));
    }

    /**
     * Helper: generates a consistent future base date (tomorrow 10:00 AM)
     */
    private function baseDate(): Carbon
    {
        return now()->addDay()->setTime(10, 0, 0);
    }
}
