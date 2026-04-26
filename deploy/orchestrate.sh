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

    # 4. Publicar configs de paquetes (solo si no existen)
    log_step "PUBLICAR CONFIGS DE PAQUETES"
    if [ ! -f "$APP_DIR/config/jwt.php" ]; then
        $PHP_BIN artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" --no-interaction 2>&1
        log_ok "config/jwt.php publicado"
    else
        log_info "config/jwt.php ya existe — omitiendo"
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
    sudo systemctl reload apache2 2>/dev/null || true
    log_ok "PHP-FPM y Apache reiniciados"

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
    for svc in php-fpm apache2; do
        if systemctl is-active --quiet "$svc" 2>/dev/null; then
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
