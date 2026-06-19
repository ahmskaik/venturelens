#!/bin/sh
set -e

if [ -n "$RUN_MIGRATIONS" ]; then
  php artisan migrate --force
fi

if [ -n "$RUN_SEED" ]; then
  php artisan db:seed --force
fi

if [ "$CONTAINER_ROLE" = "worker" ]; then
  php artisan migrate --force
  php artisan schedule:work &
  php artisan queue:work --sleep=5 --tries=3 --timeout=330 &
  exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
