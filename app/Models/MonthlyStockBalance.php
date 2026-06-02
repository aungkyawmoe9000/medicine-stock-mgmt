<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyStockBalance extends Model
{
    //
    use HasFactory;

    protected $guarded = [];  // Mass assignment

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
