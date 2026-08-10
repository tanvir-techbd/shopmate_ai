# ShopMate AI — Low-Config Implementation Plan

This is the build plan actually being followed in this repo, adapted from
`ShopMate_AI_MSc_Project_Proposal.tex` for a single low-spec laptop
(quoted RAM at project start: 7.1GB total, often only ~1-2GB free, swap in
active use). It keeps every module from the proposal but swaps out the
heavy infrastructure pieces for ones that run comfortably on that machine.

## 1. Architecture decisions (and why)

| Proposal component | This build | Why |
|---|---|---|
| Docker everywhere | Plain local processes (`start.sh`) | No container overhead; one less thing to run/debug on a low-RAM machine. Docker is a documented later option, not a requirement. |
| MySQL/PostgreSQL | MySQL via existing **LAMPP** install | Already installed, zero new setup. |
| pgvector / Qdrant / Weaviate | Embeddings/keyword search computed on the fly in the FastAPI service, over the MySQL catalogue | MVP catalogue is tens–low hundreds of products. A dedicated vector database is unnecessary weight at this scale. `products.embedding` (JSON column) is reserved for when a real embedding model is introduced. |
| Elasticsearch/OpenSearch | scikit-learn TF-IDF + rule-based keyword filters, recomputed per request | Same reasoning — full-text search engine is overkill for the MVP catalogue size, and TF-IDF has no heavy runtime dependency. |
| Redis + Celery | Laravel's **database** queue/cache driver | Removes a background daemon; MySQL already covers the throughput this MVP needs. |
| Hugging Face LLM / sentence-transformers | Rule-based intent classifier + regex/keyword entity extractor (`ai-service/app/nlp.py`) | Chosen explicitly over an LLM-API or local-LLM approach so the service has **no torch/transformers dependency** — it starts instantly and uses negligible RAM. This is the biggest laptop-friendliness win. |
| React/Vue frontend | Laravel Blade + Alpine.js | No separate Node dev server needs to stay running; one less process. |

**Documented upgrade path** (do these only once the MVP works and you want
better accuracy, not before):
1. Swap TF-IDF for `sentence-transformers` (`all-MiniLM-L6-v2`, ~90MB) once
   Bangla/Banglish semantic matching needs to improve — populate
   `products.embedding` and compare with cosine similarity instead of TF-IDF.
2. Swap the rule-based intent/entity engine for a small fine-tuned
   classifier or an LLM API call, if budget for an API key exists.
3. Introduce Docker Compose once the two services are stable, for easier
   handoff/deployment.
4. Swap the mock catalogue for a real store API/affiliate feed behind the
   same `Provider` interface described in the proposal (Section "Proposed
   System Architecture").

## 2. Repo layout

```
shopmate_ai/                   <- project root (this folder)
  app/                         <- Laravel app (business logic, auth, chat UI, DB)
  ai-service/                  <- FastAPI app (intent/entity parsing, hybrid search, ranking)
    app/
      main.py                  <- FastAPI endpoints
      nlp.py                   <- rule-based intent classification + entity extraction
      search.py                <- TF-IDF + keyword filtering hybrid retrieval
      ranking.py                <- multi-factor scoring (relevance, price, rating, delivery) + explanation
      db.py                     <- read-only MySQL access (same DB as Laravel)
    requirements.txt
    .env.example
  docs/
    IMPLEMENTATION_PLAN.md     <- this file
  start.sh                     <- launches LAMPP MySQL + Laravel + FastAPI together
  README.md                    <- day-to-day run instructions
```

Both services point at the **same** MySQL database (`shopmate_ai`) — Laravel
owns the schema (migrations), the AI service only reads it. This mirrors the
proposal's service-oriented architecture without needing an internal
message bus.

## 3. Phased build order

Reduced from the proposal's 20-week/12-module plan into laptop-sized,
demoable increments. Each phase should end with something you can run and
click through.

**Phase 0 — Environment** ✅ done
Laravel install, MySQL DB via LAMPP, core migrations/models, seeded mock
two-store catalogue, FastAPI skeleton.

**Phase 1 — Conversational search loop (the MVP core)** ✅ done
- Blade chat UI → Laravel `ChatController` → FastAPI `/chat/query` → results
  rendered back into the chat.
- Covers modules: Conversational Interface, Intent & Entity Engine, Product
  Search, Product Matching (same-product-across-stores via `products` +
  `product_prices`), Price Comparison, Recommendation Engine (ranking +
  reason string).
- Definition of done: typing "black backpack under 3000 taka with a laptop
  compartment" returns a ranked, explained list pulling from both mock
  stores. Verified working end to end.

**Phase 2 — Accounts, history, shopping list** ✅ done
- Hand-rolled Laravel auth (`AuthController`, `auth/login.blade.php`,
  `auth/register.blade.php` — no Breeze/Node build needed, same
  no-build-step approach as the chat UI).
- Multiple conversations per user (`chat.new`, sidebar conversation
  switcher), `search_history` logging on every query.
- Shopping List CRUD (`ShoppingListController`) with an "+ Add to shopping
  list" action directly on product cards in chat results.

**Phase 3 — Alerts** ✅ done
- "Notify me on price drop" / "Notify when back in stock" buttons on
  product cards create `price_alerts` rows (`PriceAlertController`).
- `php artisan alerts:check` (`CheckPriceAlerts` command, scheduled every 5
  minutes in `routes/console.php`) compares current `product_prices`
  against each alert's target and flips it to triggered. In-app only —
  email/SMS is out of scope per the proposal.

**Phase 4 — Human-confirmed order/redirect workflow** ✅ done
- "Buy" on a product card → `orders.confirm` (shows price breakdown, no
  side effect) → explicit confirm → `OrderController::store` creates an
  `orders` row and redirects to the store's `product_url`. Since the mock
  catalogue's URLs are placeholders (`#`), the demo shows an explanatory
  status message instead of a dead redirect — a real store integration
  would redirect for real at that exact point.
- `/orders` lists order history with cancel support.

**Phase 5 — Admin dashboard** ✅ done
- `/admin` (gated by an `is_admin` flag + `EnsureUserIsAdmin` middleware):
  user/product/store/order/search counts, an "avg. AI confidence" stat
  (mean ranking score across the last 50 assistant replies), per-store
  listing health, catalogue-by-category breakdown, and a recent-searches
  table.
- Seeded admin login: `admin@shopmate.test` / `password`.

**Phase 6 — Evaluation** ✅ done
- `ai-service/eval/queries.json` — 11 hand-labelled queries covering
  budget, brand, attribute/rating, multi-constraint, Bangla/Banglish, and
  ambiguous-name/ranking-quality cases, each with ground-truth relevant
  product IDs from the seeded catalogue.
- `ai-service/eval/run_evaluation.py` computes Precision@5 / Recall@5 /
  NDCG@5 for ShopMate's actual pipeline (imported directly, not over HTTP)
  against a naive unranked keyword-substring baseline, and writes
  `eval/results.json`. Run it with:
  ```bash
  cd ai-service && venv/bin/python -m eval.run_evaluation
  ```
- Result from the seeded catalogue (n=11 queries, K=5): ShopMate averaged
  Recall@5 1.00 / NDCG@5 1.00 vs. the baseline's 0.59 / 0.59 — the baseline
  scores exactly 0 on every budget-aware, multi-constraint and
  Bangla/Banglish query, which is precisely the gap the proposal's
  research question is about. Precision@5 is low for both (0.36 vs 0.18)
  simply because most queries only have 1-3 truly relevant products in a
  20-product catalogue against K=5 — expected at this catalogue size, and
  worth a line in the thesis's limitations section. Growing the catalogue
  and hand-labelling more queries directly tightens this evaluation for
  the actual thesis submission.
- Still honest to write up as limitations: TF-IDF vs. true semantic
  embeddings, rule-based vs. learned intent classification, mock vs. real
  store data, small catalogue size. Expected and appropriate for an
  MVP-scoped MSc evaluation — the point of Phase 6 was to prove the
  methodology works, not to produce publication-scale numbers.

## 4. Running it day to day

See `README.md` in the project root for the actual commands
(`./start.sh`, seeding, etc.). Summary: LAMPP's MySQL, `php artisan serve`,
and `uvicorn` all run as plain local processes — no Docker required.

## 5. What's in this repo

All six phases above are implemented and were smoke-tested end to end
(login, chat search, shopping list, alerts, orders, admin dashboard, and
the evaluation harness) via direct HTTP requests against a running
`start.sh` session before being called done.

- Laravel app: auth, multi-conversation chat, shopping list, price/stock
  alerts + scheduled check command, human-confirmed orders, admin
  dashboard — routes in `routes/web.php`, `routes/admin.php`,
  `routes/console.php`.
- Migrations + Eloquent models for: users (+`is_admin`), stores, products,
  product_prices, conversations, messages, search_history, shopping_lists,
  shopping_list_items, price_alerts, orders.
- Seeded two mock stores ("TrendyMart BD", "QuickBazaar") with 20 products
  / 29 store listings spanning bags, phones, laptops, audio, footwear,
  watches, appliances, fashion, wearables and accessories, plus a demo
  user (`demo@shopmate.test` / `password`) and an admin user
  (`admin@shopmate.test` / `password`).
- FastAPI `ai-service` with rule-based intent/entity extraction
  (`nlp.py`), TF-IDF hybrid search (`search.py`), multi-factor ranking with
  explanations (`ranking.py`), and the Phase 6 evaluation harness
  (`eval/`).

### Known simplifications worth revisiting before a real thesis submission

- Orders are single-product (no multi-item cart / `order_items`), unlike
  the proposal's DB design — deliberate MVP scope cut, documented here so
  it isn't mistaken for an oversight.
- The evaluation query set (11 queries) is small; a real thesis evaluation
  should expand this and grow the seeded catalogue for statistical
  weight.
- No automated test suite (PHPUnit/Pytest) yet — Phase 6's evaluation
  script is the only automated correctness check beyond manual smoke
  testing. Worth adding before treating this as submission-ready.
- Still only mock/fake store data — see `docs/ENRICHMENT_ROADMAP.md` for
  the plan to add real stores (Daraz etc.) via affiliate feeds, and for
  the product-matching algorithm (`ProductMatchingService`) and provider
  architecture (`StoreProviderInterface`) that were added specifically to
  make that a drop-in change later rather than a rewrite.
