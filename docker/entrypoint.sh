#!/bin/sh
set -e

if [ -n "$RUN_MIGRATIONS" ]; then
  php artisan migrate --force
fi

if [ "$CONTAINER_ROLE" = "worker" ]; then
  # Laravel scheduler (Growth daily, Support hourly, impact snapshot nightly)
  php artisan schedule:work &
  exec php artisan queue:work --sleep=3 --tries=3 --timeout=120
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
