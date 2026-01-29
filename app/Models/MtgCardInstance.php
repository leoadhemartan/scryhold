<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MtgCardInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'scryfall_id',
        'location_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the card for this instance.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(MtgCard::class, 'scryfall_id', 'scryfall_id');
    }

    /**
     * Get the location for this instance.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(MtgLocation::class, 'location_id');
    }
}
