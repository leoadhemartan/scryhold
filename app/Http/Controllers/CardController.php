<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class CardController extends Controller
{
    public function index()
    {
        // TODO: Fetch cards from DB
        return Inertia::render('Cards/List');
    }

    public function show($id)
    {
        // TODO: Fetch card details from DB
        return Inertia::render('Cards/Show', ['id' => $id]);
    }
}
