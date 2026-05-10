#!/bin/bash

# Wait for DB to be connectable
echo "Checking database connection..."
until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
  echo "Database connection failed. Waiting 5 seconds..."
  sleep 5
done
echo "Database connected!"

# Run migrations (safe, applies new ones without wiping)
echo "Running migrations..."
php artisan migrate --force

# Check if database has data
echo "Checking if database has data..."
if php artisan tinker --execute="echo \App\Models\User::exists() ? 'true' : 'false';" | grep -q "true"; then
    echo "Database has data. Skipping seeding."
else
    echo "Database is empty. Running seeding..."
    php artisan db:seed --force
fi

# Run the rest of the commands
php artisan queue:work &
npm run build
php artisan optimize:clear
php artisan serve --host 0.0.0.0
