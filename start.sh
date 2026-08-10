#!/usr/bin/env bash
# Launches everything ShopMate AI needs, as plain local processes.
# No Docker. Ctrl+C stops all of them together.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_PORT="${LARAVEL_PORT:-8010}"
AI_PORT="${AI_PORT:-8001}"

echo "==> Starting MySQL (LAMPP)..."
if /opt/lampp/bin/mysql -u root -e "SELECT 1;" >/dev/null 2>&1; then
  echo "    already running."
else
  /opt/lampp/lampp startmysql
fi

echo "==> Starting AI service on http://127.0.0.1:${AI_PORT} ..."
(
  cd "$ROOT_DIR/ai-service"
  venv/bin/uvicorn app.main:app --host 127.0.0.1 --port "$AI_PORT"
) &
AI_PID=$!

echo "==> Starting Laravel on http://127.0.0.1:${LARAVEL_PORT} ..."
(
  cd "$ROOT_DIR/app"
  php artisan serve --host 127.0.0.1 --port "$LARAVEL_PORT"
) &
LARAVEL_PID=$!

cleanup() {
  echo ""
  echo "==> Stopping..."
  kill "$AI_PID" "$LARAVEL_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo ""
echo "ShopMate AI is running:"
echo "  Chat UI:     http://127.0.0.1:${LARAVEL_PORT}"
echo "  AI service:  http://127.0.0.1:${AI_PORT}/health"
echo ""
echo "Press Ctrl+C to stop."

wait
