FROM php:7.4-cli

# Устанавливаем необходимые пакеты
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Устанавливаем необходимые расширения PHP
RUN docker-php-ext-install pdo pdo_mysql

# Устанавливаем глобальные Composer зависимости
RUN composer global require "friendsofphp/php-cs-fixer" "phpunit/phpunit"

# Устанавливаем переменные окружения
ENV PATH="/root/.composer/vendor/bin:${PATH}"

# Создаём рабочую директорию
WORKDIR /app

# Копируем файлы проекта в контейнер
COPY . /app

# Устанавливаем зависимости проекта через Composer
RUN composer install --no-interaction --prefer-dist

# Запускаем PHPUnit тесты
CMD ["phpunit"]
