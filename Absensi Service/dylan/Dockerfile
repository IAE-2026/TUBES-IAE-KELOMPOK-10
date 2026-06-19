FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libpq-dev zip unzip nginx \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /tmp

# Disable security advisory block BEFORE create-project (needed for dependencies)
RUN composer config --global policy.advisories.block false \
    && composer config --global allow-plugins.composer/installers true \
    && composer create-project laravel/laravel:^11.0 laravel \
        --no-interaction \
        --prefer-dist \
    && rm -rf /var/www/html \
    && cp -a laravel/. /var/www/ \
    && rm -rf laravel

WORKDIR /var/www

# Install additional packages (firebase/php-jwt needed for SSO/JWT verification)
RUN composer config --global policy.advisories.block false \
    && composer require \
        guzzlehttp/guzzle:^7.2 \
        nuwave/lighthouse:^6.0 \
        darkaonline/l5-swagger:^11.0 \
        firebase/php-jwt:^6.0 \
        --no-interaction --prefer-dist

# Copy our application files (overrides Laravel defaults)
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY graphql/ graphql/
COPY public/ public/
COPY routes/ routes/
COPY tests/ tests/
COPY phpunit.xml phpunit.xml

# Re-run autoload after copying our files
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
