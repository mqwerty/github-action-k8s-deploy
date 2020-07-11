#!/usr/bin/env sh

cp config.example.php config.php \
&& composer install --no-interaction --no-progress \
&& composer cs:check && composer stan:md && composer stan:phan && composer test:unit || exit 1

sh /app/docker-entrypoint.sh &

sleep 10
composer test:api || exit 1
