ARG COMPOSER_IMAGE=composer:2
ARG NODE_IMAGE=node:20-alpine
FROM ${COMPOSER_IMAGE} AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

FROM ${NODE_IMAGE} AS node

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --silent

COPY . .
RUN npm run build

FROM php:8.3-apache AS production

LABEL maintainer="LuisNol"

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV APP_ENV=production
ENV APP_DEBUG=0
ENV LOG_CHANNEL=stderr

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    a2enmod rewrite expires headers remoteip && \
    apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        unzip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mbstring \
        pdo_mysql \
        zip \
        opcache \
        bcmath \
        exif && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer /app/vendor /var/www/html/vendor
COPY --from=node /app/public/build /var/www/html/public/build

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/upload.ini && \
    echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/max-execution.ini

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN echo "RemoteIPHeader X-Forwarded-For" > /etc/apache2/conf-available/remoteip.conf && \
    a2enconf remoteip && \
    chmod +x /usr/local/bin/docker-entrypoint

EXPOSE 80
ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
