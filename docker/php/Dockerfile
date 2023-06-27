# Author: Dominik Harmim <harmim6@gmail.com>

FROM php:7.4-cli

RUN apt-get update -y && \
    apt-get install -y \
      git \
      zip \
      libjpeg-dev \
      libpng-dev && \
    apt-get clean -y

RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install gd

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/bin --filename=composer --quiet && \
    rm -f composer-setup.php
ENV COMPOSER_ALLOW_SUPERUSER 1
