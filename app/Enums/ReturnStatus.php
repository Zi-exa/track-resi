<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case PENDING = 'belum_diterima';
    case RECEIVED = 'sudah_diterima';
    case LATE = 'terlambat';
    case NEEDS_INSPECTION = 'perlu_diperiksa';
    case NEEDS_REPORTING = 'perlu_dilaporkan';
    case UNDER_INVESTIGATION = 'dalam_investigasi';
    case LOST = 'hilang';
    case UNRECOGNIZED = 'tidak_dikenali';
    case NO_PHYSICAL_RETURN = 'selesai_tanpa_retur_fisik';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Diterima',
            self::RECEIVED => 'Sudah Diterima',
            self::LATE => 'Terlambat',
            self::NEEDS_INSPECTION => 'Perlu Diperiksa',
            self::NEEDS_REPORTING => 'Perlu Dilaporkan',
            self::UNDER_INVESTIGATION => 'Dalam Investigasi',
            self::LOST => 'Hilang',
            self::UNRECOGNIZED => 'Tidak Dikenali',
            self::NO_PHYSICAL_RETURN => 'Selesai Tanpa Retur Fisik',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::RECEIVED, self::NO_PHYSICAL_RETURN => 'success',
            self::PENDING => 'neutral',
            self::LATE => 'warning',
            self::NEEDS_INSPECTION, self::NEEDS_REPORTING => 'danger',
            self::UNDER_INVESTIGATION => 'info',
            self::LOST, self::UNRECOGNIZED => 'dark',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
