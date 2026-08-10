<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\Store;
use App\Models\User;
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
            'avg_confidence' => $this->averageConfidence(),
        ];

        $storeHealth = Store::withCount('prices')
            ->get()
            ->map(function (Store $store) {
                $lastChecked = $store->prices()->max('last_checked_at');

                return [
                    'name' => $store->name,
                    'is_active' => $store->is_active,
                    'listing_count' => $store->prices_count,
                    'last_checked' => $lastChecked,
                ];
            });

        $recentSearches = SearchHistory::with('user')->latest()->limit(15)->get();

        $topCategories = Product::selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'storeHealth' => $storeHealth,
            'recentSearches' => $recentSearches,
            'topCategories' => $topCategories,
        ]);
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
