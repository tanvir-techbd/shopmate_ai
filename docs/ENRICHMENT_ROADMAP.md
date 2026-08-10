# Enrichment Roadmap — Real Stores & Feature Depth

Written in response to: "it should compare item with them [Daraz etc.] —
make a plan to enrich features." Phases A and B below are now implemented;
Phase C (real store data) is still just a plan, blocked on you obtaining
real feed/affiliate access.

## 1. Where things stand now

- **Three stores, still all fake — but no longer hand-paired.**
  `ProductCatalogSeeder` (used by `db:seed`, the main demo/eval dataset)
  still hand-groups 2 mock stores, unchanged, so the Phase 6 evaluation
  numbers stay reproducible. Separately, `php artisan providers:import`
  (§3 below) ingests three *independently worded* mock stores — TrendyMart
  BD, QuickBazaar, and a new third one, ClickBuy BD — through a real
  matching algorithm that has to figure out cross-store equivalence from
  title/brand/category alone, the same way it would for real feeds.
- **A real product-matching algorithm now exists** (`ProductMatchingService`,
  §3) — brand-exact-match veto + category gate + token-overlap title
  similarity. Tested against a fixture with genuinely varied phrasing per
  store plus deliberate "distractor" products (same category, different
  brand) designed to catch false merges: it matched every true cross-store
  pair correctly and created zero false merges. Numbers in §3.
- **`Store`/`ProductPrice` schema already supports N stores** — adding a
  third, fourth, fifth store needs no schema change, only more rows.
- Still explicitly fake data. No real Daraz/Pickaboo/etc. integration
  exists — that's still entirely Phase C, and still blocked on you.

## 2. Why "just scrape Daraz" is the wrong first move

- Violates Daraz's Terms of Service and generally requires defeating
  anti-bot protections — your own proposal explicitly excludes this
  ("do not design around bypassing CAPTCHA, authentication or anti-bot
  measures").
- Fragile: scrapers break on every markup change, with no warning.
- The legitimate path your proposal already names is an **official API or
  affiliate feed**. For Daraz specifically that means their affiliate
  program (directly, or via a network like Admitad / InvolveAsia, which
  has historically hosted Daraz Bangladesh's program) — approved partners
  typically get a product feed (CSV/XML/API) plus tracked affiliate links.
  Program terms and availability change, so verify current details
  directly with Daraz/the network when you're ready to sign up — I can't
  do that signup for you, it needs your business details.
- Other BD stores worth the same treatment if/when they have an affiliate
  or partner API: Pickaboo, AjkerDeal, Othoba, Rokomari (books). Same
  pattern, different feed format each time.

## 3. Phase A + B — Provider architecture + real matching ✅ done

Implemented together since B needs A's shape to plug into. Try it now:

```bash
cd app
php artisan migrate:fresh          # empty catalogue, no --seed
php artisan providers:import       # ingests 3 independent mock stores, matches them
php artisan serve                  # then browse the chat as usual
```

(`db:seed` is untouched and still gives you the original deterministic
2-store/20-product dataset the Phase 6 eval numbers depend on —
`providers:import` is a separate, additive demo path. Run
`migrate:fresh --seed` afterward to go back to it.)

**What's there:**

- `app/StoreProviders/StoreProviderInterface.php` — `slug()`, `name()`,
  `baseUrl()`, `fetchListings(): array` (raw title/price/brand/category/
  attributes per listing). This is the "Provider Layer" from the original
  proposal's architecture diagram, now real.
- `app/StoreProviders/MockCatalogProvider.php` + `ProviderCatalogFixture.php`
  — three mock stores (TrendyMart BD, QuickBazaar, and a new **ClickBuy
  BD**) with *independently worded* listings for the same underlying
  products, plus deliberate same-category/different-brand "distractor"
  products, specifically to stress-test matching rather than make it easy.
- `app/Services/ProductMatchingService.php` — the actual algorithm:
  1. **Category is a hard gate** — a listing is only ever compared against
     existing products in the same category.
  2. **Explicit brand mismatch is a hard veto** — if both listing and
     candidate have a named brand and they differ, similarity is forced to
     0, regardless of how similar the titles read. This is what stops
     "Xiaomi Redmi Note 13" and "Samsung Galaxy A15" from merging just
     because both are black 128GB phones.
  3. Otherwise, score = token-set Jaccard similarity of the two titles,
     +0.30 if both listings agree on an exact brand name. Merge
     automatically at ≥0.55.
  4. No ML/embedding dependency — pure PHP string tokenizing, so it's as
     laptop-friendly as the TF-IDF search. Documented upgrade path is the
     same as search: swap in `sentence-transformers` embeddings once
     TF-IDF-style matching hits its ceiling on real, messier feed data.
- `app/Console/Commands/ImportProductsFromProviders.php`
  (`providers:import`) — runs every registered provider's listings through
  the matcher, upserting `products`/`product_prices` exactly like
  `ProductCatalogSeeder` does, just algorithmically instead of by hand.
- **Human-in-the-loop review queue** (`possible_duplicate_products` table +
  `php artisan products:find-duplicates` + the "Possible Duplicate
  Products" section on `/admin`): a *separate*, retroactive pairwise scan
  across existing products that flags anything with similarity ≥0.30
  (including pairs the live matcher never got to compare, e.g. products
  created independently) for a human to **Merge** or **Not a duplicate**
  — never auto-merged. This is also where the proposal's Objective 4
  product-matching-accuracy ground truth would come from in a real
  evaluation: every admin merge/dismiss decision is a labelled example.

**Validation run** (fresh catalogue, `providers:import`): 26 raw listings
from 3 independently-worded mock stores collapsed into exactly 12 correct
products — every true cross-store match found (including 3-way merges),
every distractor pair correctly kept separate (different brand, same
category; same brand, different category), zero pairs needed manual
review. Full breakdown and the exact fixture data are in
`app/app/StoreProviders/ProviderCatalogFixture.php` and
`app/app/Services/ProductMatchingService.php` if you want to see exactly
what it's checking.

## 4. Phase C — First real store connector

Now that Phase A/B exist, once you have real feed access to *something* (Daraz
affiliate feed, or even a smaller/easier partner to start with):

1. Parse the feed into `DarazFeedProvider` (format depends entirely on what
   the feed/API actually gives you — CSV columns vs. JSON vs. XML differs
   by network).
2. Scheduled import (e.g. daily — real feeds aren't live, they refresh
   periodically) populates/updates `products`/`product_prices`, running
   new listings through Phase B's matching against existing products.
3. Respect the feed's own rate limits/refresh cadence; store
   `last_checked_at` per listing like the mock data already does, so
   `alerts:check` and price-drop detection keep working unchanged.
4. Start with a narrow product category (e.g. just "backpacks" or just
   "smartphones") to validate matching quality before ingesting the full
   catalogue — much easier to hand-verify correctness on ~50 products than
   ~50,000.

## 5. Phase D — Additional stores

Repeat Phase C's shape per store, gated on you actually having a
permitted integration for each:
- Pickaboo, AjkerDeal, Othoba — same affiliate-network pattern as Daraz.
- Rokomari — books specifically; may have its own partner API.
Each new provider is additive once the interface exists — no changes
needed to search/ranking/matching code.

## 6. Other feature enrichment (independent of multi-store)

From your original proposal's module list, not yet built:
- **Review Intelligence** — a `reviews`/`review_analysis` table +
  sentiment/topic summarization. Needs real review text, so this pairs
  naturally with Phase C (real feeds often include review counts/ratings,
  sometimes review text).
- **Real semantic embeddings** replacing TF-IDF — biggest lever for
  Bangla/Banglish query quality specifically (documented tradeoff already
  in `IMPLEMENTATION_PLAN.md`).
- **Learning-to-rank personalization** — use `search_history` +
  `shopping_list` + past orders to bias ranking per user, instead of the
  current static weighted formula.
- **Email notifications for alerts** — `alerts:check` currently just
  flips a DB flag; wiring Laravel's Mail facade to actually notify is a
  small, self-contained addition.
- **Automated test suite** (PHPUnit for Laravel, pytest for the AI
  service) — flagged as a gap in `IMPLEMENTATION_PLAN.md` already;
  matters more once real data sources exist and things can silently break.

## 7. Suggested order

~~Phase A (provider refactor) and Phase B stage 1 (fuzzy matching) are
buildable right now, with zero external dependencies~~ — **done**, see §3.
Everything else in this document (Phase C onward) is blocked on you
obtaining real feed/affiliate access, or is independent feature work (§6)
you can request any time.
