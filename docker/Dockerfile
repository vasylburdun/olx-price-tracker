# ./docker/Dockerfile

# Use the official PHP image with Apache as the base.
FROM php:8.3-apache

# Set the working directory inside the container.
WORKDIR /var/www/html

# Update package lists and install necessary system dependencies.
# For Debian/Ubuntu (used by the base Apache image), we use apt-get.
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
    iputils-ping \
    netcat-traditional \
    wget \
    --no-install-recommends && rm -rf /var/lib/apt/lists/*

# Install PHP extensions.
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-configure intl && docker-php-ext-install intl
RUN docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype && docker-php-ext-install gd
RUN docker-php-ext-install soap
RUN docker-php-ext-install exif
RUN docker-php-ext-install zip

# Enable the mod_rewrite module for Apache (required for Laravel Pretty URLs).
RUN a2enmod rewrite
# Configure Apache's DocumentRoot to Laravel's public directory.
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

# Install Composer globally.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the timezone for the container.
ENV TZ=Europe/Kyiv
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# --- Cron Configuration for Laravel Scheduler ---
# Copy the startup script (start_app.sh) and make it executable.
# This script will handle adding the cron job and starting cron/apache.
COPY ./docker/config/start_app.sh /usr/local/bin/start_app.sh
RUN chmod +x /usr/local/bin/start_app.sh

# IMPORTANT NOTE:
# The cron job itself is no longer added here with 'RUN echo ... | crontab -'.
# It is now handled by 'start_app.sh' during container startup to ensure it's
# always set correctly when the container starts.

# Expose port 80, which Apache listens on.
EXPOSE 80

# The command that runs when the container starts.
# It will execute the start_app.sh script, which in turn will start Apache
# in the foreground and the Cron daemon in the background.
CMD ["start_app.sh"]