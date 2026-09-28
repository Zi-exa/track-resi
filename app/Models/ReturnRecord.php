<?php

namespace App\Models;

use App\Enums\CourierRefundStatus;
use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReturnRecord extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'tracking_number',
        'return_source',
        'requires_physical_return',
        'courier',
        'return_date',
        'region',
        'tiktok_return_type',
        'tiktok_status',
        'return_reason',
        'status',
        'received_at',
        'last_tracking_update',
        'reported_at',
        'lost_confirmed_at',
        'courier_refund_status',
        'courier_refunded_at',
        'no_refund_confirmed_at',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'requires_physical_return' => 'boolean',
            'return_date' => 'datetime',
            'status' => ReturnStatus::class,
            'received_at' => 'datetime',
            'last_tracking_update' => 'datetime',
            'reported_at' => 'datetime',
            'lost_confirmed_at' => 'datetime',
            'courier_refund_status' => CourierRefundStatus::class,
            'courier_refunded_at' => 'datetime',
            'no_refund_confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(ScanHistory::class, 'return_id');
    }

    public function courierReport(): HasOne
    {
        return $this->hasOne(CourierReport::class, 'return_id')->latestOfMany();
    }

    public function getAgeInDaysAttribute(): ?int
    {
        if (! $this->return_date || ! $this->requires_physical_return) {
            return null;
        }

        return (int) $this->return_date->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->return_source) {
            'gagal_kirim' => 'Gagal Kirim',
            'refund_delivered' => 'Refund Setelah Diterima',
            'refund_no_physical' => 'Selesai Tanpa Retur Fisik',
            default => ucfirst(str_replace('_', ' ', (string) $this->return_source)),
        };
    }
}
