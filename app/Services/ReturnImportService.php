<?php

namespace App\Services;

use App\Enums\CourierRefundStatus;
use App\Models\ImportLog;
use App\Models\Order;
use App\Models\ReturnRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReturnImportService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total: int, success: int, skipped: int}
     */
    public function commit(array $rows, string $filename, string $source, User $user): array
    {
        $success = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $filename, $source, $user, &$success, &$skipped): void {
            foreach ($rows as $row) {
                if ($this->alreadyExists($row)) {
                    $skipped++;

                    continue;
                }

                $order = Order::query()->firstOrCreate(
                    ['order_number' => $row['order_number']],
                    [
                        'sku_id' => $row['sku_id'] ?? null,
                        'product_name' => $row['product_name'],
                        'variation' => $row['variation'] ?? null,
                        'quantity' => $row['quantity'] ?? 1,
                        'order_amount' => $row['order_amount'] ?? null,
                    ],
                );

                $order->returns()->create([
                    'tracking_number' => $row['tracking_number'] ?? null,
                    'return_source' => $row['return_source'],
                    'requires_physical_return' => $row['requires_physical_return'],
                    'courier' => $row['courier'] ?? null,
                    'return_date' => $row['return_date'],
                    'region' => $row['region'] ?? null,
                    'tiktok_return_type' => $row['tiktok_return_type'] ?? null,
                    'tiktok_status' => $row['tiktok_status'] ?? null,
                    'return_reason' => $row['return_reason'] ?? null,
                    'status' => $row['status'],
                    'courier_refund_status' => $row['requires_physical_return']
                        ? CourierRefundStatus::PENDING
                        : CourierRefundStatus::NOT_APPLICABLE,
                ]);

                $success++;
            }

            ImportLog::query()->create([
                'filename' => $filename,
                'source' => $source,
                'total_rows' => count($rows),
                'success_rows' => $success,
                'failed_rows' => $skipped,
                'imported_at' => now(),
                'user_id' => $user->id,
            ]);
        });

        return ['total' => count($rows), 'success' => $success, 'skipped' => $skipped];
    }

    /** @param array<string, mixed> $row */
    private function alreadyExists(array $row): bool
    {
        if (! empty($row['tracking_number'])) {
            return ReturnRecord::query()->where('tracking_number', $row['tracking_number'])->exists();
        }

        return ReturnRecord::query()
            ->where('return_source', $row['return_source'])
            ->whereHas('order', function ($query) use ($row): void {
                $query->where('order_number', $row['order_number'])
                    ->where('product_name', $row['product_name'])
                    ->where('variation', $row['variation'] ?? null);
            })
            ->exists();
    }
}
