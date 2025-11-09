# Use the official PHP image with FPM
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libssh2-1-dev \
    zip \
    unzip \
    libssh2-1 \
    libssh2-1-dev


# Install SSH2 extension
RUN pecl install ssh2-1.3.1 && \
    docker-php-ext-enable ssh2

RUN pecl install redis && \
	docker-php-ext-enable redis

RUN pecl install xdebug && \
	docker-php-ext-enable xdebug

COPY ./docker/configs/xdebug.ini "${PHP_INI_DIR}/conf.d"

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Install PHP extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pgsql pdo_pgsql

RUN docker-php-ext-install  mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www
ENV PHP_IDE_CONFIG="serverName=localhost"


# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
