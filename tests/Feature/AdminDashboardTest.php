<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response
            ->assertStatus(200)
            ->assertJson([
                'users_count' => 1,
                'items_count' => 0,
                'categories_count' => 0,
            ]);
    }

    public function test_admin_dashboard_includes_analytics_for_current_periods(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $now = Carbon::create(2026, 10, 8, 12, 0, 0);
        Carbon::setTestNow($now);

        $visitorA = '11111111-1111-4111-8111-111111111111';
        $visitorB = '22222222-2222-4222-8222-222222222222';
        $visitorC = '33333333-3333-4333-8333-333333333333';
        $visitorD = '44444444-4444-4444-8444-444444444444';

        $events = [
            [AnalyticsEvent::EVENT_VISIT, $visitorA, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $now->copy()->subHour()],
            [AnalyticsEvent::EVENT_VISIT, $visitorA, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $now->copy()->subHours(2)],
            [AnalyticsEvent::EVENT_PROMPT_ACCESS, $visitorB, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', $now->copy()->subHours(3)],
            [AnalyticsEvent::EVENT_PROMPT_COPY, $visitorB, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', $now->copy()->subHours(4)],
            [AnalyticsEvent::EVENT_VISIT, $visitorB, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', $now->copy()->subDays(2)],
            [AnalyticsEvent::EVENT_PROMPT_ACCESS, $visitorB, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', $now->copy()->subDays(2)->addHour()],
            [AnalyticsEvent::EVENT_VISIT, $visitorC, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $now->copy()->startOfMonth()->addDay()],
            [AnalyticsEvent::EVENT_PROMPT_COPY, $visitorC, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $now->copy()->startOfMonth()->addDay()->addHour()],
            [AnalyticsEvent::EVENT_VISIT, $visitorD, 'ffffffff-ffff-4fff-8fff-ffffffffffff', $now->copy()->subDays(40)],
            [AnalyticsEvent::EVENT_PROMPT_ACCESS, $visitorD, 'ffffffff-ffff-4fff-8fff-ffffffffffff', $now->copy()->subDays(40)->addHour()],
        ];

        foreach ($events as [$event, $visitorId, $sessionId, $createdAt]) {
            AnalyticsEvent::create([
                'event' => $event,
                'visitor_id' => $visitorId,
                'session_id' => $sessionId,
                'user_id' => $event === AnalyticsEvent::EVENT_VISIT ? null : $admin->id,
                'created_at' => $createdAt,
            ]);
        }

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response
            ->assertOk()
            ->assertJson([
                'analytics' => [
                    'today' => [
                        'visits' => 2,
                        'unique_visitors' => 1,
                        'prompt_access' => 1,
                        'prompt_copies' => 1,
                    ],
                    'week' => [
                        'visits' => 3,
                        'unique_visitors' => 2,
                        'prompt_access' => 2,
                        'prompt_copies' => 1,
                    ],
                    'month' => [
                        'visits' => 4,
                        'unique_visitors' => 3,
                        'prompt_access' => 2,
                        'prompt_copies' => 2,
                    ],
                    'year' => [
                        'visits' => 5,
                        'unique_visitors' => 4,
                        'prompt_access' => 3,
                        'prompt_copies' => 2,
                    ],
                ],
            ]);
    }

}
