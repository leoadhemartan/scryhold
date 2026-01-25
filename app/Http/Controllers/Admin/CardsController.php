<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\MtgSet;

class CardsController extends Controller
{
    public function index()
    {
        // Render the cards management page with modal
        return Inertia::render('Admin/Cards/List');
    }

    public function updateSetLibrary()
    {
        try {
            // Call Scryfall API to get all sets
            $response = Http::get('https://api.scryfall.com/sets');

            if (!$response->successful()) {
                return redirect()->back()->with([
                    'flash' => [
                        'success' => false,
                        'message' => 'Failed to fetch sets from Scryfall API',
                        'imported' => 0,
                        'errors' => ['API request failed'],
                        'log' => ['Failed to fetch sets from Scryfall API']
                    ]
                ]);
            }

            $sets = $response->json()['data'] ?? [];
            
            // Get existing set codes from database
            $existingSetCodes = MtgSet::pluck('set_code')->toArray();
            
            $imported = 0;
            $errors = [];
            $log = [];
            
            $log[] = "Fetched " . count($sets) . " sets from Scryfall API";
            $log[] = "Found " . count($existingSetCodes) . " existing sets in database";

            foreach ($sets as $setData) {
                try {
                    $setCode = $setData['code'] ?? null;
                    $setName = $setData['name'] ?? null;
                    $iconSvgUri = $setData['icon_svg_uri'] ?? null;
                    $setType = $setData['set_type'] ?? null;
                    $releasedAt = $setData['released_at'] ?? null;

                    if (!$setCode || !$setName) {
                        continue;
                    }

                    // Skip if set already exists
                    if (in_array($setCode, $existingSetCodes)) {
                        continue;
                    }

                    // Download SVG if available
                    $svgPath = null;
                    if ($iconSvgUri) {
                        try {
                            $svgContent = Http::get($iconSvgUri)->body();
                            $filename = "{$setCode}.svg";
                            Storage::disk('public')->put("sets/{$filename}", $svgContent);
                            $svgPath = "sets/{$filename}";
                        } catch (\Exception $e) {
                            $errors[] = "Failed to download SVG for {$setCode}: {$e->getMessage()}";
                        }
                    }

                    // Create new set in database
                    MtgSet::create([
                        'set_code' => $setCode,
                        'set_name' => $setName,
                        'set_svg_url' => $svgPath,
                        'set_type' => $setType,
                        'set_release_date' => $releasedAt,
                    ]);

                    $log[] = "✓ Added: [{$setCode}] {$setName}" . ($setType ? " ({$setType})" : "");
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Error processing set {$setCode}: {$e->getMessage()}";
                }
            }

            $log[] = "---";
            $log[] = "Import completed: {$imported} new sets added to database";
            if (count($errors) > 0) {
                $log[] = "Encountered " . count($errors) . " errors during import";
            }

            return redirect()->back()->with([
                'flash' => [
                    'success' => true,
                    'message' => "Successfully imported {$imported} new sets",
                    'imported' => $imported,
                    'errors' => $errors,
                    'log' => $log
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'flash' => [
                    'success' => false,
                    'message' => 'Error updating set library: ' . $e->getMessage(),
                    'imported' => 0,
                    'errors' => [$e->getMessage()],
                    'log' => ['Error: ' . $e->getMessage()]
                ]
            ]);
        }
    }
}