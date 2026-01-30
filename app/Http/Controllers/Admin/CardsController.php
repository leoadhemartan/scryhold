<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\MtgSet;
use App\Models\MtgCard;
use App\Models\MtgLocation;
use App\Models\MtgCardInstance;

class CardsController extends Controller
{
    public function index()
    {
        // Fetch paginated cards from database with their instances
        $cardsQuery = MtgCard::with(['cardInstances.location'])
            ->orderBy('created_at', 'desc')
            ->paginate(18); // 18 cards per page for a 3x6 or 2x9 grid
        
        // Transform the paginated data
        $cardsData = $cardsQuery->through(function ($card) {
            return [
                'id' => $card->id,
                'scryfall_id' => $card->scryfall_id,
                'name' => $card->name,
                'type_line' => $card->type_line,
                'layout' => $card->layout,
                'lang' => $card->lang,
                'image_uri' => $card->image_uri,
                'cfl_image_uri' => $card->cfl_image_uri,
                'cfr_image_uri' => $card->cfr_image_uri,
                'created_at' => $card->created_at,
                'total_quantity' => $card->cardInstances->sum('quantity'),
                'locations' => $card->cardInstances->sortBy(function ($instance) {
                    return $instance->location->name ?? '';
                })->map(function ($instance) {
                    return [
                        'location_id' => $instance->location_id,
                        'location_name' => $instance->location->name ?? 'Unknown',
                        'quantity' => $instance->quantity,
                    ];
                })->values(),
            ];
        });
        
        // Fetch all locations for the location selector
        $locations = MtgLocation::select('id', 'name')->orderBy('name')->get();
        
        // Render the cards management page with modal
        return Inertia::render('Admin/Cards/List', [
            'cards' => $cardsData,
            'locations' => $locations
        ]);
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

    public function store(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'scryfall_data' => 'required|array',
                'location_id' => 'required|exists:mtg_locations,id',
            ]);

            $scryfallData = $validated['scryfall_data'];
            $scryfallId = $scryfallData['id'] ?? null;
            $locationId = $validated['location_id'];

            if (!$scryfallId) {
                return back()->withErrors(['message' => 'Invalid card data: missing Scryfall ID']);
            }

            // Begin transaction for atomic operations
            DB::beginTransaction();

            try {
                $cardCreated = false;
                $quantityIncremented = false;

                // Check if card already exists in mtg_cards table
                $existingCard = MtgCard::where('scryfall_id', $scryfallId)->first();

                if (!$existingCard) {
                    // Card doesn't exist - create new card
                    $cardData = [
                        'scryfall_id' => $scryfallId,
                        'name' => $scryfallData['name'] ?? '',
                        'layout' => $scryfallData['layout'] ?? 'normal',
                        'lang' => $scryfallData['lang'] ?? 'en',
                        'scryfall_json' => $scryfallData,
                    ];

                    // Handle image downloads and storage
                    $imageResults = $this->processCardImages($scryfallData);
                    $cardData = array_merge($cardData, $imageResults);

                    // Process single-faced vs multi-faced cards
                    if (!isset($scryfallData['card_faces']) || empty($scryfallData['card_faces'])) {
                        // Single-faced card
                        $cardData['type_line'] = $scryfallData['type_line'] ?? null;
                        $cardData['mana_cost'] = $scryfallData['mana_cost'] ?? null;
                        $cardData['oracle_text'] = $scryfallData['oracle_text'] ?? null;
                    } else {
                        // Multi-faced card
                        $faces = $scryfallData['card_faces'];
                        
                        if (isset($faces[0])) {
                            $cardData['cfl_name'] = $faces[0]['name'] ?? null;
                            $cardData['cfl_mana_cost'] = $faces[0]['mana_cost'] ?? null;
                            $cardData['cfl_type_line'] = $faces[0]['type_line'] ?? null;
                            $cardData['cfl_oracle_text'] = $faces[0]['oracle_text'] ?? null;
                        }
                        
                        if (isset($faces[1])) {
                            $cardData['cfr_name'] = $faces[1]['name'] ?? null;
                            $cardData['cfr_mana_cost'] = $faces[1]['mana_cost'] ?? null;
                            $cardData['cfr_type_line'] = $faces[1]['type_line'] ?? null;
                            $cardData['cfr_oracle_text'] = $faces[1]['oracle_text'] ?? null;
                        }
                    }

                    // Create the card
                    $card = MtgCard::create($cardData);
                    $cardCreated = true;

                    Log::info("Created new card in mtg_cards: {$scryfallId}");
                } else {
                    Log::info("Card already exists in mtg_cards: {$scryfallId}");
                }

                // Check if card instance already exists for this location
                $existingInstance = MtgCardInstance::where('scryfall_id', $scryfallId)
                    ->where('location_id', $locationId)
                    ->first();

                if ($existingInstance) {
                    // Card instance exists - increment quantity
                    $existingInstance->increment('quantity');
                    $quantityIncremented = true;
                    
                    Log::info("Incremented quantity for card instance: {$scryfallId} at location {$locationId}, new quantity: {$existingInstance->quantity}");

                    $message = "Card quantity updated to {$existingInstance->quantity} at the selected location";
                } else {
                    // Card instance doesn't exist - create new instance
                    MtgCardInstance::create([
                        'scryfall_id' => $scryfallId,
                        'location_id' => $locationId,
                        'quantity' => 1,
                    ]);

                    Log::info("Created new card instance: {$scryfallId} at location {$locationId}");

                    $message = 'Card added to library successfully';
                }

                // Commit transaction
                DB::commit();

                return back()->with('success', $message);

            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error saving card: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['message' => 'Failed to save card: ' . $e->getMessage()]);
        }
    }

    private function processCardImages(array $scryfallData): array
    {
        $result = [
            'image_uri' => null,
            'cfl_image_uri' => null,
            'cfr_image_uri' => null,
        ];

        try {
            $hasFaces = isset($scryfallData['card_faces']) && !empty($scryfallData['card_faces']);
            $faces = $hasFaces ? $scryfallData['card_faces'] : [];

            // Check if card_faces have image_uris
            $face0HasImage = $hasFaces && isset($faces[0]['image_uris']['normal']);
            $face1HasImage = $hasFaces && isset($faces[1]['image_uris']['normal']);

            if ($hasFaces && $face0HasImage && $face1HasImage) {
                // Multi-faced card with images in both faces
                $result['cfl_image_uri'] = $this->downloadAndSaveImage(
                    $faces[0]['image_uris']['normal'],
                    $scryfallData['id'],
                    'front',
                    '_face0'
                );

                $result['cfr_image_uri'] = $this->downloadAndSaveImage(
                    $faces[1]['image_uris']['normal'],
                    $scryfallData['id'],
                    'back',
                    '_face1'
                );
            } elseif (isset($scryfallData['image_uris']['normal'])) {
                // Single-faced card or shared image
                $result['image_uri'] = $this->downloadAndSaveImage(
                    $scryfallData['image_uris']['normal'],
                    $scryfallData['id'],
                    'front'
                );
            }
        } catch (\Exception $e) {
            Log::error('Error processing card images: ' . $e->getMessage());
            // Continue without images
        }

        return $result;
    }

    private function downloadAndSaveImage(string $imageUrl, string $scryfallId, string $folder, string $suffix = ''): ?string
    {
        try {
            // Download image
            $response = Http::timeout(30)->get($imageUrl);
            
            if (!$response->successful()) {
                Log::error("Failed to download image from {$imageUrl}");
                return null;
            }

            // Generate filename
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $scryfallId . $suffix . '.' . $extension;
            $path = "{$folder}/{$filename}";

            // Save to storage
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Exception $e) {
            Log::error("Error downloading image: " . $e->getMessage());
            return null;
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $card = MtgCard::findOrFail($id);
            $scryfallId = $card->scryfall_id;
            
            // Delete all card instances associated with this card
            MtgCardInstance::where('scryfall_id', $scryfallId)->delete();
            
            Log::info("Deleted all card instances for scryfall_id: {$scryfallId}");
            
            // Delete associated images
            if ($card->image_uri) {
                Storage::disk('public')->delete($card->image_uri);
            }
            if ($card->cfl_image_uri) {
                Storage::disk('public')->delete($card->cfl_image_uri);
            }
            if ($card->cfr_image_uri) {
                Storage::disk('public')->delete($card->cfr_image_uri);
            }
            
            // Delete the card
            $card->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Card and all instances deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting card: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete card'
            ], 500);
        }
    }
}
