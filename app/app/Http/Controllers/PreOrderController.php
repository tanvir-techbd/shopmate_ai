<?php

namespace App\Http\Controllers;

use App\Models\PreOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreOrderController extends Controller
{
    /**
     * Records demand for a search that matched nothing in the catalogue.
     * There is no product to attach this to (that's the point), so it's a
     * standalone request queue an admin can act on manually for now - see
     * docs/ENRICHMENT_ROADMAP.md for the same "capture demand, act on it
     * manually" pattern used by the duplicate-review queue.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
        ]);

        PreOrderRequest::create([
            'user_id' => Auth::id(),
            'query' => $validated['query'],
            'category' => $validated['category'] ?? null,
            'brand' => $validated['brand'] ?? null,
        ]);

        return back()->with('status', "Got it - we'll let you know if \"{$validated['query']}\" becomes available.");
    }
}
