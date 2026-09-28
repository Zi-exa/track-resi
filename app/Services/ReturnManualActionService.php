<?php

namespace App\Services;

use App\Enums\CourierRefundStatus;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReturnManualActionService
{
    public function __construct(private readonly ReturnStatusService $statusService) {}

    /** @return array{level: string, message: string} */
    public function apply(ReturnRecord $return, string $action, User $user): array
    {
        if (! $return->requires_physical_return) {
            throw ValidationException::withMessages([
                'action' => 'Aksi manual hanya tersedia untuk retur yang memiliki paket fisik.',
            ]);
        }

        if ($action === 'received') {
            $return->update([
                'received_at' => $return->received_at ?? now(),
                'received_by' => $return->received_by ?? $user->id,
                'lost_confirmed_at' => null,
                'courier_refund_status' => CourierRefundStatus::NOT_APPLICABLE,
                'courier_refunded_at' => null,
                'no_refund_confirmed_at' => null,
            ]);
            $this->statusService->recompute($return->refresh());

            return [
                'level' => 'success',
                'message' => 'Paket berhasil ditandai sudah diterima.',
            ];
        }

        if ($action === 'reported') {
            $return->update([
                'reported_at' => $return->reported_at ?? now(),
            ]);
            $this->statusService->recompute($return->refresh());

            return [
                'level' => 'success',
                'message' => 'Paket berhasil ditandai sudah dilaporkan ke kurir.',
            ];
        }

        if ($action === 'refunded') {
            $return->update([
                'courier_refund_status' => CourierRefundStatus::REFUNDED,
                'courier_refunded_at' => $return->courier_refunded_at ?? now(),
                'no_refund_confirmed_at' => null,
            ]);

            return [
                'level' => 'success',
                'message' => 'Dana dari kurir berhasil ditandai sudah direfund.',
            ];
        }

        if ($action === 'lost_without_refund') {
            $return->update([
                'lost_confirmed_at' => $return->lost_confirmed_at ?? now(),
                'courier_refund_status' => CourierRefundStatus::NOT_REFUNDED,
                'courier_refunded_at' => null,
                'no_refund_confirmed_at' => $return->no_refund_confirmed_at ?? now(),
            ]);
            $this->statusService->recompute($return->refresh());

            return [
                'level' => 'warning',
                'message' => 'Paket ditandai hilang tanpa refund dari kurir.',
            ];
        }

        throw new \InvalidArgumentException("Aksi manual tidak didukung: {$action}");
    }
}
