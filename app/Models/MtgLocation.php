<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MtgLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location_type',
        'deck_type',
        'is_default',
        'commander',
        'side_deck_parent',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the parent location for side decks.
     */
    public function parentDeck(): BelongsTo
    {
        return $this->belongsTo(MtgLocation::class, 'side_deck_parent');
    }

    /**
     * Get the side decks belonging to this location.
     */
    public function sideDecks(): HasMany
    {
        return $this->hasMany(MtgLocation::class, 'side_deck_parent');
    }

    /**
     * Get the card instances at this location.
     */
    public function cardInstances(): HasMany
    {
        return $this->hasMany(MtgCardInstance::class, 'location_id');
    }

    /**
     * Scope to get only deck locations.
     */
    public function scopeDecks($query)
    {
        return $query->where('location_type', 'Deck');
    }

    /**
     * Scope to get Commander or Standard decks (eligible as side deck parents).
     */
    public function scopeEligibleParents($query)
    {
        return $query->where('location_type', 'Deck')
            ->whereIn('deck_type', ['Commander', 'Standard']);
    }

    /**
     * Scope to get the default location.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
