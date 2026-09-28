<?php

namespace Tests\Feature;

use App\Enums\ReturnStatus;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\ReturnDeadline;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InspectionQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_opening_the_queue_recomputes_and_prioritizes_physical_returns(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');
        $user = User::factory()->create();
        ReturnDeadline::query()->create(['region' => 'Default', 'maximum_days' => 10]);
        AppSetting::put('stagnant_days', 3);

        $oldest = $this->makeReturn('JY-OLD', now()->subDays(16));
        $stagnant = $this->makeReturn('JY-STAGNANT', now()->subDays(14), [
            'last_tracking_update' => now()->subDays(4),
        ]);
        $late = $this->makeReturn('JY-LATE', now()->subDays(9));
        $this->makeReturn(null, now()->subDays(30), [
            'return_source' => 'refund_no_physical',
            'requires_physical_return' => false,
            'status' => ReturnStatus::NO_PHYSICAL_RETURN,
        ]);

        $response = $this->actingAs($user)->get(route('inspections.index'));

        $response->assertOk()
            ->assertSeeInOrder(['JY-OLD', 'JY-STAGNANT', 'JY-LATE'])
            ->assertDontSee('Selesai Tanpa Retur Fisik');
        $this->assertSame(ReturnStatus::NEEDS_INSPECTION, $oldest->fresh()->status);
        $this->assertSame(ReturnStatus::NEEDS_REPORTING, $stagnant->fresh()->status);
        $this->assertSame(ReturnStatus::LATE, $late->fresh()->status);
    }

    private function makeReturn(?string $tracking, Carbon $returnDate, array $overrides = []): ReturnRecord
    {
        $order = Order::query()->create([
            'order_number' => uniqid('579', false),
            'product_name' => 'Celana Cargo',
            'variation' => 'L Hitam',
            'quantity' => 1,
        ]);

        return $order->returns()->create(array_merge([
            'tracking_number' => $tracking,
            'return_source' => 'gagal_kirim',
            'requires_physical_return' => true,
            'courier' => 'J&T Express',
            'return_date' => $returnDate,
            'region' => 'Jawa Barat / Bandung',
            'status' => ReturnStatus::PENDING,
        ], $overrides));
    }
}
