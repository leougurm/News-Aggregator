#!/bin/bash

# Exit on error
#set -e

echo "Running database migrations..."
php artisan migrate --force

echo "Seeding database..."
php artisan db:seed --force

echo "Generacting Swagger documentation..."
php artisan l5-swagger:generate

echo "Startup categories fetch"
php artisan startup-fetch:categories

echo "Starting scheduler..."
exec php artisan schedule:work
