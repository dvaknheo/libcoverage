FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP 8.5 用 xdebug 3.5.3（PECL 暂未发布，从 GitHub 源码编译）
RUN curl -fsSL https://github.com/xdebug/xdebug/archive/3.5.3.tar.gz | tar xz -C /tmp \
    && cd /tmp/xdebug-3.5.3 && phpize && ./configure && make && make install \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/xdebug-3.5.3

RUN echo 'xdebug.mode=coverage' > /usr/local/etc/php/conf.d/99-xdebug-coverage.ini

# Composer 2.8.x
RUN curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=2.8.8 \
    && rm /tmp/composer-setup.php

WORKDIR /DATA
