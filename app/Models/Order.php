<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'sku_id', 'product_name', 'variation', 'quantity', 'order_amount', 'order_date',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'order_amount' => 'decimal:2',
        ];
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class);
    }
}
