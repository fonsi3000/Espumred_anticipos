# Usar una imagen base de Ubuntu 22.04
FROM ubuntu:22.04

# Configurar variables de entorno
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Bogota
ENV OCTANE_SERVER=swoole

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
    php8.2-dev \
    php8.2-swoole

# Configurar OPcache para mejor rendimiento
RUN echo "opcache.enable=1" >> /etc/php/8.2/cli/conf.d/10-opcache.ini && \
    echo "opcache.memory_consumption=128" >> /etc/php/8.2/cli/conf.d/10-opcache.ini && \
    echo "opcache.interned_strings_buffer=8" >> /etc/php/8.2/cli/conf.d/10-opcache.ini && \
    echo "opcache.max_accelerated_files=10000" >> /etc/php/8.2/cli/conf.d/10-opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /etc/php/8.2/cli/conf.d/10-opcache.ini && \
    echo "opcache.save_comments=1" >> /etc/php/8.2/cli/conf.d/10-opcache.ini && \
    echo "opcache.fast_shutdown=1" >> /etc/php/8.2/cli/conf.d/10-opcache.ini

# Configurar PHP para mejor rendimiento con Octane
RUN echo "memory_limit=512M" >> /etc/php/8.2/cli/conf.d/99-custom.ini && \
    echo "max_execution_time=60" >> /etc/php/8.2/cli/conf.d/99-custom.ini

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar los archivos de la aplicación
COPY . .

# Configurar el entorno para evitar conexiones a la base de datos durante la construcción
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=:memory:

# Instalar dependencias de manera separada para facilitar la depuración
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN composer require laravel/octane --with-all-dependencies
RUN php artisan octane:install --server=swoole --force

# Verificar y corregir el registro del proveedor de servicios de Octane
RUN php artisan list | grep octane || echo "Octane no está registrado correctamente; registrando manualmente"
RUN if ! php artisan list | grep -q octane; then \
    echo "class_exists('\Laravel\Octane\OctaneServiceProvider') || exit(1);" | php && \
    sed -i "/App\\\\Providers\\\\RouteServiceProvider::class,/a \        Laravel\\\\Octane\\\\OctaneServiceProvider::class," config/app.php; \
    fi

# Verificar después de registro manual
RUN php artisan list | grep octane

# Limpiar caché y optimizar autoload
RUN composer dump-autoload -o
RUN php artisan config:clear --no-interaction
RUN php artisan view:clear --no-interaction
RUN php artisan route:clear --no-interaction

# Instalar y compilar assets
RUN npm install || true
RUN npm run build || true

# Configurar permisos
RUN chown -R www-data:www-data /app
RUN chmod -R 775 storage bootstrap/cache
RUN php artisan key:generate --force || true

# Restaurar la configuración de la base de datos para producción
ENV DB_CONNECTION=mysql

# Exponer el puerto 5050
EXPOSE 5050

# Mantener el contenedor en ejecución sin iniciar ningún servidor
CMD ["tail", "-f", "/dev/null"]

