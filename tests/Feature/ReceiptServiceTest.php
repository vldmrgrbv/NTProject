<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Models\Receipt;
use App\Models\User;
use App\Services\ReceiptService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReceiptService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReceiptService;
    }

    public function test_check_limits_daily_limit(): void
    {
        $user = User::create(['phone' => '79991234567']);

        // Create 2 accepted receipts today
        Receipt::create(['user_id' => $user->id, 'status' => ReceiptStatus::ACCEPTED, 'created_at' => Carbon::today()]);
        Receipt::create(['user_id' => $user->id, 'status' => ReceiptStatus::ACCEPTED, 'created_at' => Carbon::today()]);

        $this->assertFalse($this->service->checkLimits($user));
    }

    public function test_check_limits_monthly_limit(): void
    {
        $user = User::create(['phone' => '79991234567']);

        // Create 4 accepted receipts this month (spread across days)
        for ($i = 1; $i <= 4; $i++) {
            Receipt::create([
                'user_id' => $user->id,
                'status' => ReceiptStatus::ACCEPTED,
                'created_at' => Carbon::now()->subDays($i),
            ]);
        }

        $this->assertFalse($this->service->checkLimits($user));
    }

    public function test_check_limits_whitelist_bypass(): void
    {
        $user = User::create(['phone' => '79991234567', 'is_whitelisted' => true]);

        // Create many receipts
        for ($i = 0; $i < 10; $i++) {
            Receipt::create(['user_id' => $user->id, 'status' => ReceiptStatus::ACCEPTED]);
        }

        $this->assertTrue($this->service->checkLimits($user));
    }
}
