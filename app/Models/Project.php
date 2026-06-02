<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    //Mock Data
    use HasFactory;

    protected $guarded = []; // Mass assignment error protection

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
