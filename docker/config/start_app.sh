#!/bin/bash
# This script is the entrypoint for the Docker container.
# It performs initial setup tasks and then starts the main services.

echo "Running composer install..."
composer install # Install PHP dependencies
php artisan migrate # Run database migrations
npm install && npm run build # Install Node.js dependencies and build frontend assets

# Ensure log and cache directories exist and have correct permissions.
# This is crucial for Laravel when using mounted volumes in Docker.
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# --- START: Laravel Scheduler Setup ---
echo "Clearing Laravel scheduler cache..."
php artisan schedule:clear-cache # Clear any existing scheduler cache

# Add the cron job for the Laravel scheduler.
# This job runs the Laravel scheduler every minute.
# Output is redirected to /var/log/cron.log for debugging.
# Using full path to PHP ensures Cron finds it reliably.
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

# Start the Cron daemon in the background.
service cron start

# Check if cron started successfully. If not, print an error and exit.
if ! pgrep -x "cron" > /dev/null
then
    echo "Cron failed to start. Exiting."
    exit 1
fi

echo "Cron started successfully."
# --- END: Laravel Scheduler Setup ---


# Start Apache in the foreground. This will be the main process (PID 1) of the container.
# `exec` replaces the current shell process with apache2ctl, ensuring proper signal handling from Docker.
exec apache2ctl -D FOREGROUND