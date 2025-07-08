#!/bin/sh

echo "⏳ Esperando que MySQL esté disponible..."
until nc -z anticipos_db 3306; do
  echo "MySQL aún no responde, reintentando..."
  sleep 2
done
echo "✅ MySQL disponible"

echo "⏳ Esperando que Redis esté disponible..."
until nc -z anticipos_redis 6379; do
  echo "Redis aún no responde, reintentando..."
  sleep 2
done
echo "✅ Redis disponible"

# Git safe directory para evitar advertencias
git config --global --add safe.directory /var/www/html

echo "📦 Instalando dependencias con Composer..."
composer install --no-dev --optimize-autoloader || {
  echo "❌ Falló composer install"
  exit 1
}

echo "🎨 Instalando y compilando assets frontend (npm)..."
npm install
npm run build || {
  echo "❌ Falló compilación de frontend"
  exit 1
}

echo "⚙️ Ejecutando comandos de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan key:generate --force

# Ignora si el enlace ya existe
php artisan storage:link || true

echo "✅ Laravel listo para producción"

# Inicia Supervisor (PHP-FPM + Cron)
exec supervisord -c /etc/supervisord.conf
