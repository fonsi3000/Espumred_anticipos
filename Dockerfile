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

# Instalar Swoole con manejo de errores y reintentos
RUN set -e; \
    # Crear un script para instalar Swoole con reintentos
    echo '#!/bin/bash \n\
    MAX_ATTEMPTS=5 \n\
    ATTEMPT=1 \n\
    while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do \n\
    echo "Intento $ATTEMPT de $MAX_ATTEMPTS para instalar Swoole" \n\
    if pecl install swoole; then \n\
    echo "Swoole instalado correctamente" \n\
    exit 0 \n\
    fi \n\
    echo "Fallo en el intento $ATTEMPT. Esperando 10 segundos antes de reintentar..." \n\
    sleep 10 \n\
    ATTEMPT=$((ATTEMPT+1)) \n\
    done \n\
    echo "Error: No se pudo instalar Swoole después de $MAX_ATTEMPTS intentos" \n\
    exit 1' > /usr/local/bin/install-swoole.sh && \
    chmod +x /usr/local/bin/install-swoole.sh && \
    /usr/local/bin/install-swoole.sh && \
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

# Generar el autoloader optimizado y configurar la aplicación
# Se eliminó la llamada a post-install-cmd que no existe
RUN composer dump-autoload --optimize && \
    composer require laravel/octane --no-interaction && \
    php artisan octane:install --server=swoole && \
    npm install && npm run build && \
    chown -R www-data:www-data /app && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan key:generate --force

# Exponer el puerto 5050
EXPOSE 5050

# Comando para iniciar la aplicación con Laravel Octane y Swoole
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=5050", "--workers=4", "--task-workers=2"]