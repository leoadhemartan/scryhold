<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtgCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'scryfall_id',
        'name',
        'layout',
        'lang',
        'type_line',
        'mana_cost',
        'oracle_text',
        'cfl_name',
        'cfl_mana_cost',
        'cfl_type_line',
        'cfl_oracle_text',
        'cfr_name',
        'cfr_mana_cost',
        'cfr_type_line',
        'cfr_oracle_text',
        'image_uri',
        'cfl_image_uri',
        'cfr_image_uri',
        'scryfall_json',
    ];

    protected $casts = [
        'scryfall_json' => 'array',
    ];

    /**
     * Get the card instances of this card.
     */
    public function cardInstances()
    {
        return $this->hasMany(MtgCardInstance::class, 'scryfall_id', 'scryfall_id');
    }
}
