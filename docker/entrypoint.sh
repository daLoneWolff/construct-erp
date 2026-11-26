#!/bin/sh
set -eu

PORT="${PORT:-8080}"
export PORT

envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

php artisan package:discover --ansi --no-interaction || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

php artisan optimize --no-interaction
php artisan storage:link --no-interaction || true

chown -R www-data:www-data bootstrap/cache storage || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
