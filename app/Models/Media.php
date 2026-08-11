<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
