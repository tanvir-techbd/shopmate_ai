<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use App\Models\PossibleDuplicateProduct;
use App\Models\PreOrderRequest;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'users' => User::count(),
            'products' => Product::count(),
            'stores' => Store::count(),
            'orders' => Order::count(),
            'searches' => SearchHistory::count(),
            'pre_orders' => PreOrderRequest::count(),
            'avg_confidence' => $this->averageConfidence(),
        ];

        $storeHealth = Store::withCount('prices')
            ->get()
            ->map(function (Store $store) {
                $lastChecked = $store->prices()->max('last_checked_at');

                return [
                    'name' => $store->name,
                    'is_active' => $store->is_active,
                    'origin' => $store->origin,
                    'listing_count' => $store->prices_count,
                    'last_checked' => $lastChecked,
                ];
            });

        $recentSearches = SearchHistory::with('user')->latest()->limit(15)->get();

        $preOrderRequests = PreOrderRequest::with('user')->latest()->limit(15)->get();

        $topCategories = Product::selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $duplicates = PossibleDuplicateProduct::with(['productA.prices.store', 'productB.prices.store'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'storeHealth' => $storeHealth,
            'recentSearches' => $recentSearches,
            'preOrderRequests' => $preOrderRequests,
            'topCategories' => $topCategories,
            'duplicates' => $duplicates,
        ]);
    }

    public function mergeDuplicate(PossibleDuplicateProduct $duplicate): RedirectResponse
    {
        $loserTitle = $duplicate->productB->canonical_title;
        $winnerTitle = $duplicate->productA->canonical_title;

        // Deletes productB, which cascades away this row (and any other
        // pending pairs mentioning productB - correctly, since those
        // comparisons are now moot).
        $duplicate->productB->mergeInto($duplicate->productA);

        return back()->with('status', "Merged \"{$loserTitle}\" into \"{$winnerTitle}\".");
    }

    public function dismissDuplicate(PossibleDuplicateProduct $duplicate): RedirectResponse
    {
        $duplicate->update(['status' => 'dismissed']);

        return back()->with('status', 'Dismissed - not a duplicate.');
    }

    /**
     * Average ranking score across recent assistant responses, as a rough
     * proxy for "AI confidence" (per the proposal's Admin Dashboard module)
     * until a proper evaluation harness (Phase 6) produces a real metric.
     */
    private function averageConfidence(): ?float
    {
        $scores = Message::where('sender', 'assistant')
            ->whereNotNull('results')
            ->latest()
            ->limit(50)
            ->pluck('results')
            ->flatMap(fn ($results) => collect($results)->pluck('score'))
            ->filter(fn ($score) => $score !== null);

        return $scores->isEmpty() ? null : round($scores->avg(), 3);
    }
}
