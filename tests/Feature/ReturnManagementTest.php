<?php

namespace Tests\Feature;

use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReturnManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_list_hides_non_physical_refunds_and_searches_order_product_or_tracking(): void
    {
        $user = User::factory()->create();
        $physical = $this->makeReturn('JY-SEARCH-001', '579111', 'Celana Cargo Premium');
        $this->makeReturn(null, '579222', 'Produk Refund Saja', [
            'return_source' => 'refund_no_physical',
            'requires_physical_return' => false,
            'tiktok_return_type' => 'Refund only',
            'status' => ReturnStatus::NO_PHYSICAL_RETURN,
        ]);

        $response = $this->actingAs($user)->get(route('returns.index', ['search' => 'Cargo Premium']));

        $response->assertOk()->assertSee($physical->tracking_number)->assertDontSee('Produk Refund Saja');

        $this->actingAs($user)->get(route('returns.index', ['source' => 'refund_no_physical']))
            ->assertOk()->assertSee('Produk Refund Saja');
    }

    public function test_courier_report_moves_a_return_into_investigation(): void
    {
        Carbon::setTestNow('2026-09-25 10:00:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-REPORT-001', '579333', 'Celana Training', [
            'status' => ReturnStatus::NEEDS_REPORTING,
        ]);

        $response = $this->actingAs($user)->post(route('returns.report', $return), [
            'report_date' => '2026-09-25',
            'ticket_number' => 'TKT-88421',
            'courier' => 'J&T Express',
            'notes' => 'Tracking tidak bergerak selama 5 hari.',
            'investigation_status' => 'Dalam proses',
        ]);

        $response->assertRedirect(route('returns.show', $return));
        $this->assertSame(ReturnStatus::UNDER_INVESTIGATION, $return->fresh()->status);
        $this->assertNotNull($return->fresh()->reported_at);
        $this->assertDatabaseHas('courier_reports', ['return_id' => $return->id, 'ticket_number' => 'TKT-88421']);
    }

    public function test_lost_status_requires_an_explicit_admin_confirmation(): void
    {
        Carbon::setTestNow('2026-09-25 11:00:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-LOST-001', '579444', 'Celana Cargo', [
            'status' => ReturnStatus::UNDER_INVESTIGATION,
            'reported_at' => now()->subDays(2),
        ]);

        $this->actingAs($user)->post(route('returns.lost', $return), [
            'confirmation' => 'confirmed',
        ])->assertRedirect(route('returns.show', $return));

        $return->refresh();
        $this->assertSame(ReturnStatus::LOST, $return->status);
        $this->assertSame('2026-09-25 11:00:00', $return->lost_confirmed_at->format('Y-m-d H:i:s'));
        $this->assertSame('not_refunded', $return->courier_refund_status->value);
        $this->assertSame('2026-09-25 11:00:00', $return->no_refund_confirmed_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_manually_mark_a_package_as_received(): void
    {
        Carbon::setTestNow('2026-09-25 13:15:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-MANUAL-001', '579555', 'Celana Cargo', [
            'status' => ReturnStatus::PENDING,
        ]);

        $response = $this->actingAs($user)->post(route('returns.manual-action', $return), [
            'action' => 'received',
        ]);

        $response->assertRedirect(route('returns.show', $return))
            ->assertSessionHas('success');

        $return->refresh();
        $this->assertSame(ReturnStatus::RECEIVED, $return->status);
        $this->assertSame('2026-09-25 13:15:00', $return->received_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->id, $return->received_by);
    }

    public function test_marking_a_package_received_clears_an_obsolete_lost_without_refund_outcome(): void
    {
        Carbon::setTestNow('2026-09-25 13:45:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-MANUAL-CORRECTION', '5795551', 'Celana Cargo', [
            'status' => ReturnStatus::LOST,
            'lost_confirmed_at' => now()->subHour(),
            'courier_refund_status' => 'not_refunded',
            'no_refund_confirmed_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->post(route('returns.manual-action', $return), [
            'action' => 'received',
        ])->assertRedirect(route('returns.show', $return));

        $return->refresh();
        $this->assertSame(ReturnStatus::RECEIVED, $return->status);
        $this->assertSame('not_applicable', $return->courier_refund_status->value);
        $this->assertNull($return->lost_confirmed_at);
        $this->assertNull($return->courier_refunded_at);
        $this->assertNull($return->no_refund_confirmed_at);
    }

    public function test_admin_can_manually_mark_a_package_as_reported(): void
    {
        Carbon::setTestNow('2026-09-25 14:20:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-MANUAL-002', '579556', 'Celana Cargo', [
            'status' => ReturnStatus::NEEDS_REPORTING,
        ]);

        $response = $this->actingAs($user)->post(route('returns.manual-action', $return), [
            'action' => 'reported',
        ]);

        $response->assertRedirect(route('returns.show', $return))
            ->assertSessionHas('success');

        $return->refresh();
        $this->assertSame(ReturnStatus::UNDER_INVESTIGATION, $return->status);
        $this->assertSame('2026-09-25 14:20:00', $return->reported_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_mark_courier_money_as_refunded_without_changing_package_status(): void
    {
        Carbon::setTestNow('2026-09-25 15:30:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-MANUAL-003', '579557', 'Celana Cargo', [
            'status' => ReturnStatus::UNDER_INVESTIGATION,
            'reported_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->post(route('returns.manual-action', $return), [
            'action' => 'refunded',
        ]);

        $response->assertRedirect(route('returns.show', $return))
            ->assertSessionHas('success');

        $return->refresh();
        $this->assertSame(ReturnStatus::UNDER_INVESTIGATION, $return->status);
        $this->assertSame('refunded', $return->courier_refund_status->value);
        $this->assertSame('2026-09-25 15:30:00', $return->courier_refunded_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_mark_a_package_lost_without_a_refund(): void
    {
        Carbon::setTestNow('2026-09-25 16:45:00');
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-MANUAL-004', '579558', 'Celana Cargo', [
            'status' => ReturnStatus::UNDER_INVESTIGATION,
            'reported_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->post(route('returns.manual-action', $return), [
            'action' => 'lost_without_refund',
        ]);

        $response->assertRedirect(route('returns.show', $return))
            ->assertSessionHas('warning');

        $return->refresh();
        $this->assertSame(ReturnStatus::LOST, $return->status);
        $this->assertSame('not_refunded', $return->courier_refund_status->value);
        $this->assertSame('2026-09-25 16:45:00', $return->lost_confirmed_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-25 16:45:00', $return->no_refund_confirmed_at->format('Y-m-d H:i:s'));
    }

    public function test_manual_actions_are_rejected_for_returns_without_a_physical_package(): void
    {
        $user = User::factory()->create();
        $return = $this->makeReturn(null, '579559', 'Celana Cargo', [
            'return_source' => 'refund_no_physical',
            'requires_physical_return' => false,
            'status' => ReturnStatus::NO_PHYSICAL_RETURN,
        ]);

        $response = $this->from(route('returns.show', $return))
            ->actingAs($user)
            ->post(route('returns.manual-action', $return), [
                'action' => 'received',
            ]);

        $response->assertRedirect(route('returns.show', $return))
            ->assertSessionHasErrors('action');

        $return->refresh();
        $this->assertSame(ReturnStatus::NO_PHYSICAL_RETURN, $return->status);
        $this->assertNull($return->received_at);
    }

    public function test_return_detail_displays_the_four_manual_status_controls(): void
    {
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-MANUAL-005', '579560', 'Celana Cargo');

        $response = $this->actingAs($user)->get(route('returns.show', $return));

        $response->assertOk()
            ->assertSee('Update status manual')
            ->assertSee('Paket sudah diterima')
            ->assertSee('Sudah dilaporkan')
            ->assertSee('Uang sudah direfund')
            ->assertSee('Hilang tanpa refund');
    }

    public function test_return_list_displays_a_resolved_courier_refund_outcome(): void
    {
        $user = User::factory()->create();
        $this->makeReturn('JY-MANUAL-006', '579561', 'Celana Cargo', [
            'status' => ReturnStatus::UNDER_INVESTIGATION,
            'reported_at' => now()->subDay(),
            'courier_refund_status' => 'refunded',
            'courier_refunded_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('returns.index'));

        $response->assertOk()
            ->assertSee('JY-MANUAL-006')
            ->assertSee('Sudah direfund');
    }

    private function makeReturn(?string $tracking, string $orderNumber, string $product, array $overrides = []): ReturnRecord
    {
        $order = Order::query()->create([
            'order_number' => $orderNumber,
            'product_name' => $product,
            'variation' => 'L Hitam',
            'quantity' => 1,
        ]);

        return $order->returns()->create(array_merge([
            'tracking_number' => $tracking,
            'return_source' => 'gagal_kirim',
            'requires_physical_return' => true,
            'courier' => 'J&T Express',
            'return_date' => now()->subDays(12),
            'region' => 'Jawa Barat / Bandung',
            'status' => ReturnStatus::NEEDS_INSPECTION,
        ], $overrides));
    }
}
