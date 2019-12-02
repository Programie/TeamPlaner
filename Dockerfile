FROM composer AS composer

COPY composer.* /app/

WORKDIR /app

RUN composer install --no-dev --ignore-platform-reqs && \
    rm /app/composer.json /app/composer.lock


FROM node:current AS bower

COPY bower.json .bowerrc /app/

WORKDIR /app

RUN npm install -g bower && \
    bower --allow-root install


FROM php:7.3-apache

ENV TZ=Europe/Berlin
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone && \
    echo "date.timezone=\"$TZ\"" > $PHP_INI_DIR/conf.d/timezone.ini

RUN savedAptMark="$(apt-mark showmanual)" && \
    apt-get update && \
    docker-php-ext-install -j "$(nproc)" pdo_mysql && \
    apt-mark auto '.*' > /dev/null && \
    apt-mark manual $savedAptMark && \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual && \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false && \
    rm -rf /var/lib/apt/lists/*

RUN sed -ri -e 's!/var/www/html!/app/httpdocs!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!/app/httpdocs!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    echo "ServerTokens Prod" > /etc/apache2/conf-enabled/z-server-tokens.conf && \
    a2enmod rewrite && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer /app/vendor /app/vendor
COPY --from=bower /app/httpdocs/bower_components /app/httpdocs/bower_components

COPY bootstrap.php /app/
COPY bin /app/bin
COPY httpdocs /app/httpdocs
COPY src /app/src
COPY config/config.template.json /app/config/config.template.json

WORKDIR /app

CMD ["apache2-foreground"]
