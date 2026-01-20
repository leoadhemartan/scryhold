<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() { return Inertia::render('Admin/Locations/List'); }

    /**
     * Show the form for creating a new resource.
     */
    public function create() { return Inertia::render('Admin/Locations/Create'); }

    /**
     * Store a newly created resource in storage.
     */
    public function store() { /* TODO */ }

    /**
     * Display the specified resource.
     */
    public function show($id) { return Inertia::render('Admin/Locations/Show', ['id' => $id]); }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id) { return Inertia::render('Admin/Locations/Edit', ['id' => $id]); }

    /**
     * Update the specified resource in storage.
     */
    public function update($id) { /* TODO */ }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) { /* TODO */ }
}
