#!/bin/sh

echo "Esperando que MySQL esté disponible..."

# Esperar hasta que el puerto 3306 esté disponible en fondo_empleado_db
until nc -z fondo_empleado_db 3306; do
  echo "MySQL aún no responde, reintentando..."
  sleep 2
done

echo "MySQL disponible, continuando setup Laravel..."

# Opcional si Git lanza advertencias: git config --global --add safe.directory /var/www/html

# Instalar dependencias (solo si es necesario en producción)
echo "Instalando dependencias con Composer..."
composer install --no-dev --optimize-autoloader

# Laravel setup
echo "Ejecutando comandos de Laravel..."
echo "Ejecutando php artisan config:cache..."
php artisan config:cache
echo "Ejecutando php artisan route:cache..."
php artisan route:cache
echo "Ejecutando php artisan migrate --force..."
php artisan migrate --force
echo "Ejecutando php artisan storage:link..."
php artisan storage:link
echo "Ejecutando php artisan key:generate..."
php artisan key:generate

echo "Setup de Laravel completado. Iniciando supervisord..."
# Lanzar supervisord (que maneja PHP-FPM y cron)
exec supervisord -c /etc/supervisord.conf