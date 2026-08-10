# ShopMate AI

LLM-orchestrated, retrieval-augmented shopping agent — see
`docs/ShopMate_AI_MSc_Project_Proposal.tex` for the full proposal and
`docs/IMPLEMENTATION_PLAN.md` for how this build simplifies it for a
low-spec laptop (no Docker, no Elasticsearch/vector DB, no Redis/Celery, no
local LLM — MySQL via LAMPP + a lightweight rule-based/TF-IDF AI service).

## Prerequisites (already satisfied on this machine)

- PHP 8.5+, Composer
- LAMPP installed at `/opt/lampp` (its MySQL is used as the database)
- Python 3.14 (system Python has no `pip`/`venv` module preinstalled — see
  "First-time Python setup" below for the one-time workaround used)

## First-time setup (already done once in this repo, documented for re-setup)

```bash
# 1. Laravel app
cd app
composer install
cp .env.example .env   # already configured for LAMPP MySQL + AI_SERVICE_URL
php artisan key:generate   # only if APP_KEY is empty
/opt/lampp/lampp startmysql
mysql -u root -h 127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS shopmate_ai;"
php artisan migrate
php artisan db:seed   # creates demo user + mock 2-store product catalogue

# 2. AI service
cd ../ai-service
python3 -m venv --without-pip venv   # this Python has no ensurepip; see note below
curl -sS https://bootstrap.pypa.io/get-pip.py -o /tmp/get-pip.py
venv/bin/python /tmp/get-pip.py
venv/bin/pip install -r requirements.txt
cp .env.example .env
```

**Why `--without-pip` + `get-pip.py`?** This machine's Python 3.14 install
is missing the `ensurepip` module, so `python3 -m venv` alone fails, and
`pip install --user` is blocked by PEP 668 ("externally managed
environment"). Creating the venv without pip and then bootstrapping pip
*inside* the venv from the official installer script sidesteps both
problems without needing `sudo apt install python3.14-venv` (which needs an
interactive password we can't supply from here). If a future machine has a
normal Python install, the standard `python3 -m venv venv` works fine.

## Running it

```bash
./start.sh
```

This starts, as plain local processes (no Docker):
- LAMPP's MySQL
- the FastAPI AI service on `http://127.0.0.1:8001`
- the Laravel app on `http://127.0.0.1:8010`

Open **http://127.0.0.1:8010** and log in with one of the seeded accounts:

| Role  | Email                  | Password |
|-------|------------------------|----------|
| Demo  | `demo@shopmate.test`   | `password` |
| Admin | `admin@shopmate.test`  | `password` (also has `/admin` access) |

...or register a new account. In the chat, try:

> black backpack under 3000 taka with a laptop compartment

From a product result you can add it to your **Shopping List**, set a
**price-drop/restock alert**, or **Buy** (which always shows a confirmation
screen before anything happens — no automated checkout). Ctrl+C in the
terminal running `start.sh` stops everything.

### Checking for triggered price/stock alerts

Alerts are only evaluated when the check command runs:

```bash
cd app && php artisan alerts:check          # run once, manually
php artisan schedule:work                    # or: run continuously (fires every 5 min, see routes/console.php)
```

### Running the Phase 6 evaluation (ShopMate vs. keyword baseline)

```bash
cd ai-service
venv/bin/python -m eval.run_evaluation
```

Prints Precision@5/Recall@5/NDCG@5 for both systems across an 11-query
held-out set (`eval/queries.json`) and writes `eval/results.json`. See
`docs/IMPLEMENTATION_PLAN.md` §Phase 6 for the last recorded numbers and
their caveats.

### Running pieces individually (for debugging)

```bash
/opt/lampp/lampp startmysql
cd ai-service && venv/bin/uvicorn app.main:app --port 8001 --reload
cd app && php artisan serve --port 8010
```

## Project layout

```
shopmate_ai/
  app/            Laravel app — business logic, DB, chat UI, auth
  ai-service/     FastAPI app — intent/entity parsing, hybrid search, ranking
  docs/           Implementation plan and other project docs
  start.sh        Launches everything together
```

## Re-seeding / resetting data

```bash
cd app
php artisan migrate:fresh --seed
```

## Troubleshooting

- **Port 8010 or 8001 already in use** — override with
  `LARAVEL_PORT=8020 AI_PORT=8002 ./start.sh` (and update
  `app/.env`'s `AI_SERVICE_URL` accordingly if you change the AI port).
- **AI service unreachable from the chat UI** — check
  `curl http://127.0.0.1:8001/health`; the chat UI degrades gracefully with
  an in-chat error message if the AI service is down, so the Laravel side
  keeps working even mid-outage.
- **MySQL connection refused** — run `/opt/lampp/lampp startmysql` and
  confirm with `/opt/lampp/bin/mysql -u root -e "SELECT 1;"`.
