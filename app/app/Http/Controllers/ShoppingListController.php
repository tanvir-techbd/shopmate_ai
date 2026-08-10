<?php

namespace App\Http\Controllers;

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShoppingListController extends Controller
{
    public function index(): View
    {
        $list = $this->currentList();
        $list->load(['items.product.prices.store']);

        return view('shopping-list.index', ['list' => $list]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'note' => 'nullable|string|max:255',
        ]);

        if (empty($validated['product_id']) && empty($validated['note'])) {
            return back()->withErrors(['note' => 'Add a product or a note.']);
        }

        $list = $this->currentList();

        $existing = null;
        if (! empty($validated['product_id'])) {
            $existing = $list->items()->where('product_id', $validated['product_id'])->first();
        }

        if ($existing) {
            $existing->increment('quantity');
        } else {
            $list->items()->create([
                'product_id' => $validated['product_id'] ?? null,
                'note' => $validated['note'] ?? null,
                'quantity' => 1,
            ]);
        }

        return back()->with('status', 'Added to your shopping list.');
    }

    public function update(Request $request, ShoppingListItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $validated = $request->validate([
            'is_purchased' => 'sometimes|boolean',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $item->update($validated);

        return back();
    }

    public function destroy(ShoppingListItem $item): RedirectResponse
    {
        $this->authorizeItem($item);
        $item->delete();

        return back()->with('status', 'Removed from your shopping list.');
    }

    private function currentList(): ShoppingList
    {
        return Auth::user()->shoppingLists()->firstOrCreate(
            ['name' => 'My Shopping List'],
        );
    }

    private function authorizeItem(ShoppingListItem $item): void
    {
        abort_if($item->shoppingList->user_id !== Auth::id(), 403);
    }
}
