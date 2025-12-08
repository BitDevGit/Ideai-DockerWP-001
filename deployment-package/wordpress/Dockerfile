FROM wordpress:6.4-php8.2-fpm

# Install additional PHP extensions and tools
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    intl \
    mbstring \
    opcache \
    mysqli \
    pdo \
    pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer not needed for basic WordPress - can install later if needed

# Set working directory
WORKDIR /var/www/html

# Copy custom PHP configurations
COPY php.ini /usr/local/etc/php/php.ini
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Copy WordPress multisite configuration script
COPY configure-multisite.sh /usr/local/bin/configure-multisite.sh
RUN chmod +x /usr/local/bin/configure-multisite.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
  CMD php -r "if (file_exists('/var/www/html/wp-config.php')) { exit(0); } exit(1);"


