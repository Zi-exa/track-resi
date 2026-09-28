<?php

namespace Tests\Feature;

use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScanPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_a_known_tracking_number_marks_it_received_and_logs_the_scan(): void
    {
        Carbon::setTestNow('2026-09-25 14:32:00');
        $user = User::factory()->create();
        $return = $this->returnRecord();

        $response = $this->actingAs($user)->post(route('scan.confirm', $return));

        $response->assertRedirect(route('scan.index'));
        $response->assertSessionHas('success');
        $return->refresh();
        $this->assertSame(ReturnStatus::RECEIVED, $return->status);
        $this->assertSame('2026-09-25 14:32:00', $return->received_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $return->received_by);
        $this->assertSame('not_applicable', $return->courier_refund_status->value);
        $this->assertDatabaseHas('scan_histories', [
            'return_id' => $return->id,
            'tracking_number' => 'JY0001',
            'result' => 'diterima',
            'user_id' => $user->id,
        ]);
    }

    public function test_double_scan_never_overwrites_the_original_receipt_time(): void
    {
        Carbon::setTestNow('2026-09-25 15:00:00');
        $user = User::factory()->create();
        $return = $this->returnRecord([
            'status' => ReturnStatus::RECEIVED,
            'received_at' => '2026-09-24 10:10:00',
            'received_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('scan.confirm', $return));

        $response->assertSessionHas('warning');
        $this->assertSame('2026-09-24 10:10:00', $return->fresh()->received_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('scan_histories', ['return_id' => $return->id, 'result' => 'scan_ganda']);
    }

    public function test_unknown_tracking_number_can_be_recorded_without_a_return(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('scan.unknown'), [
            'tracking_number' => 'JX999999999',
        ]);

        $response->assertRedirect(route('scan.index'));
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('scan_histories', [
            'return_id' => null,
            'tracking_number' => 'JX999999999',
            'result' => 'tidak_dikenali',
            'user_id' => $user->id,
        ]);
    }

    private function returnRecord(array $overrides = []): ReturnRecord
    {
        $order = Order::query()->create([
            'order_number' => '579000000000000001',
            'product_name' => 'Celana Cargo',
            'variation' => 'XL Hitam',
            'quantity' => 1,
        ]);

        return $order->returns()->create(array_merge([
            'tracking_number' => 'JY0001',
            'return_source' => 'gagal_kirim',
            'requires_physical_return' => true,
            'courier' => 'J&T Express',
            'return_date' => '2026-09-20 10:00:00',
            'region' => 'Jawa Barat / Bandung',
            'status' => ReturnStatus::PENDING,
        ], $overrides));
    }
}
