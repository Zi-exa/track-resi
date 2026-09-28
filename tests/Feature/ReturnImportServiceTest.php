<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReturnImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_commits_valid_rows_and_skips_duplicate_physical_tracking_numbers(): void
    {
        $user = User::factory()->create();
        $rows = [
            $this->row(['order_number' => '579000000000000001', 'tracking_number' => 'JY0001']),
            $this->row(['order_number' => '579000000000000002', 'tracking_number' => 'JY0001']),
        ];

        $stats = (new ReturnImportService)->commit($rows, 'dibatalkan.csv', 'gagal_kirim', $user);

        $this->assertSame(['total' => 2, 'success' => 1, 'skipped' => 1], $stats);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('returns', 1);
        $this->assertDatabaseHas('returns', ['tracking_number' => 'JY0001', 'return_source' => 'gagal_kirim']);
        $this->assertDatabaseHas('imports', [
            'filename' => 'dibatalkan.csv',
            'total_rows' => 2,
            'success_rows' => 1,
            'failed_rows' => 1,
            'user_id' => $user->id,
        ]);
    }

    public function test_refund_only_rows_are_deduplicated_by_order_and_source(): void
    {
        $user = User::factory()->create();
        $row = $this->row([
            'order_number' => '579000000000000009',
            'tracking_number' => null,
            'return_source' => 'refund_no_physical',
            'requires_physical_return' => false,
            'tiktok_return_type' => 'Refund only',
            'status' => 'selesai_tanpa_retur_fisik',
        ]);
        $service = new ReturnImportService;

        $service->commit([$row], 'refund-1.csv', 'refund', $user);
        $stats = $service->commit([$row], 'refund-2.csv', 'refund', $user);

        $this->assertSame(0, $stats['success']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertDatabaseCount('returns', 1);
        $this->assertDatabaseHas('returns', [
            'return_source' => 'refund_no_physical',
            'courier_refund_status' => 'not_applicable',
        ]);
    }

    /** @return array<string, mixed> */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'order_number' => '579000000000000001',
            'sku_id' => '889000000000000001',
            'product_name' => 'Celana Cargo',
            'variation' => 'XL Hitam',
            'quantity' => 1,
            'order_amount' => 38824,
            'tracking_number' => 'JY0001',
            'return_source' => 'gagal_kirim',
            'requires_physical_return' => true,
            'courier' => 'J&T Express',
            'return_date' => '2026-09-19 10:00:00',
            'region' => 'Jawa Barat / Bandung',
            'tiktok_return_type' => 'Cancel',
            'tiktok_status' => 'Dibatalkan',
            'return_reason' => 'Pengiriman paket gagal',
            'status' => 'belum_diterima',
        ], $overrides);
    }
}
