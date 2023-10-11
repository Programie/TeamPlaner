FROM composer AS composer

WORKDIR /app

COPY composer.* /app/
RUN composer install --no-dev --ignore-platform-reqs


FROM node:current AS bower

WORKDIR /app

COPY bower.json .bowerrc /app/
RUN npm install -g bower && \
    bower --allow-root install


FROM ghcr.io/programie/dockerimages/php

ENV WEB_ROOT=/app/httpdocs

RUN install-php 8.2 pdo-mysql && \
    a2enmod rewrite

COPY --from=composer /app/vendor /app/vendor
COPY --from=bower /app/httpdocs/bower_components /app/httpdocs/bower_components

COPY bootstrap.php /app/
COPY bin /app/bin
COPY httpdocs /app/httpdocs
COPY src /app/src
COPY config/config.template.json /app/config/config.template.json

WORKDIR /app