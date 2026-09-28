<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierReport extends Model
{
    use HasFactory;

    protected $fillable = ['return_id', 'courier', 'report_date', 'ticket_number', 'notes', 'investigation_status'];

    protected function casts(): array
    {
        return ['report_date' => 'date'];
    }

    public function returnRecord(): BelongsTo
    {
        return $this->belongsTo(ReturnRecord::class, 'return_id');
    }
}
