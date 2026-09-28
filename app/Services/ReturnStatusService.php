<?php

namespace App\Services;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ReturnStatusService
{
    public function determine(
        ReturnRecord $return,
        int $maximumDays = 10,
        ?CarbonInterface $today = null,
        int $stagnantDays = 3,
    ): ReturnStatus {
        if (! $return->requires_physical_return || $return->return_source === 'refund_no_physical') {
            return ReturnStatus::NO_PHYSICAL_RETURN;
        }

        if ($return->received_at) {
            return ReturnStatus::RECEIVED;
        }

        if ($return->lost_confirmed_at) {
            return ReturnStatus::LOST;
        }

        if ($return->reported_at) {
            return ReturnStatus::UNDER_INVESTIGATION;
        }

        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $returnDate = CarbonImmutable::instance($return->return_date)->startOfDay();
        $ageInDays = (int) $returnDate->diffInDays($today);

        if ($ageInDays > $maximumDays) {
            if ($this->trackingIsStagnant($return, $today, $stagnantDays)
                || $return->status === ReturnStatus::NEEDS_REPORTING) {
                return ReturnStatus::NEEDS_REPORTING;
            }

            return ReturnStatus::NEEDS_INSPECTION;
        }

        if ($ageInDays >= max(0, $maximumDays - 2)) {
            return ReturnStatus::LATE;
        }

        return ReturnStatus::PENDING;
    }

    public function recompute(ReturnRecord $return, int $maximumDays = 10, int $stagnantDays = 3): ReturnStatus
    {
        $status = $this->determine($return, $maximumDays, stagnantDays: $stagnantDays);

        if ($return->status !== $status) {
            $return->forceFill(['status' => $status])->save();
        }

        return $status;
    }

    private function trackingIsStagnant(
        ReturnRecord $return,
        CarbonInterface $today,
        int $stagnantDays,
    ): bool {
        if (! $return->last_tracking_update) {
            return false;
        }

        return $return->last_tracking_update->startOfDay()->diffInDays($today) >= $stagnantDays;
    }
}
