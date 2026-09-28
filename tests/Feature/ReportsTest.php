<?php

namespace Tests\Feature;

use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_filters_returns_and_builds_summary(): void
    {
        $user = User::factory()->create();
        $this->makeReturn('JY-REPORT-001', '2026-09-20 10:00:00', ReturnStatus::RECEIVED, 'J&T Express');
        $this->makeReturn('JX-REPORT-002', '2026-08-20 10:00:00', ReturnStatus::LOST, 'SiCepat');

        $response = $this->actingAs($user)->get(route('reports.index', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'status' => ReturnStatus::RECEIVED->value,
            'courier' => 'J&T Express',
        ]));

        $response->assertOk()
            ->assertSee('JY-REPORT-001')
            ->assertDontSee('JX-REPORT-002')
            ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 1
                && $summary[ReturnStatus::RECEIVED->value] === 1);
    }

    public function test_filtered_report_can_be_downloaded_as_csv_and_xlsx(): void
    {
        $user = User::factory()->create();
        $return = $this->makeReturn('JY-EXPORT-001', '2026-09-20 10:00:00', ReturnStatus::RECEIVED, 'J&T Express');
        $return->update([
            'courier_refund_status' => 'refunded',
            'courier_refunded_at' => '2026-09-25 15:30:00',
        ]);
        $filters = ['status' => ReturnStatus::RECEIVED->value];

        $csv = $this->actingAs($user)->get(route('reports.csv', $filters));
        $csv->assertOk()->assertDownload('laporan-retur.csv');
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('JY-EXPORT-001', $csvContent);
        $this->assertStringContainsString('Status Dana Kurir', $csvContent);
        $this->assertStringContainsString('Sudah direfund', $csvContent);

        $xlsx = $this->actingAs($user)->get(route('reports.xlsx', $filters));
        $xlsx->assertOk()->assertDownload('laporan-retur.xlsx');
    }

    private function makeReturn(string $tracking, string $date, ReturnStatus $status, string $courier): ReturnRecord
    {
        $order = Order::query()->create([
            'order_number' => uniqid('579', false),
            'product_name' => 'Celana Cargo',
            'variation' => 'L Hitam',
            'quantity' => 1,
        ]);

        return $order->returns()->create([
            'tracking_number' => $tracking,
            'return_source' => 'gagal_kirim',
            'requires_physical_return' => true,
            'courier' => $courier,
            'return_date' => $date,
            'region' => 'Jawa Barat / Bandung',
            'status' => $status,
            'received_at' => $status === ReturnStatus::RECEIVED ? $date : null,
            'lost_confirmed_at' => $status === ReturnStatus::LOST ? $date : null,
        ]);
    }
}
