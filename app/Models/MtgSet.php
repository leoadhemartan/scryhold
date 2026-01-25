<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtgSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'set_code',
        'set_name',
        'set_svg_url',
        'set_release_date',
        'set_type',
    ];
}
