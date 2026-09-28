<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\ReturnDeadline;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Emza',
            'email' => 'admin@emza.test',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $deadlines = [
            'Default' => 10,
            'Jawa' => 7,
            'Sumatera' => 10,
            'Kalimantan' => 12,
            'Sulawesi' => 12,
            'Bali / Nusa Tenggara' => 12,
            'Papua / Maluku' => 14,
        ];

        foreach ($deadlines as $region => $days) {
            ReturnDeadline::query()->updateOrCreate(['region' => $region], ['maximum_days' => $days]);
        }

        AppSetting::put('store_name', 'Emza Store');
        AppSetting::put('date_format', 'd M Y');
        AppSetting::put('stagnant_days', 3);
        AppSetting::put('late_days', 2);
        AppSetting::put('report_days', 7);

        if (app()->environment('local')) {
            $this->seedSampleReturns();
        }
    }

    private function seedSampleReturns(): void
    {
        if (\App\Models\Order::query()->exists()) {
            return;
        }

        $samples = [
            ['order' => '579123456789012341', 'tracking' => 'JY00123456789', 'product' => 'Celana Cargo Pendek', 'variation' => 'XL Hitam', 'source' => 'gagal_kirim', 'courier' => 'J&T Express', 'region' => 'Jawa Barat / Bandung', 'days' => 2],
            ['order' => '579123456789012342', 'tracking' => 'JX00123456790', 'product' => 'Celana Training', 'variation' => 'L Navy', 'source' => 'refund_delivered', 'courier' => 'J&T Express', 'region' => 'DKI Jakarta / Jakarta Timur', 'days' => 9],
            ['order' => '579123456789012343', 'tracking' => 'JY00123456791', 'product' => 'Celana Cargo Panjang', 'variation' => 'M Cream', 'source' => 'gagal_kirim', 'courier' => 'J&T Express', 'region' => 'Sumatera Utara / Medan', 'days' => 14],
            ['order' => '579123456789012344', 'tracking' => null, 'product' => 'Kemeja Flanel', 'variation' => 'L Merah', 'source' => 'refund_no_physical', 'courier' => null, 'region' => null, 'days' => 1, 'refund_only' => true],
        ];

        foreach ($samples as $s) {
            $order = \App\Models\Order::query()->create([
                'order_number' => $s['order'],
                'product_name' => $s['product'],
                'variation' => $s['variation'],
                'quantity' => 1,
            ]);

            $order->returns()->create([
                'tracking_number' => $s['tracking'],
                'return_source' => $s['source'],
                'requires_physical_return' => ! ($s['refund_only'] ?? false),
                'courier' => $s['courier'],
                'return_date' => now()->subDays($s['days']),
                'region' => $s['region'],
                'tiktok_return_type' => ($s['refund_only'] ?? false) ? 'Refund only' : 'Return and refund',
                'tiktok_status' => 'Dibatalkan',
                'return_reason' => 'Pengiriman paket gagal',
                'status' => ($s['refund_only'] ?? false) ? \App\Enums\ReturnStatus::NO_PHYSICAL_RETURN->value : \App\Enums\ReturnStatus::PENDING->value,
            ]);
        }
    }
}
