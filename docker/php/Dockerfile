FROM php:8.2-cli

RUN apt-get update -y && \
    apt-get install -y \
      git \
      zip \
      libjpeg-dev \
      libpng-dev \
      libicu-dev && \
    apt-get clean -y

RUN docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-configure intl
RUN docker-php-ext-install  \
    gd \
    intl

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
      && \
    php composer-setup.php --install-dir=/usr/bin --filename=composer \
      --quiet && \
    rm -f composer-setup.php
ENV COMPOSER_ALLOW_SUPERUSER 1
