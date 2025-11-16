FROM ghcr.io/roadrunner-server/roadrunner:2024 AS roadrunner

FROM php:8.3-fpm-alpine as base

LABEL maintainer="sergey@volkar.ru"

# Copy php config
COPY ./conf/php.ini /usr/local/etc/php/php.ini

# Packages
RUN apk add --no-cache curl git build-base zlib-dev oniguruma-dev autoconf icu-dev bash postgresql-dev rabbitmq-c-dev


# PHP extensions (core)
RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions intl opcache pdo_pgsql sockets pcntl amqp redis msgpack gd

# PECL extensions
RUN pecl install amqp msgpack \
 && docker-php-ext-enable amqp msgpack

# XDebug
ARG INSTALL_XDEBUG=false
RUN if [ ${INSTALL_XDEBUG} = true ]; \
  then \
    pecl install xdebug && docker-php-ext-enable xdebug; \
  fi;

# Xdebug config
COPY ./conf/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Non root user
ARG PUID=1000
ARG PGID=1000
RUN apk --no-cache add shadow && \
    groupmod -o -g ${PGID} www-data && \
    usermod -o -u ${PUID} -g www-data www-data

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Git config
RUN git config --global user.email "email@domain.com" \
    && git config --global user.name "Your Name"

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

# Set user
USER www-data

# Files
COPY ./ /var/www
WORKDIR /var/www

# Run php-fpm
CMD ["rr", "serve", "-c", ".rr.yaml"]

# Expose port
EXPOSE 9000 8080