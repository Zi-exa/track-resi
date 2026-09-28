<?php

namespace App\Providers;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layout', function ($view): void {
            $physical = ReturnRecord::query()->where('requires_physical_return', true);

            $view->with('navReturnCount', (clone $physical)->count());
            $view->with('navAttentionCount', (clone $physical)->whereIn('status', [
                ReturnStatus::LATE,
                ReturnStatus::NEEDS_INSPECTION,
                ReturnStatus::NEEDS_REPORTING,
            ])->count());
        });
    }
}
