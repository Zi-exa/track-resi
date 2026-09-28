<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ReturnDeadline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_profile_and_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Lama',
            'email' => 'lama@example.com',
            'password' => Hash::make('rahasia-lama'),
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile'), [
            'name' => 'Admin Emza',
            'email' => 'admin@emza.test',
            'current_password' => 'rahasia-lama',
            'password' => 'rahasia-baru',
            'password_confirmation' => 'rahasia-baru',
        ]);

        $response->assertRedirect(route('settings.index'))->assertSessionHas('success');
        $user->refresh();
        $this->assertSame('Admin Emza', $user->name);
        $this->assertSame('admin@emza.test', $user->email);
        $this->assertTrue(Hash::check('rahasia-baru', $user->password));
    }

    public function test_admin_can_update_monitoring_system_and_region_deadlines(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.monitoring'), [
            'stagnant_days' => 4,
        ])->assertSessionHas('success');
        $this->actingAs($user)->put(route('settings.system'), [
            'store_name' => 'Emza Store Bandung',
            'date_format' => 'd/m/Y',
        ])->assertSessionHas('success');
        $this->actingAs($user)->post(route('settings.deadlines.store'), [
            'region' => 'Jawa Barat',
            'maximum_days' => 8,
        ])->assertSessionHas('success');

        $this->assertSame('4', AppSetting::valueFor('stagnant_days'));
        $this->assertSame('Emza Store Bandung', AppSetting::valueFor('store_name'));
        $this->assertDatabaseHas('return_deadlines', ['region' => 'Jawa Barat', 'maximum_days' => 8]);
    }
}
