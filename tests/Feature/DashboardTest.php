<?php

namespace Tests\Feature;

use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_admin_can_log_in_and_log_out_with_a_session(): void
    {
        $user = User::factory()->create(['email' => 'admin@emza.test', 'password' => Hash::make('rahasia123')]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_dashboard_metrics_exclude_refund_without_physical_return_from_the_main_total(): void
    {
        $user = User::factory()->create();
        $this->createReturn('JY1001', ReturnStatus::PENDING, 'gagal_kirim', true);
        $this->createReturn('JX1002', ReturnStatus::RECEIVED, 'refund_delivered', true);
        $this->createReturn(null, ReturnStatus::NO_PHYSICAL_RETURN, 'refund_no_physical', false);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('metrics', fn (array $metrics) => $metrics['total'] === 2
            && $metrics['belum_diterima'] === 1
            && $metrics['sudah_diterima'] === 1
            && $metrics['tanpa_fisik'] === 1);
    }

    private function createReturn(?string $tracking, ReturnStatus $status, string $source, bool $physical): void
    {
        $order = Order::query()->create([
            'order_number' => uniqid('579', true),
            'product_name' => 'Celana Cargo',
            'variation' => 'L Hitam',
            'quantity' => 1,
        ]);

        $order->returns()->create([
            'tracking_number' => $tracking,
            'return_source' => $source,
            'requires_physical_return' => $physical,
            'return_date' => now()->subDays(3),
            'status' => $status,
            'received_at' => $status === ReturnStatus::RECEIVED ? now() : null,
        ]);
    }
}
