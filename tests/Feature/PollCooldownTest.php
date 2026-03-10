<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollCooldownTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: A user who has never created a poll CAN create one.
     */
    public function test_user_can_create_poll_when_no_previous_poll(): void
    {
        $user = User::factory()->create();

        // Check canCreate endpoint
        $response = $this->actingAs($user)->getJson('/api/poll-petition/polls/can-create');
        $response->assertOk()
            ->assertJson([
                'can_create' => true,
                'next_available_date' => null,
            ]);

        // Actually create a poll
        $response = $this->actingAs($user)->postJson('/api/poll-petition/polls', [
            'title' => 'First Poll',
            'description' => 'My first poll ever',
            'options' => ['Option A', 'Option B'],
            'expiry_date' => now()->addDays(14)->toDateString(),
            'category' => 'campus-life',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('polls', ['user_id' => $user->id, 'title' => 'First Poll']);
    }

    /**
     * Test: Creating a second poll within 7 days is BLOCKED (422).
     */
    public function test_user_cannot_create_poll_within_7_day_cooldown(): void
    {
        $user = User::factory()->create();

        // Create a poll 3 days ago
        $poll = Poll::forceCreate([
            'user_id' => $user->id,
            'title' => 'Recent Poll',
            'description' => 'A poll created 3 days ago',
            'category' => 'academic',
            'expires_at' => now()->addDays(14),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        // canCreate should return false
        $response = $this->actingAs($user)->getJson('/api/poll-petition/polls/can-create');
        $response->assertOk()
            ->assertJson([
                'can_create' => false,
            ]);
        $this->assertNotNull($response->json('next_available_date'));

        // Trying to create another poll should be rejected with 422
        $response = $this->actingAs($user)->postJson('/api/poll-petition/polls', [
            'title' => 'Second Poll',
            'description' => 'Trying too soon',
            'options' => ['Yes', 'No'],
            'expiry_date' => now()->addDays(7)->toDateString(),
            'category' => 'campus-life',
        ]);
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'next_available_date']);
        $this->assertStringContainsString('7 days', $response->json('message'));
    }

    /**
     * Test: After 7 days have passed, the user CAN create again.
     */
    public function test_user_can_create_poll_after_cooldown_expires(): void
    {
        $user = User::factory()->create();

        // Create a poll 8 days ago (cooldown expired)
        Poll::forceCreate([
            'user_id' => $user->id,
            'title' => 'Old Poll',
            'description' => 'A poll created 8 days ago',
            'category' => 'events',
            'expires_at' => now()->addDays(6),
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        // canCreate should return true
        $response = $this->actingAs($user)->getJson('/api/poll-petition/polls/can-create');
        $response->assertOk()
            ->assertJson([
                'can_create' => true,
                'next_available_date' => null,
            ]);

        // Creating a new poll should succeed
        $response = $this->actingAs($user)->postJson('/api/poll-petition/polls', [
            'title' => 'New Poll After Cooldown',
            'description' => 'This should work',
            'options' => ['A', 'B', 'C'],
            'expiry_date' => now()->addDays(10)->toDateString(),
            'category' => 'campus-life',
        ]);
        $response->assertStatus(201);
    }

    /**
     * Test: Exactly on day 7 boundary (6 days, 23 hours) – still blocked.
     */
    public function test_cooldown_blocks_at_exactly_6_days(): void
    {
        $user = User::factory()->create();

        // Created exactly 6 days ago
        Poll::forceCreate([
            'user_id' => $user->id,
            'title' => 'Boundary Poll',
            'description' => 'Created 6 days ago',
            'category' => 'feedback',
            'expires_at' => now()->addDays(14),
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        // Should still be blocked (only 6 days passed, need 7)
        $response = $this->actingAs($user)->getJson('/api/poll-petition/polls/can-create');
        $response->assertOk()
            ->assertJson(['can_create' => false]);

        $response = $this->actingAs($user)->postJson('/api/poll-petition/polls', [
            'title' => 'Too Early Poll',
            'description' => 'Should fail',
            'options' => ['Yes', 'No'],
            'expiry_date' => now()->addDays(7)->toDateString(),
            'category' => 'campus-life',
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test: next_available_date is correctly calculated.
     */
    public function test_next_available_date_is_correct(): void
    {
        $user = User::factory()->create();

        $createdAt = now()->subDays(2);
        Poll::forceCreate([
            'user_id' => $user->id,
            'title' => 'Date Check Poll',
            'description' => 'Testing date calculation',
            'category' => 'academic',
            'expires_at' => now()->addDays(14),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $response = $this->actingAs($user)->getJson('/api/poll-petition/polls/can-create');
        $expectedDate = $createdAt->copy()->addDays(7)->toDateString();
        $response->assertOk()
            ->assertJson([
                'can_create' => false,
                'next_available_date' => $expectedDate,
            ]);
    }

    /**
     * Test: Cooldown is per-user (another user's poll doesn't affect you).
     */
    public function test_cooldown_is_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User1 created a recent poll
        Poll::forceCreate([
            'user_id' => $user1->id,
            'title' => 'User1 Poll',
            'description' => 'User1 recent',
            'category' => 'academic',
            'expires_at' => now()->addDays(14),
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        // User2 should NOT be affected — can create freely
        $response = $this->actingAs($user2)->getJson('/api/poll-petition/polls/can-create');
        $response->assertOk()
            ->assertJson(['can_create' => true]);

        $response = $this->actingAs($user2)->postJson('/api/poll-petition/polls', [
            'title' => 'User2 Poll',
            'description' => 'Unaffected by user1',
            'options' => ['X', 'Y'],
            'expiry_date' => now()->addDays(7)->toDateString(),
            'category' => 'campus-life',
        ]);
        $response->assertStatus(201);
    }
}
