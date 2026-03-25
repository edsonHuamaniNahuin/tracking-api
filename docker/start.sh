#!/bin/sh
set -e

echo "=========================================="
echo "  Tracking API — Arranque en producción"
echo "=========================================="

echo "▶ Cacheando configuración..."
php artisan config:cache

echo "▶ Cacheando rutas..."
php artisan route:cache

echo "▶ Cacheando vistas..."
php artisan view:cache

echo "▶ Ejecutando migraciones..."
php artisan migrate --force

echo "▶ Generando documentación Swagger..."
php artisan l5-swagger:generate || echo "⚠ Swagger falló, continuando..."

echo "✅ Listo. Servidor en http://0.0.0.0:8080"
exec /usr/bin/supervisord -c /etc/supervisord.conf
