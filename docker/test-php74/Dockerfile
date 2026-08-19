FROM php:7.4-cli

# 路线：源码编译 xdebug（参照 DNMVCS docker/test-php74/Dockerfile）
# - php:7.4-cli(debian-slim)镜像里已经预装 phpize / gcc / make / docker-php-ext-install
# - 只需 apt 装 git,1 个包 10 秒
# - xdebug 2.9.2 是 PHP 7.4 兼容的稳定版
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 编译并启用 xdebug 2.9.2（PHP 7.4 兼容）
RUN curl -fsSL https://github.com/xdebug/xdebug/archive/2.9.2.tar.gz | tar xz -C /tmp \
    && cd /tmp/xdebug-2.9.2 && phpize && ./configure && make && make install \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/xdebug-2.9.2

# Xdebug 覆盖率配置：兼容 2.x (coverage_enable) 与 3.x (mode)
RUN { echo 'xdebug.coverage_enable=1'; echo 'xdebug.mode=coverage'; } > /usr/local/etc/php/conf.d/99-xdebug-coverage.ini

# 安装 Composer 2.2.x（走官方源 getcomposer.org，不依赖其他 docker 镜像）
RUN curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=2.2.21 \
    && rm /tmp/composer-setup.php

WORKDIR /DATA

# build 阶段需要 composer.json 才能跑 composer install
# （运行时由 docker-compose mount 覆盖）
COPY composer-test-php74.json /DATA/composer.json
COPY src/ /DATA/src/
COPY bin/ /DATA/bin/

# 预烤 vendor（走 packagist 官方源 https://repo.packagist.org）
# 首次 build 会从 packagist 拉 32 个依赖包，后续 build 命中 cache 秒过
RUN composer install --no-interaction --prefer-dist \
    && composer clear-cache