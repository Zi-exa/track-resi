<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\ReturnDeadline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'deadlines' => ReturnDeadline::query()->orderBy('region')->get(),
            'settings' => [
                'store_name' => AppSetting::valueFor('store_name', 'Emza Store'),
                'date_format' => AppSetting::valueFor('date_format', 'd M Y'),
                'stagnant_days' => AppSetting::valueFor('stagnant_days', 3),
                'late_days' => AppSetting::valueFor('late_days', 2),
                'report_days' => AppSetting::valueFor('report_days', 7),
                'default_deadline' => ReturnDeadline::query()->where('region', 'Default')->value('maximum_days') ?? 10,
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return to_route('settings.index')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateMonitoring(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stagnant_days' => ['required', 'integer', 'min:1', 'max:30'],
            'late_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'report_days' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        AppSetting::put('stagnant_days', $data['stagnant_days']);
        if (isset($data['late_days'])) {
            AppSetting::put('late_days', $data['late_days']);
        }
        if (isset($data['report_days'])) {
            AppSetting::put('report_days', $data['report_days']);
        }

        return to_route('settings.index')->with('success', 'Konfigurasi monitoring berhasil disimpan.');
    }

    public function updateSystem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'date_format' => ['required', 'string', 'max:50'],
        ]);

        AppSetting::put('store_name', $data['store_name']);
        AppSetting::put('date_format', $data['date_format']);

        return to_route('settings.index')->with('success', 'Konfigurasi sistem berhasil disimpan.');
    }

    public function storeDeadline(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:120', 'unique:return_deadlines,region'],
            'maximum_days' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        ReturnDeadline::query()->create($data);

        return to_route('settings.index')->with('success', "Batas waktu untuk {$data['region']} berhasil ditambahkan.");
    }

    public function updateDeadline(Request $request, ReturnDeadline $deadline): RedirectResponse
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:120', Rule::unique('return_deadlines', 'region')->ignore($deadline->id)],
            'maximum_days' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $deadline->update($data);

        return to_route('settings.index')->with('success', 'Batas waktu wilayah berhasil diperbarui.');
    }

    public function destroyDeadline(ReturnDeadline $deadline): RedirectResponse
    {
        $deadline->delete();

        return to_route('settings.index')->with('success', 'Batas waktu wilayah berhasil dihapus.');
    }
}
