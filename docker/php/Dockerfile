# 使用官方 PHP 8.2 FPM 映像檔
FROM php:8.2-fpm

# 設定工作目錄
WORKDIR /var/www/html

# 安裝系統依賴套件
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install xml \
    && docker-php-ext-install zip \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install opcache

# 安裝 Redis 擴充套件
RUN pecl install redis && docker-php-ext-enable redis

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 複製 PHP 配置檔案
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# 建立 Laravel 使用者
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# 複製應用程式檔案
COPY . /var/www/html

# 設定檔案權限
RUN chown -R www:www /var/www/html
RUN chmod -R 755 /var/www/html/storage
RUN chmod -R 755 /var/www/html/bootstrap/cache

# 切換到 www 使用者
USER www

# 暴露 9000 埠
EXPOSE 9000

# 啟動 PHP-FPM
CMD ["php-fpm"]