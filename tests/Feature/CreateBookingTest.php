<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_booking_successfully(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $client = Client::factory()->create(['name' => 'Acme Corp']);

        $payload = $this->validPayload($user, $client);

        // Act
        $response = $this->postJson('/api/bookings', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'start_time',
                    'end_time',
                    'user' => ['id', 'name'],
                    'client' => ['id', 'name'],
                ],
            ])
            ->assertJsonPath('data.title', 'Project Kickoff')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'John Doe')
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonPath('data.client.name', 'Acme Corp');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', [
            'title' => 'Project Kickoff',
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/bookings', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'start_time',
                'end_time',
                'user_id',
                'client_id',
            ]);
    }

    #[Test]
    public function it_validates_time_relationships(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $payload = [
            'title' => 'Invalid Time',
            'start_time' => '2025-08-05 11:00:00',
            'end_time' => '2025-08-05 10:00:00',
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    #[Test]
    public function it_handles_optional_description(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $payload = $this->validPayload($user, $client);
        unset($payload['description']);

        $response = $this->postJson('/api/bookings', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('bookings', [
            'title' => 'Project Kickoff',
            'description' => null,
        ]);
    }

    #[Test]
    public function it_prevents_past_booking_times(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $payload = $this->validPayload($user, $client);
        $payload['start_time'] = now()->subDay()->format('Y-m-d H:i:s');
        $payload['end_time'] = now()->subDay()->addHour()->format('Y-m-d H:i:s');

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
    }

    #[Test]
    public function it_validates_user_and_client_existence(): void
    {
        $payload = [
            'title' => 'Non-existent User',
            'start_time' => '2025-08-05 09:00:00',
            'end_time' => '2025-08-05 10:00:00',
            'user_id' => 999,
            'client_id' => 888,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'client_id']);
    }

    #[Test]
    public function it_trims_and_sanitizes_input(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $payload = $this->validPayload($user, $client);
        $payload['title'] = '   Extra Spaces   ';
        $payload['description'] = '   Description with spaces   ';

        $response = $this->postJson('/api/bookings', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Extra Spaces')
            ->assertJsonPath('data.description', 'Description with spaces');

        $this->assertDatabaseHas('bookings', [
            'title' => 'Extra Spaces',
            'description' => 'Description with spaces',
        ]);
    }

    private function validPayload(User $user, Client $client): array
    {
        $start = now()->addDay()->setTime(9, 0, 0);   // Tomorrow 9 AM
        $end = (clone $start)->addHour();           // 10 AM

        return [
            'title' => 'Project Kickoff',
            'description' => 'Initial client meeting',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'client_id' => $client->id,
        ];
    }
}
