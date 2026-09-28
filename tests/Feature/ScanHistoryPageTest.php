<?php

namespace Tests\Feature;

use App\Models\ScanHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_can_be_filtered_by_result_and_tracking_number(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Emza']);
        ScanHistory::query()->create([
            'tracking_number' => 'JY-KNOWN-001',
            'scan_time' => '2026-09-25 10:00:00',
            'result' => 'diterima',
            'user_id' => $admin->id,
        ]);
        ScanHistory::query()->create([
            'tracking_number' => 'JX-UNKNOWN-999',
            'scan_time' => '2026-09-25 10:05:00',
            'result' => 'tidak_dikenali',
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('scan-history.index', [
            'result' => 'tidak_dikenali',
            'search' => 'UNKNOWN',
        ]));

        $response->assertOk()
            ->assertSee('JX-UNKNOWN-999')
            ->assertSee('Admin Emza')
            ->assertDontSee('JY-KNOWN-001');
    }
}
