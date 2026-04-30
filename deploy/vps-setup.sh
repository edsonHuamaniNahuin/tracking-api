#!/bin/bash
# ============================================================================
# SETUP INICIAL DEL VPS ELASTIKA PARA TRACKING-API
# Ejecutar como root o con sudo en Ubuntu/Debian
# ============================================================================
set -e

echo "══════════════════════════════════════════"
echo "  Tracking API — Setup VPS Elastika"
echo "══════════════════════════════════════════"

# ── 1. Actualizar sistema ────────────────────────────────────────────────────
echo "▶ Actualizando sistema..."
apt update && apt upgrade -y

# ── 2. Instalar Docker ──────────────────────────────────────────────────────
echo "▶ Instalando Docker..."
apt install -y ca-certificates curl gnupg
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

systemctl enable docker
systemctl start docker

# ── 3. Instalar Nginx (reverse proxy) ───────────────────────────────────────
echo "▶ Instalando Nginx..."
apt install -y nginx

# ── 4. Instalar Certbot (SSL Let's Encrypt) ─────────────────────────────────
echo "▶ Instalando Certbot..."
apt install -y certbot python3-certbot-nginx

# ── 5. Instalar Git ─────────────────────────────────────────────────────────
echo "▶ Instalando Git..."
apt install -y git

# ── 6. Crear directorio del proyecto ────────────────────────────────────────
echo "▶ Creando directorio del proyecto..."
mkdir -p /var/www/tracking-api
cd /var/www/tracking-api

# ── 7. Clonar repositorio ───────────────────────────────────────────────────
echo "▶ Clonando repositorio..."
git clone https://github.com/edsonHuamaniNahuin/tracking-api.git .

# ── 8. Crear .env de producción ─────────────────────────────────────────────
echo "▶ Creando .env de producción (EDITAR MANUALMENTE)..."
cat > .env << 'EOF'
APP_NAME=TrackingAPI
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERA_CON_php_artisan_key_generate_show
APP_URL=https://api.nautic.run

LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tracking_api
DB_USERNAME=TU_USUARIO
DB_PASSWORD=TU_PASSWORD

JWT_SECRET=GENERA_CON_php_artisan_jwt_secret_show

SESSION_DRIVER=cookie
CACHE_STORE=file
QUEUE_CONNECTION=sync

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=GENERA_CON_php_artisan_reverb_install
REVERB_APP_KEY=GENERA_CON_php_artisan_reverb_install
REVERB_APP_SECRET=GENERA_CON_php_artisan_reverb_install
REVERB_HOST=0.0.0.0
REVERB_PORT=8081
REVERB_SCHEME=http

FRONTEND_URL=https://nautic.run
EOF

echo ""
echo "⚠  IMPORTANTE: Edita /var/www/tracking-api/.env con tus credenciales reales"
echo "   nano /var/www/tracking-api/.env"
echo ""

# ── 9. Configurar Nginx como reverse proxy ──────────────────────────────────
echo "▶ Configurando Nginx para api.nautic.run..."
cat > /etc/nginx/sites-available/api.nautic.run << 'EOF'
server {
    listen 80;
    server_name api.nautic.run;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
        client_max_body_size 20M;
    }
}
EOF

ln -sf /etc/nginx/sites-available/api.nautic.run /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── 10b. Habilitar módulos Nginx para WebSocket (ya incluido en el build estándar) ─────
# Si usas Apache en lugar de Nginx:
#   a2enmod proxy proxy_http proxy_wstunnel headers && systemctl reload apache2

# ── 11. Servicio systemd para Reverb WebSocket ────────────────────────────
echo "▶ Creando servicio systemd para Reverb..."
cat > /etc/systemd/system/reverb.service << 'REVERB_SERVICE'
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/tracking-api
ExecStart=/usr/local/php82/bin/php artisan reverb:start --host=0.0.0.0 --port=8081 --no-interaction
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
REVERB_SERVICE

systemctl daemon-reload
systemctl enable reverb.service
systemctl start reverb.service
echo "✅ Reverb habilitado y arrancado (puerto interno 8081)"

echo "▶ Construyendo y levantando contenedor..."
cd /var/www/tracking-api
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

# ── 11. SSL con Certbot ─────────────────────────────────────────────────────
echo "▶ Obteniendo certificado SSL..."
echo "   Ejecuta manualmente después de configurar DNS:"
echo "   certbot --nginx -d api.nautic.run --non-interactive --agree-tos -m edson2869944@gmail.com"

echo ""
echo "══════════════════════════════════════════"
echo "  ✅ Setup completado"
echo "══════════════════════════════════════════"
echo ""
echo "Pasos restantes:"
echo "  1. Editar .env:  nano /var/www/tracking-api/.env"
echo "  2. Configurar DNS en Porkbun (api.nautic.run → IP del VPS)"
echo "  3. Obtener SSL: certbot --nginx -d api.nautic.run"
echo "  4. Configurar secrets en GitHub para auto-deploy"
echo ""
