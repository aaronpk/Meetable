web: $(composer config bin-dir)/heroku-php-nginx -C nginx.conf public/
scheduler: php -d memory_limit=512M artisan queue:work --stop-when-empty
release: if [ -n "${DB_HOST}${DATABASE_URL}" ]; then php artisan migrate --force && php artisan cache:clear && php artisan storage:link; fi
