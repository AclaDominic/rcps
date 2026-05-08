#!/bin/bash

# Wait for DB to be connectable
echo "Checking database connection..."
until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
  echo "Database connection failed. Waiting 5 seconds..."
  sleep 5
done
echo "Database connected!"

# Check if database has tables
echo "Checking if database has data..."
TABLE_COUNT=$(php artisan tinker --execute="echo count(DB::select('SHOW TABLES'));" 2>/dev/null | tail -n 1 | sed 's/[^0-9]*//g')

echo "Found $TABLE_COUNT tables."

if [ -n "$TABLE_COUNT" ] && [ "$TABLE_COUNT" -gt 0 ]; then
    echo "Database already has tables. Skipping migration and seeding."
else
    echo "Database is empty or count failed. Running migrations and seeding..."
    php artisan migrate --force
    php artisan db:seed --force
fi

# Run the rest of the commands
php artisan queue:work &
npm run build
php artisan optimize:clear
php artisan serve --host 0.0.0.0
