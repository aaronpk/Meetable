web: vendor/bin/heroku-php-nginx -C nginx.conf public/
release: if [ -n "$DB_HOST" ]; then php artisan migrate --force; fi
