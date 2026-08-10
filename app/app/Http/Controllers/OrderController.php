<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Auth::user()->orders()->with(['product', 'store'])->latest()->get();

        return view('orders.index', ['orders' => $orders]);
    }

    /**
     * Show the human-confirmation step before any redirect to a store -
     * required by the proposal's "no unsupervised automated checkout" rule.
     */
    public function confirm(Request $request): View
    {
        $validated = $request->validate(['product_price_id' => 'required|exists:product_prices,id']);

        $listing = ProductPrice::with(['product', 'store'])->findOrFail($validated['product_price_id']);

        return view('orders.confirm', ['listing' => $listing]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['product_price_id' => 'required|exists:product_prices,id']);

        $listing = ProductPrice::with(['product', 'store'])->findOrFail($validated['product_price_id']);

        $isRealUrl = $listing->product_url && $listing->product_url !== '#';

        $order = Auth::user()->orders()->create([
            'product_id' => $listing->product_id,
            'store_id' => $listing->store_id,
            'store_title' => $listing->store_title,
            'price' => $listing->price,
            'delivery_charge' => $listing->delivery_charge,
            'quantity' => 1,
            'product_url' => $listing->product_url,
            'status' => 'confirmed_redirected',
        ]);

        if ($isRealUrl) {
            return redirect()->away($listing->product_url);
        }

        return redirect()->route('orders.index')
            ->with('status', "Order #{$order->id} confirmed. {$listing->store->name} is a demo store in this MVP catalogue, so there is no real checkout page to redirect to - a live store integration would redirect here.");
    }

    public function cancel(Order $order): RedirectResponse
    {
        abort_if($order->user_id !== Auth::id(), 403);

        if ($order->status === 'confirmed_redirected') {
            $order->update(['status' => 'cancelled']);
        }

        return back()->with('status', "Order #{$order->id} cancelled.");
    }
}
