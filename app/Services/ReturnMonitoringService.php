<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ReturnDeadline;
use App\Models\ReturnRecord;
use Illuminate\Support\Collection;

class ReturnMonitoringService
{
    private ?Collection $deadlines = null;

    public function __construct(private readonly ReturnStatusService $statusService)
    {
    }

    public function recomputeAll(): void
    {
        $stagnantDays = (int) AppSetting::valueFor('stagnant_days', 3);

        ReturnRecord::query()
            ->where('requires_physical_return', true)
            ->get()
            ->each(function (ReturnRecord $return) use ($stagnantDays): void {
                $this->statusService->recompute(
                    $return,
                    $this->deadlineFor($return),
                    $stagnantDays,
                );
            });
    }

    public function deadlineFor(ReturnRecord $return): int
    {
        $deadlines = $this->deadlines();
        $defaultDays = (int) ($deadlines['Default'] ?? 10);

        $matched = $deadlines->first(
            fn ($days, $region) => $region !== 'Default'
                && $return->region
                && str_contains(strtolower($return->region), strtolower((string) $region)),
        );

        return (int) ($matched ?? $defaultDays);
    }

    /** @return Collection<string, int> */
    private function deadlines(): Collection
    {
        return $this->deadlines ??= ReturnDeadline::query()->pluck('maximum_days', 'region');
    }
}
