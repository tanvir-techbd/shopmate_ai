<?php

namespace App\Http\Controllers;

use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PriceAlertController extends Controller
{
    public function index(): View
    {
        $alerts = Auth::user()->priceAlerts()
            ->with('product.prices.store')
            ->latest()
            ->get();

        return view('alerts.index', ['alerts' => $alerts]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:price_drop,restock',
        ]);

        $product = Product::with('prices')->findOrFail($validated['product_id']);
        $cheapest = $product->prices->sortBy(fn ($p) => (float) $p->price + (float) $p->delivery_charge)->first();

        $exists = Auth::user()->priceAlerts()
            ->where('product_id', $product->id)
            ->where('type', $validated['type'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            return back()->with('status', 'You already have an active alert for this product.');
        }

        Auth::user()->priceAlerts()->create([
            'product_id' => $product->id,
            'type' => $validated['type'],
            'target_price' => $validated['type'] === 'price_drop' ? $cheapest?->price : null,
            'is_active' => true,
        ]);

        return back()->with('status', $validated['type'] === 'price_drop'
            ? "We'll notify you if the price drops below the current best price."
            : "We'll notify you when this product is back in stock.");
    }

    public function destroy(PriceAlert $alert): RedirectResponse
    {
        abort_if($alert->user_id !== Auth::id(), 403);
        $alert->delete();

        return back()->with('status', 'Alert removed.');
    }
}
