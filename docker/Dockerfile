# ./docker/Dockerfile

# Використовуємо офіційний образ PHP з Apache
FROM php:8.3-apache

# Встановлюємо робочу директорію всередині контейнера
WORKDIR /var/www/html

# Оновлюємо список пакетів та встановлюємо необхідні системні залежності
# Для Debian/Ubuntu (яку використовує базовий образ apache), використовуємо apt-get
RUN apt-get update && apt-get install -y \
    git \
    nano \
    libmemcached-dev \
    zlib1g-dev \
    libicu-dev \
    g++ \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    cron \
    nodejs \
    npm \
    tzdata \
    --no-install-recommends && rm -rf /var/lib/apt/lists/*

# Встановлюємо PHP розширення
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-configure intl && docker-php-ext-install intl
RUN docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype && docker-php-ext-install gd
RUN docker-php-ext-install soap
RUN docker-php-ext-install exif
RUN docker-php-ext-install zip

# Вмикаємо модуль mod_rewrite для Apache (необхідно для Laravel Pretty URLs)
RUN a2enmod rewrite
# Налаштовуємо DocumentRoot Apache на public директорію Laravel
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Встановлюємо Composer глобально
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV TZ=Europe/Kyiv
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
# --- Конфігурація Cron для Laravel Scheduler ---
# Скопіювати start_app.sh та зробити його виконуваним
COPY ./docker/config/start_app.sh /usr/local/bin/start_app.sh
RUN chmod +x /usr/local/bin/start_app.sh

# Додаємо cron job для планувальника Laravel
# Вивід перенаправляємо до log файлу /var/log/cron.log для відлагодження
# Використовуємо повний шлях до PHP, щоб Cron завжди його знаходив
RUN echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab -

# Експортуємо порт 80, який слухає Apache
EXPOSE 80

# Команда, яка запускається при старті контейнера
# Вона запустить Apache у фоновому режимі та Cron
CMD ["start_app.sh"]