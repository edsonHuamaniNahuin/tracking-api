# Tracking API Core

Este repositorio contiene el **Core de Tracking de Barcos**, una solución backend en Laravel para gestionar:

* **Usuarios** y **Roles/Permisos**
* **Perfiles** de usuario
* **Embarcaciones** (Vessels)
* **Trackings** (posicionamiento y reportes)
* **Autenticación JWT** con refresco y 2FA
* **Preferencias** y notificaciones de usuario

---

## 📖 Descripción del Sistema

El sistema permite a diferentes perfiles de usuario interactuar con los recursos de:

1. **Auth**: login, logout, refresh token, recuperación de sesión.
2. **Profile**: ver y actualizar datos personales, cambiar contraseña, configurar preferencias (notificaciones, visibilidad, 2FA).
3. **Vessels**: CRUD de embarcaciones.
4. **Trackings**: CRUD de registros de tracking, listar por embarcación.
5. **Roles y Permisos**: asignar/revocar roles a usuarios, controlar acceso a endpoints.

Todo está versionado bajo el prefijo `/api/v1`.

---

## 🚀 Instalación y Ejecución

1. Clonar el repositorio:

   ```bash
   git clone https://tu-repo/tracking-api.git
   cd tracking-api
   ```
2. Instalar dependencias:

   ```bash
   composer install
   npm install
   ```
3. Configurar `.env` (base de datos, JWT\_SECRET, etc.)
4. Generar clave de aplicación:

   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```
5. Ejecutar migraciones y seeders:

   ```bash
   php artisan migrate
   ```
6. Actualizar documentacion Swagger:
   
   ```bash
   php artisan l5-swagger:generate
   ```

7. Correr el servidor:

   ```bash
   php artisan serve
   ```

---

## 📝 Documentación Swagger

Una vez arrancado Laravel, accede a la interfaz de Swagger para explorar y probar los endpoints:

```
http://localhost:8000/api/documentation
```

Allí encontrarás todos los recursos con sus esquemas de petición y respuesta.

---

## 🔐 Perfiles y Roles del Sistema

A continuación, los **perfiles** de usuario y los **roles** y permisos asociados:

### Perfiles (Grupos de Roles)

1. **System Administrator**

   * Rol: `Administrator`
   * Permisos: todos los recursos y operaciones.

2. **Fleet Manager**

   * Roles: `Manager`, `Operator`
   * Permisos: CRUD de embarcaciones, CRUD de trackings, ver reportes.

3. **Operator**

   * Rol: `Operator`
   * Permisos: crear y actualizar trackings; ver embarcaciones.

4. **Viewer**

   * Rol: `Viewer`
   * Permisos: solo lectura de embarcaciones y trackings.

5. **Guest**

   * Rol: `Guest`
   * Permisos mínimos: acceso público a rutas autorizadas.

---

### Roles y Permisos Detallados

| Rol               | Permisos                                                                                                 |
| ----------------- | -------------------------------------------------------------------------------------------------------- |
| **Administrator** | `manage_users`, `manage_roles`, `manage_vessels`, `manage_trackings`, `view_reports`, `configure_system` |
| **Manager**       | `manage_vessels`, `manage_trackings`, `view_reports`                                                     |
| **Operator**      | `create_tracking`, `update_tracking`, `view_vessels`, `view_trackings`                                   |
| **Viewer**        | `view_vessels`, `view_trackings`                                                                         |
| **Guest**         | `view_public_info`                                                                                       |

Puedes extender o ajustar estos perfiles en función de los requerimientos específicos de tu proyecto.

---

## 🤝 Contribuciones

1. Crear una rama con tu feature: `git checkout -b feature/nombre`
2. Hacer commit de tus cambios: `git commit -m 'Añade nuevo feature'`
3. Push a la branch: `git push origin feature/nombre`
4. Abrir Pull Request.

---

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Consulte el archivo `LICENSE` para más detalles.
 