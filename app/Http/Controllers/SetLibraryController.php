<?php

namespace App\Http\Controllers;

use App\Models\MtgSet;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class SetLibraryController extends Controller
{
    /**
     * Display paginated list of all MTG sets
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'date_desc');

        $query = MtgSet::query();

        // Apply search filter
        if ($search) {
            $query->where('set_name', 'like', '%' . $search . '%');
        }

        // Apply sorting
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('set_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('set_name', 'desc');
                break;
            case 'date_asc':
                $query->orderBy('set_release_date', 'asc');
                break;
            case 'date_desc':
            default:
                $query->orderBy('set_release_date', 'desc');
                break;
        }

        $sets = $query->paginate(24)->withQueryString();

        return Inertia::render('SetLibrary', [
            'sets' => $sets,
            'filters' => [
                'search' => $search,
                'sort' => $sort
            ]
        ]);
    }
}
