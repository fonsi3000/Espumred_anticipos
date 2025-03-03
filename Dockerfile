# Usar una imagen base de Ubuntu 22.04
FROM ubuntu:22.04

# Configurar variables de entorno
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Bogota

# Instalar dependencias del sistema
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y \
    bash \
    git \
    sudo \
    openssh-client \
    libxml2-dev \
    libonig-dev \
    autoconf \
    gcc \
    g++ \
    make \
    libfreetype6-dev \
    libjpeg-turbo8-dev \
    libpng-dev \
    libzip-dev \
    curl \
    unzip \
    nano \
    software-properties-common

# Instalar Node.js 18.x
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Agregar el repositorio de PHP 8.2 e instalar PHP y extensiones necesarias
RUN add-apt-repository ppa:ondrej/php -y && \
    apt-get update && \
    apt-get install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-readline \
    php8.2-pcov \
    php8.2-dev

# Instalar Swoole y habilitar la extensión
RUN pecl install swoole && \
    echo "extension=swoole.so" > /etc/php/8.2/mods-available/swoole.ini && \
    phpenmod swoole

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar composer.json y composer.lock primero para aprovechar la caché de capas de Docker
COPY composer.json composer.lock ./

# Instalar dependencias de Composer
RUN composer install --no-scripts --no-autoloader --no-interaction

# Copiar el resto de los archivos de la aplicación
COPY . .

# Generar el autoloader optimizado y ejecutar scripts post-install
RUN composer dump-autoload --optimize && \
    composer run-script post-install-cmd && \
    composer require laravel/octane --no-interaction && \
    php artisan octane:install --server=swoole && \
    npm install && npm run build && \
    chown -R www-data:www-data /app && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan key:generate --force

# Exponer el puerto 5050 
EXPOSE 5050

# Comando para iniciar la aplicación con Laravel Octane y Swoole en el puerto 5050
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=5050", "--workers=4", "--task-workers=2"]