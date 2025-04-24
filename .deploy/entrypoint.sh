#!/bin/sh

echo "Esperando que MySQL esté disponible..."
# ✅ Espera a anticipos_db, no registros_db
until nc -z anticipos_db 3306; do
  echo "MySQL aún no responde, reintentando..."
  sleep 2
done
echo "MySQL disponible ✅"

echo "Esperando que Redis esté disponible..."
# ✅ Espera a anticipos_redis, no registros_redis
until nc -z anticipos_redis 6379; do
  echo "Redis aún no responde, reintentando..."
  sleep 2
done
echo "Redis disponible ✅"

git config --global --add safe.directory /var/www/html

echo "Instalando dependencias con Composer..."
composer install --no-dev --optimize-autoloader || {
  echo "❌ Falló composer install"
  exit 1
}

echo "Ejecutando comandos de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan storage:link
php artisan key:generate

echo "Setup Laravel completado ✅"

exec supervisord -c /etc/supervisord.conf
