<?php

namespace App\Enums;

enum CourierRefundStatus: string
{
    case PENDING = 'pending';
    case REFUNDED = 'refunded';
    case NOT_REFUNDED = 'not_refunded';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum ditetapkan',
            self::REFUNDED => 'Sudah direfund',
            self::NOT_REFUNDED => 'Tidak direfund',
            self::NOT_APPLICABLE => 'Tidak berlaku',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::REFUNDED => 'success',
            self::NOT_REFUNDED => 'danger',
            self::NOT_APPLICABLE => 'neutral',
        };
    }
}
