#!/bin/bash

# Wait for DB to be connectable
echo "Checking database connection..."
until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
  echo "Database connection failed. Waiting 5 seconds..."
  sleep 5
done
echo "Database connected!"

# Run fresh migrations and seeding every time
echo "Running fresh migrations and seeding..."
php artisan migrate:fresh --seed --force

# Run the rest of the commands
php artisan queue:work &
npm run build
php artisan optimize:clear
php artisan serve --host 0.0.0.0
