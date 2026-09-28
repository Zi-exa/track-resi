<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $table = 'imports';

    protected $fillable = [
        'filename', 'source', 'total_rows', 'success_rows', 'failed_rows', 'imported_at', 'user_id',
    ];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
