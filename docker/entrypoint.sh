#!/bin/sh
set -e

php artisan config:clear --quiet
php artisan migrate --force --quiet

echo "ReportForge API: http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
