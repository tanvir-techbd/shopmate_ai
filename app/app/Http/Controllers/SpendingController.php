<?php

namespace App\Http\Controllers;

use App\Models\ShoppingListItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SpendingController extends Controller
{
    /**
     * A read-only view over the user's own order history - the only place
     * in the schema that represents actual buying decisions (shopping-list
     * items and searches are intent, not spend). No forecasting model:
     * everything here is a straightforward sum/group-by over their own
     * data, plus a few rule-based observations, consistent with the
     * project's "no heavy ML dependency" approach elsewhere (nlp.py,
     * search.py). See docs/IMPLEMENTATION_PLAN.md.
     */
    public function index(): View
    {
        $orders = Auth::user()->orders()
            ->with('product')
            ->where('status', '!=', 'cancelled')
            ->get();

        $cancelledCount = Auth::user()->orders()->where('status', 'cancelled')->count();

        $totalSpent = (float) $orders->sum(fn ($o) => $o->price + $o->delivery_charge);
        $orderCount = $orders->count();
        $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0.0;

        $now = Carbon::now();
        $thisMonthSpent = $this->spentInMonth($orders, $now);
        $lastMonth = $now->copy()->subMonthNoOverflow();
        $lastMonthSpent = $this->spentInMonth($orders, $lastMonth);

        $monthOverMonthChange = $lastMonthSpent > 0
            ? round((($thisMonthSpent - $lastMonthSpent) / $lastMonthSpent) * 100, 1)
            : null;

        $byCategory = $orders
            ->groupBy(fn ($o) => $o->product->category ?? 'Uncategorised')
            ->map(function ($group, $category) use ($totalSpent) {
                $total = (float) $group->sum(fn ($o) => $o->price + $o->delivery_charge);

                return [
                    'category' => $category,
                    'total' => $total,
                    'percent' => $totalSpent > 0 ? round($total / $totalSpent * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $monthlyTrend = collect(range(5, 0))->map(function ($monthsAgo) use ($orders, $now) {
            $month = $now->copy()->subMonthsNoOverflow($monthsAgo);

            return ['label' => $month->format('M Y'), 'total' => $this->spentInMonth($orders, $month)];
        });

        $maxMonthlyTotal = max(1.0, $monthlyTrend->max('total'));

        return view('spending.index', [
            'totalSpent' => $totalSpent,
            'orderCount' => $orderCount,
            'cancelledCount' => $cancelledCount,
            'avgOrderValue' => $avgOrderValue,
            'thisMonthSpent' => $thisMonthSpent,
            'lastMonthSpent' => $lastMonthSpent,
            'monthOverMonthChange' => $monthOverMonthChange,
            'byCategory' => $byCategory,
            'monthlyTrend' => $monthlyTrend,
            'maxMonthlyTotal' => $maxMonthlyTotal,
            'tips' => $this->buildTips($orders, $byCategory, $monthOverMonthChange),
        ]);
    }

    private function spentInMonth(Collection $orders, Carbon $month): float
    {
        return (float) $orders
            ->filter(fn ($o) => $o->created_at->isSameMonth($month) && $o->created_at->isSameYear($month))
            ->sum(fn ($o) => $o->price + $o->delivery_charge);
    }

    /**
     * Rule-based observations grounded in the user's own numbers - no
     * absolute "this is expensive" judgement, since the catalogue has no
     * notion of what a reasonable price is for an unknown category.
     */
    private function buildTips(Collection $orders, Collection $byCategory, ?float $monthOverMonthChange): array
    {
        if ($orders->isEmpty()) {
            return ["No orders yet - once you buy something through ShopMate AI, your spending trends and money-management tips will show up here."];
        }

        $tips = [];

        $topCategory = $byCategory->first();
        if ($topCategory && $topCategory['percent'] >= 40) {
            $tips[] = "{$topCategory['percent']}% of your spending is on {$topCategory['category']}. Before your next purchase there, ask ShopMate AI to compare a few options first - it ranks by price and rating together, not just the first result.";
        }

        if ($monthOverMonthChange !== null && $monthOverMonthChange >= 30) {
            $tips[] = "You've spent {$monthOverMonthChange}% more this month than last month. A price-drop alert lets you wait for a better deal instead of buying the moment you find something.";
        } elseif ($monthOverMonthChange !== null && $monthOverMonthChange <= -20) {
            $tips[] = 'You spent '.abs($monthOverMonthChange).'% less this month than last month - whatever changed is working.';
        }

        $unprotected = $this->shoppingListItemsWithoutAlert();
        if ($unprotected > 0) {
            $tips[] = "You have {$unprotected} item(s) on your shopping list with no price-drop alert set - set one so you don't have to keep checking manually.";
        }

        if (empty($tips)) {
            $tips[] = 'Your spending looks steady across categories and months - no particular pattern to flag right now.';
        }

        return $tips;
    }

    private function shoppingListItemsWithoutAlert(): int
    {
        $shoppingListProductIds = ShoppingListItem::whereHas('shoppingList', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('is_purchased', false)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique();

        $alertedProductIds = Auth::user()->priceAlerts()->where('is_active', true)->pluck('product_id');

        return $shoppingListProductIds->diff($alertedProductIds)->count();
    }
}
