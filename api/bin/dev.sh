#!/bin/bash

# Parse arguments for --force flag
FORCE=0
for arg in "$@"; do
    if [ "$arg" == "--force" ]; then
        FORCE=1
    fi
done

if [ $FORCE -eq 1 ]; then
    echo "Running fresh migrations and seeding..."
    php artisan migrate:fresh --seed --force
else
    echo "Running migrations and seeding..."
    php artisan migrate --force
    php artisan db:seed --force || true
fi

echo "Starting artisan server, queue, and logs..."
npx concurrently -c "#93c5fd,#c4b5fd,#fb7185" "php artisan serve --host=localhost" "php artisan queue:listen --tries=1 --timeout=0" "php artisan pail --timeout=0" --names=server,queue,logs --kill-others
