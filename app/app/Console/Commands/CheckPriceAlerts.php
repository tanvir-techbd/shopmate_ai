<?php

namespace App\Console\Commands;

use App\Models\PriceAlert;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('alerts:check')]
#[Description('Check active price-drop and restock alerts against current product_prices and trigger the ones that match.')]
class CheckPriceAlerts extends Command
{
    /**
     * Execute the console command.
     *
     * In-app only for the MVP (no email/SMS gateway - see proposal's scope
     * exclusions): a triggered alert simply flips to is_active=false with
     * triggered_at set, and shows as "Triggered" on the Alerts page.
     */
    public function handle(): int
    {
        $alerts = PriceAlert::with('product.prices')
            ->where('is_active', true)
            ->get();

        $triggered = 0;

        foreach ($alerts as $alert) {
            $listings = $alert->product->prices;

            if ($alert->type === 'price_drop') {
                $cheapest = $listings->where('in_stock', true)->sortBy(fn ($p) => (float) $p->price)->first();
                if ($cheapest && $alert->target_price !== null && (float) $cheapest->price <= (float) $alert->target_price) {
                    $alert->update(['is_active' => false, 'triggered_at' => now()]);
                    $triggered++;
                    $this->info("Price drop: {$alert->product->canonical_title} is now ৳{$cheapest->price}");
                }
            } else { // restock
                $inStock = $listings->firstWhere('in_stock', true);
                if ($inStock) {
                    $alert->update(['is_active' => false, 'triggered_at' => now()]);
                    $triggered++;
                    $this->info("Back in stock: {$alert->product->canonical_title}");
                }
            }
        }

        $this->info("Checked {$alerts->count()} active alert(s), triggered {$triggered}.");

        return self::SUCCESS;
    }
}
