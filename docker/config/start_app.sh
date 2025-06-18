#!/bin/bash

# Забезпечуємо, що файли логів і кешу існують та мають правильні права
# Це критично для роботи Laravel у Docker з монтованими обсягами
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# --- ДОДАНО: Очищення кешу планувальника при старті контейнера ---
echo "Clearing Laravel scheduler cache..."
php artisan schedule:clear-cache

# Запускаємо Cron як системну службу у фоновому режимі
service cron start

# Перевіряємо, чи cron запущено. Якщо ні, виводимо повідомлення і завершуємо роботу.
if ! pgrep -x "cron" > /dev/null
then
    echo "Cron failed to start. Exiting."
    exit 1
fi

echo "Cron started successfully."

# Запускаємо Apache на передньому плані. Це буде головний процес контейнера.
# `exec` заміняє поточний процес оболонки на Apache, роблячи його PID 1.
exec apache2ctl -D FOREGROUND