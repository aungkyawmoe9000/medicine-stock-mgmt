<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    //Mock data အတွက်
    use HasFactory;

    // Mass assignment error protection
    protected $guarded = [];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
