<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_previews_then_commits_a_cancelled_order_csv(): void
    {
        $user = User::factory()->create();
        $csv = implode("\n", [
            'Order ID,Tracking ID,Product Name,Variation,Quantity,Cancelled Time,Shipping Provider Name,Province,Regency and City,Cancel Reason,Cancelation/Return Type,Order Status',
            '579123456789012345,JY-PREVIEW-01,Celana Cargo,XL Hitam,1,19/09/2026 14:30:00,J&T Express,Jawa Barat,Bandung,Pengiriman paket gagal,Cancel,Dibatalkan',
        ]);

        $preview = $this->actingAs($user)->post(route('imports.preview'), [
            'source' => 'gagal_kirim',
            'file' => UploadedFile::fake()->createWithContent('dibatalkan.csv', $csv),
        ]);

        $preview->assertOk()->assertSee('JY-PREVIEW-01')->assertViewHas('rows', fn (array $rows) => count($rows) === 1);
        $preview->assertSessionHas('import_preview');

        $commit = $this->actingAs($user)->post(route('imports.commit'));
        $commit->assertRedirect(route('imports.index'))->assertSessionHas('success');
        $this->assertDatabaseHas('returns', ['tracking_number' => 'JY-PREVIEW-01', 'return_source' => 'gagal_kirim']);
        $this->assertDatabaseHas('orders', ['order_number' => '579123456789012345']);
    }

    public function test_import_requires_an_explicit_source_and_supported_file_type(): void
    {
        $user = User::factory()->create();

        // Auto-detect: source optional, but unsupported file type must still error on file/files
        $this->actingAs($user)->post(route('imports.preview'), [
            'file' => UploadedFile::fake()->create('data.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors(['file']);

        // Without any file → error on files/file
        $this->actingAs($user)->post(route('imports.preview'), [
            'source' => 'gagal_kirim',
        ])->assertSessionHasErrors(['file']);
    }

    public function test_import_auto_detects_source_and_accepts_two_files_at_once(): void
    {
        $user = User::factory()->create();
        $csvA = implode("\n", [
            'Order ID,Tracking ID,Product Name,Variation,Quantity,Cancelled Time,Shipping Provider Name,Province,Regency and City,Cancel Reason,Cancelation/Return Type,Order Status',
            '579123456789012399,JY-AUTO-01,Celana Cargo,XL Hitam,1,19/09/2026 14:30:00,J&T Express,Jawa Barat,Bandung,Pengiriman paket gagal,Cancel,Dibatalkan',
        ]);
        $csvB = implode("\n", [
            'Order ID,Return Logistics Tracking ID,Product Name,SKU Name,Return Quantity,Time Requested,Return Reason,Return Type,Return Status,Return Sub Status',
            '579123456789012400,JX-AUTO-01,Celana Training,L Navy,1,20/09/2026 08:15:00,Garansi sampel,Refund only,Refunded,Completed',
        ]);

        $preview = $this->actingAs($user)->post(route('imports.preview'), [
            'files' => [
                UploadedFile::fake()->createWithContent('a.csv', $csvA),
                UploadedFile::fake()->createWithContent('b.csv', $csvB),
            ],
        ]);

        $preview->assertOk()->assertViewHas('rows', fn (array $rows) => count($rows) === 2);
        $this->assertTrue(collect($preview->viewData('rows'))->pluck('return_source')->contains('gagal_kirim'));
        $this->assertTrue(collect($preview->viewData('rows'))->pluck('return_source')->contains('refund_no_physical'));
    }
}
