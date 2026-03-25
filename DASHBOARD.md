# Dashboard de Tracking - Métricas y APIs

Este documento describe cómo generar datos de prueba y utilizar las APIs del dashboard de tracking.

## 🚀 Generación de Datos

### Comando Rápido
```bash
php artisan dashboard:generate-data
```

### Reiniciar Completamente
```bash
php artisan dashboard:generate-data --fresh
```

## 📊 Datos Generados

El seeder crea aproximadamente **245 embarcaciones** distribuidas de la siguiente manera:

### Por Tipo
- **Carguero**: 65 embarcaciones
- **Petrolero**: 40 embarcaciones
- **Pasajeros**: 30 embarcaciones
- **Pesquero**: 50 embarcaciones
- **Remolcador**: 25 embarcaciones
- **Otros**: 35 embarcaciones

### Por Estado
- **Activas**: 189 embarcaciones
- **En Mantenimiento**: 42 embarcaciones
- **Inactivas**: 14 embarcaciones
- **Con Alertas**: 8 embarcaciones

### Datos Adicionales
- **Trackings históricos** para embarcaciones activas (20-100 por embarcación)
- **Métricas de rendimiento** por los últimos 12 meses
- **Distribución de antigüedad** (0-30+ años)
- **Coordenadas realistas** para el mapa

## 🔗 APIs del Dashboard

### Autenticación
Todas las APIs requieren autenticación JWT:
```bash
Authorization: Bearer {your-jwt-token}
```

### Usuario de Prueba
- **Email**: `admin@tracking.com`
- **Password**: `admin123`

### Endpoints Disponibles

#### 1. Todas las Métricas (Recomendado)
```http
GET /api/v1/dashboard/all-metrics
```
Retorna todas las métricas en una sola llamada.

#### 2. Métricas Principales
```http
GET /api/v1/dashboard/metrics
```
```json
{
  "total_vessels": 245,
  "active_vessels": 189,
  "maintenance_vessels": 42,
  "alert_vessels": 8
}
```

#### 3. Embarcaciones por Tipo (Gráfico de Barras)
```http
GET /api/v1/dashboard/vessels-by-type
```
```json
[
  { "name": "Carguero", "value": 65, "color": "#2563eb" },
  { "name": "Petrolero", "value": 40, "color": "#0891b2" },
  { "name": "Pasajeros", "value": 30, "color": "#4f46e5" }
]
```

#### 4. Actividad Mensual por Tipo
```http
GET /api/v1/dashboard/monthly-activity
```
```json
[
  { "name": "Ene", "cargueros": 65, "petroleros": 28, "pasajeros": 40 },
  { "name": "Feb", "cargueros": 59, "petroleros": 32, "pasajeros": 36 }
]
```

#### 5. Embarcaciones por Estado
```http
GET /api/v1/dashboard/vessels-by-status
```
```json
[
  { "name": "Activas", "value": 189, "color": "#22c55e", "icon": "CheckCircle" },
  { "name": "En Mantenimiento", "value": 42, "color": "#f59e0b", "icon": "Anchor" }
]
```

#### 6. Distribución de Antigüedad
```http
GET /api/v1/dashboard/fleet-aging
```
```json
[
  { "name": "0-5 años", "value": 45, "color": "#22c55e" },
  { "name": "6-10 años", "value": 65, "color": "#10b981" }
]
```

#### 7. Métricas de Rendimiento (Radar Chart)
```http
GET /api/v1/dashboard/performance-metrics
```
```json
[
  { "subject": "Eficiencia", "carguero": 80, "petrolero": 90, "pasajeros": 70 },
  { "subject": "Velocidad", "carguero": 65, "petrolero": 60, "pasajeros": 85 }
]
```

#### 8. Posiciones para Mapa
```http
GET /api/v1/dashboard/vessel-positions
```
```json
[
  {
    "id": 1,
    "name": "Atlantic Explorer",
    "position": { "x": 30, "y": 40 },
    "type": "Carguero",
    "coordinates": { "lat": -23.5505, "lng": -46.6333 }
  }
]
```

## 🎨 Estructura de Tabs del Dashboard

### Tab 1: Tipos de Embarcaciones
- **Componente**: Gráfico de barras
- **API**: `/dashboard/vessels-by-type`
- **Visualización**: Chart.js, Recharts, etc.

### Tab 2: Actividad
- **Componente**: Gráfico de líneas múltiples
- **API**: `/dashboard/monthly-activity`
- **Visualización**: Área o líneas por tipo

### Tab 3: Estado
- **Componente**: Cards con iconos
- **API**: `/dashboard/vessels-by-status`
- **Visualización**: Grid de tarjetas con colores

### Tab 4: Antigüedad
- **Componente**: Gráfico de dona/pie
- **API**: `/dashboard/fleet-aging`
- **Visualización**: Distribución por rangos de edad

### Tab 5: Rendimiento
- **Componente**: Radar/Spider chart
- **API**: `/dashboard/performance-metrics`
- **Visualización**: Comparativa por tipo

### Tab 6: Mapa
- **Componente**: Mapa interactivo
- **API**: `/dashboard/vessel-positions`
- **Visualización**: Leaflet, Google Maps, etc.

## 🛠️ Integración Frontend

### Ejemplo con fetch
```javascript
// Obtener todas las métricas
const response = await fetch('/api/v1/dashboard/all-metrics', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const data = await response.json();
```

### Ejemplo con axios
```javascript
const token = localStorage.getItem('auth_token');

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

// Cargar métricas principales
const metrics = await api.get('/dashboard/metrics');

// Cargar datos para gráfico de tipos
const typeData = await api.get('/dashboard/vessels-by-type');
```

## 🔄 Actualización de Datos

Los datos se actualizan automáticamente basándose en:
- **Nuevas embarcaciones** creadas por usuarios
- **Cambios de estado** de embarcaciones existentes
- **Nuevos trackings** registrados
- **Métricas** calculadas periódicamente

## 📝 Notas Importantes

1. **Permisos**: Los datos mostrados dependen del rol del usuario
   - **Administradores**: Ven todas las embarcaciones
   - **Usuarios normales**: Solo sus embarcaciones

2. **Rendimiento**: El endpoint `/all-metrics` es más eficiente para cargar el dashboard completo

3. **Caché**: Considera implementar caché para mejorar el rendimiento en producción

4. **Real-time**: Para actualizaciones en tiempo real, considera usar WebSockets o Server-Sent Events
