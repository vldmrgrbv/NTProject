<?php

namespace Tests\Feature;

use App\DTOs\AnalyticsDTO;
use App\Enums\MaxBot\BotButton;
use App\Enums\UserEventType;
use App\Models\User;
use App\Models\UserEvent;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService();
    }

    public function test_it_calculates_analytics_correctly(): void
    {
        $user = User::factory()->create();

        // 1. Start count
        UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEventType::START,
        ]);

        // 2. Auth count
        UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEventType::AUTH,
        ]);

        // 3. Button clicks
        UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEventType::BUTTON_CLICK,
            'payload' => ['button' => BotButton::CLUB_PRIVILEGES->value],
        ]);

        UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEventType::BUTTON_CLICK,
            'payload' => ['button' => BotButton::ABOUT_PRODUCTS->value],
        ]);

        // 10. Receipt uploads
        // Create MaxUser for the user to satisfy the new condition
        \App\Models\MaxUser::create([
            'user_id' => $user->id,
            'max_id' => 12345,
        ]);

        \App\Models\Receipt::create([
            'user_id' => $user->id,
            'status' => \App\Enums\ReceiptStatus::PENDING,
            'sum' => '1000',
        ]);

        $data = $this->service->getAnalytics();

        $this->assertEquals(1, $data->startBotCount);
        $this->assertEquals(1, $data->authorizedUsersCount);
        $this->assertEquals(1, $data->clubPrivilegesClicks);
        $this->assertEquals(1, $data->aboutProductsClicks);
        $this->assertEquals(1, $data->receiptUploadsCount);
        $this->assertEquals(0, $data->whereToBuyClicks);

        // Test receipt without max_user (should not be counted)
        $userWithoutMax = User::factory()->create();
        \App\Models\Receipt::create([
            'user_id' => $userWithoutMax->id,
            'status' => \App\Enums\ReceiptStatus::PENDING,
            'sum' => '500',
        ]);

        $data = $this->service->getAnalytics();
        $this->assertEquals(1, $data->receiptUploadsCount);
    }

    public function test_it_filters_by_date(): void
    {
        $user = User::factory()->create();

        // Yesterday
        $yesterday = UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEventType::START,
        ]);
        $yesterday->created_at = now()->subDay();
        $yesterday->save();

        // Today
        UserEvent::create([
            'user_id' => $user->id,
            'event_type' => UserEventType::START,
        ]);

        $dataAll = $this->service->getAnalytics();
        $this->assertEquals(2, $dataAll->startBotCount);

        $dataToday = $this->service->getAnalytics(now());
        $this->assertEquals(1, $dataToday->startBotCount);
    }
}
