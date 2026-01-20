<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LocationController extends Controller
{
    public function index()
    {
        // TODO: Fetch locations from DB
        return Inertia::render('Locations/List');
    }
}
