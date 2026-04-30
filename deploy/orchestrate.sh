#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Tracking API — Orquestador de Deploy (Elastika VPS)
# ═══════════════════════════════════════════════════════════════
#
# Uso:
#   ./deploy/orchestrate.sh <accion> [opciones]
#
# Acciones:
#   deploy     Pull → deps → migrate → cache → restart
#   status     Estado del servicio web
#   health     Verificación de salud de la API
#
# Opciones:
#   --skip-deps    Omitir composer install
#   --skip-migrate Omitir migraciones
#   --skip-pull    Omitir git pull (usado por CD que ya hizo pull)
#
# ═══════════════════════════════════════════════════════════════

set -euo pipefail

# ── Configuración ─────────────────────────────────────────────
APP_DIR="${VPS_APP_DIR:-/var/www/tracking-api}"
PHP_BIN="${PHP_BIN:-/usr/local/php82/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"

# Opciones
SKIP_DEPS=false
SKIP_MIGRATE=false
SKIP_PULL=false

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info()  { echo -e "${BLUE}[INFO]${NC}  $(date '+%H:%M:%S') $*"; }
log_ok()    { echo -e "${GREEN}[OK]${NC}    $(date '+%H:%M:%S') $*"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC}  $(date '+%H:%M:%S') $*"; }
log_error() { echo -e "${RED}[ERROR]${NC} $(date '+%H:%M:%S') $*"; }
log_step()  { echo -e "\n${CYAN}═══ $* ═══${NC}"; }

# ── Deploy ────────────────────────────────────────────────────
do_deploy() {
    local start_time=$(date +%s)

    log_step "DEPLOY TRACKING-API - $(date '+%Y-%m-%d %H:%M:%S')"

    # 1. Pull cambios
    if [ "$SKIP_PULL" = false ]; then
        log_step "GIT PULL"
        cd "$APP_DIR"
        git fetch origin main
        git reset --hard origin/main
        log_ok "Código actualizado"
    else
        log_info "Saltando git pull (--skip-pull)"
        cd "$APP_DIR"
    fi

    # 2. Dependencias PHP
    if [ "$SKIP_DEPS" = false ]; then
        log_step "DEPENDENCIAS PHP"
        $PHP_BIN $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
        log_ok "Composer install completado"
    else
        log_info "Saltando dependencias (--skip-deps)"
    fi

    # 3. Migraciones
    if [ "$SKIP_MIGRATE" = false ]; then
        log_step "MIGRACIONES"
        $PHP_BIN artisan migrate --force 2>&1
        log_ok "Migraciones ejecutadas"
    else
        log_info "Saltando migraciones (--skip-migrate)"
    fi

    # 3b. Garantizar roles/permisos y usuario admin permanente
    log_step "ROLES, PERMISOS Y ADMIN PERMANENTE"
    $PHP_BIN artisan db:seed --class=RolePermissionSeeder --force 2>&1
    $PHP_BIN artisan db:seed --class=AdminUserSeeder --force 2>&1
    $PHP_BIN artisan permission:cache-reset 2>&1 || true
    log_ok "Roles, permisos y admin garantizados"

    # 4. Publicar configs de paquetes (solo si no existen)
    log_step "PUBLICAR CONFIGS DE PAQUETES"
    if [ ! -f "$APP_DIR/config/jwt.php" ]; then
        $PHP_BIN artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" --no-interaction 2>&1
        log_ok "config/jwt.php publicado"
    else
        log_info "config/jwt.php ya existe — omitiendo publicación"
    fi

    # 4b. Garantizar que jwt.ttl y jwt.refresh_ttl sean int (fix Carbon TypeError)
    #     Reemplaza  env('JWT_TTL', 60)  →  (int) env('JWT_TTL', 60)  si aún no tiene el cast
    log_step "VERIFICAR CAST INT EN config/jwt.php"
    JWT_CFG="$APP_DIR/config/jwt.php"
    if grep -q "=> env('JWT_TTL'" "$JWT_CFG" && ! grep -q "(int) env('JWT_TTL'" "$JWT_CFG"; then
        sed -i "s/'ttl' => env('JWT_TTL',/'ttl' => (int) env('JWT_TTL',/g" "$JWT_CFG"
        sed -i "s/'refresh_ttl' => env('JWT_REFRESH_TTL',/'refresh_ttl' => (int) env('JWT_REFRESH_TTL',/g" "$JWT_CFG"
        log_ok "Cast (int) aplicado a jwt.ttl y jwt.refresh_ttl"
    else
        log_info "config/jwt.php ya tiene cast (int) — omitiendo"
    fi

    # 5. Cachés Laravel
    log_step "CACHÉS LARAVEL"
    $PHP_BIN artisan config:cache
    $PHP_BIN artisan route:cache
    $PHP_BIN artisan view:cache
    log_ok "Cachés generados"

    # 5. Swagger
    log_step "SWAGGER"
    $PHP_BIN artisan l5-swagger:generate 2>&1 || log_warn "Swagger falló (no crítico)"
    log_ok "Documentación Swagger actualizada"

    # 6. Permisos
    log_step "PERMISOS"
    sudo chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
    sudo chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
    log_ok "Permisos configurados"

    # 7. Reiniciar servicios web
    log_step "REINICIO WEB SERVER"
    sudo systemctl restart php-fpm.service 2>/dev/null || true

    # 7a. Garantizar proxy WebSocket en el VirtualHost SSL de Apache.
    #     Solo modifica api.nautic.run-le-ssl.conf — ningún otro conf se toca.
    #     El bloque se inserta una única vez (idempotente: verifica antes de insertar).
    log_step "APACHE — PROXY WEBSOCKET REVERB"
    SSL_CONF="/etc/apache2/sites-available/api.nautic.run-le-ssl.conf"
    if [ -f "$SSL_CONF" ]; then
        if ! grep -q "proxy_wstunnel\|ProxyPass.*8081\|Reverb WebSocket" "$SSL_CONF"; then
            sudo a2enmod proxy proxy_http proxy_wstunnel rewrite 2>/dev/null || true
            sudo sed -i 's|</VirtualHost>|    # Reverb WebSocket — wss://api.nautic.run/app/{key}\n    RewriteEngine On\n    RewriteCond %{HTTP:Upgrade} websocket [NC]\n    RewriteCond %{HTTP:Connection} upgrade [NC]\n    RewriteRule ^/app(.*)$ ws://127.0.0.1:8081/app$1 [P,L]\n\n    ProxyPass        /app  ws://127.0.0.1:8081/app\n    ProxyPassReverse /app  ws://127.0.0.1:8081/app\n</VirtualHost>|' "$SSL_CONF"
            log_ok "Proxy WebSocket insertado en $SSL_CONF"
        else
            log_info "Proxy WebSocket ya configurado — omitiendo"
        fi
        sudo apache2ctl configtest 2>&1 && sudo systemctl reload apache2 2>/dev/null || true
        log_ok "Apache recargado"
    else
        log_warn "$SSL_CONF no encontrado — proxy WebSocket no configurado"
    fi

    # 7b. Reverb WebSocket server (se crea el servicio si no existe — idempotente)
    log_step "REVERB WEBSOCKET"
    if ! systemctl list-unit-files reverb.service &>/dev/null || ! systemctl is-enabled --quiet reverb.service 2>/dev/null; then
        log_info "reverb.service no encontrado — creando servicio systemd..."
        PHP_BIN=$(which php 2>/dev/null || echo "/usr/bin/php")
        sudo tee /etc/systemd/system/reverb.service > /dev/null << REVERB_UNIT
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/tracking-api
ExecStart=${PHP_BIN} artisan reverb:start --host=0.0.0.0 --port=8081 --no-interaction
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
REVERB_UNIT
        sudo systemctl daemon-reload
        sudo systemctl enable reverb.service
        sudo systemctl start reverb.service
        log_ok "reverb.service creado y arrancado"
    else
        sudo systemctl restart reverb.service
        log_ok "Reverb reiniciado"
    fi

    # 8. Health check
    do_health

    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    log_step "DEPLOY COMPLETADO en ${duration}s"
}

# ── Status ────────────────────────────────────────────────────
do_status() {
    log_step "ESTADO DE SERVICIOS"
    echo ""
    for svc in php-fpm apache2 reverb; do
        if systemctl is-active --quiet "$svc.service" 2>/dev/null; then
            echo -e "  ${GREEN}●${NC} ${svc} — activo"
        else
            echo -e "  ${RED}●${NC} ${svc} — inactivo"
        fi
    done
    echo ""
}

# ── Health ────────────────────────────────────────────────────
do_health() {
    log_step "VERIFICACIÓN DE SALUD"

    local status_code
    status_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 http://localhost/up 2>/dev/null || echo "000")

    if [ "$status_code" = "200" ]; then
        log_ok "API respondiendo correctamente (HTTP $status_code)"
    else
        log_error "API no responde correctamente (HTTP $status_code)"
    fi
}

# ── Parse de argumentos ──────────────────────────────────────
parse_options() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --skip-deps)    SKIP_DEPS=true ;;
            --skip-migrate) SKIP_MIGRATE=true ;;
            --skip-pull)    SKIP_PULL=true ;;
            *) ;;
        esac
        shift
    done
}

# ── Main ──────────────────────────────────────────────────────
main() {
    local action="${1:-help}"
    shift || true
    parse_options "$@"

    case "$action" in
        deploy)  do_deploy ;;
        status)  do_status ;;
        health)  do_health ;;
        *)
            echo "Uso: $0 {deploy|status|health} [--skip-deps] [--skip-migrate] [--skip-pull]"
            exit 1
            ;;
    esac
}

main "$@"
