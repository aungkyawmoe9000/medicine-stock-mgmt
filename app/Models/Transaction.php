<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    //
    use HasFactory;

    protected $guarded = []; // Mass assignment protection

    // Make foreign key of the Item model
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // Make foreign key of the Location model
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // Make foreign key of the Grant model
    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    // Make foreign key of the Project model
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Make foreign key of the User model
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
