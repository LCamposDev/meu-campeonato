FROM php:8.5-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    python3 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Laravel
RUN composer install --no-interaction --optimize-autoloader

# Permissões
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]