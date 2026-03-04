# PHP-FPM 生产环境 Dockerfile
FROM php:8.1-fpm-alpine

LABEL maintainer="dev@advnet.com"
LABEL description="PHP-FPM for AdvNet Short Video Platform"

# 安装系统依赖
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    nginx \
    supervisor \
    curl \
    wget \
    git \
    vim \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libcurl \
    curl-dev \
    libmcrypt-dev \
    libmemcached-dev \
    cyrus-sasl-dev \
    rabbitmq-c-dev \
    icu-dev \
    oniguruma-dev \
    openssl-dev \
    hiredis-dev \
    postgresql-dev \
    mysql-client \
    redis

# 安装PHP扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    curl \
    mbstring \
    xml \
    bcmath \
    opcache \
    sockets \
    pcntl \
    sysvmsg \
    sysvsem \
    sysvshm \
    intl

# 安装Redis扩展
RUN pecl install redis && docker-php-ext-enable redis

# 安装Swoole扩展（用于高性能网络通信）
RUN pecl install swoole && docker-php-ext-enable swoole

# 安装RabbitMQ AMQP扩展
RUN apk add --no-cache rabbitmq-c-dev && \
    pecl install amqp && \
    docker-php-ext-enable amqp

# 安装Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 创建应用目录
WORKDIR /var/www/html

# 复制PHP配置
COPY php.ini /usr/local/etc/php/php.ini
COPY php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf

# 创建日志目录
RUN mkdir -p /var/log/php /var/log/supervisor \
    && chown -R www-data:www-data /var/log/php /var/log/supervisor

# 创建健康检查脚本
RUN echo '#!/bin/sh' > /usr/local/bin/php-fpm-healthcheck \
    && echo 'php-fpm -t 2>/dev/null' >> /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# 清理缓存
RUN rm -rf /var/cache/apk/* /tmp/*

# 暴露端口
EXPOSE 9000

# 启动PHP-FPM
CMD ["php-fpm", "-F"]
