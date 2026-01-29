<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MtgLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = MtgLocation::withCount([
            'cardInstances as card_count' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(quantity), 0)'));
            }
        ])
        ->with('parentDeck:id,name')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'location_type' => $location->location_type,
                'deck_type' => $location->deck_type,
                'is_default' => $location->is_default,
                'commander' => $location->commander,
                'side_deck_parent' => $location->parentDeck ? $location->parentDeck->name : null,
                'side_deck_parent_id' => $location->side_deck_parent,
                'card_count' => $location->card_count ?? 0,
            ];
        });

        $eligibleParents = MtgLocation::eligibleParents()
            ->select('id', 'name', 'deck_type')
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'deck_type' => $location->deck_type,
                    'label' => "{$location->name} ({$location->deck_type})",
                ];
            });

        return Inertia::render('Admin/Locations/List', [
            'locations' => $locations,
            'eligibleParents' => $eligibleParents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mtg_locations,name',
            'location_type' => 'required|string|in:Storage,Deck,Side Deck',
            'deck_type' => 'nullable|string|in:Standard,Commander,Modern,Legacy,Casual',
            'commander' => 'nullable|string|max:255',
            'side_deck_parent' => 'nullable|exists:mtg_locations,id',
            'is_default' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            // If setting as default, remove default from all other locations
            if ($validated['is_default'] ?? false) {
                MtgLocation::where('is_default', true)->update(['is_default' => false]);
            }

            MtgLocation::create($validated);
        });

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $location = MtgLocation::with('parentDeck', 'cardInstances')
            ->findOrFail($id);

        return Inertia::render('Admin/Locations/Show', [
            'location' => $location,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $location = MtgLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mtg_locations')->ignore($id)],
            'location_type' => 'required|string|in:Storage,Deck,Side Deck',
            'deck_type' => 'nullable|string|in:Standard,Commander,Modern,Legacy,Casual',
            'commander' => 'nullable|string|max:255',
            'side_deck_parent' => 'nullable|exists:mtg_locations,id',
            'is_default' => 'boolean',
        ]);

        DB::transaction(function () use ($location, $validated) {
            // If setting as default, remove default from all other locations
            if ($validated['is_default'] ?? false) {
                MtgLocation::where('is_default', true)
                    ->where('id', '!=', $location->id)
                    ->update(['is_default' => false]);
            }

            $location->update($validated);
        });

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $location = MtgLocation::withCount('cardInstances')->findOrFail($id);

        DB::transaction(function () use ($location) {
            // If this is the default location, set another as default
            if ($location->is_default) {
                $newDefault = MtgLocation::where('id', '!=', $location->id)->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            // Cascade delete will handle card instances
            $location->delete();
        });

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}
