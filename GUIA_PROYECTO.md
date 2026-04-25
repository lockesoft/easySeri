# 🧭 GUÍA DEL PROYECTO - easySeri

## 🎯 OBJETIVO

Construir una plataforma modular única (easySeri) que unifique todas las aplicaciones existentes en un solo sistema, con control de usuarios, permisos y módulos activables/desactivables.

---

# 🧱 ARQUITECTURA BASE

- Core central (sin lógica de negocio)
- Módulos independientes
- Base de datos común
- Sistema de permisos por roles
- Menú dinámico
- Routing centralizado

---

# 📊 ESTADO ACTUAL DEL PROYECTO

## ✅ FASE 0 — Base del proyecto
- [x] Estructura de carpetas
- [x] Proyecto local inicial
- [x] Repositorio Git creado
- [x] Subida a GitHub
- [x] `.gitignore` configurado
- [x] `.env` protegido

---

## ✅ FASE 1 — Core técnico
- [x] Conexión a base de datos
- [x] Tabla `core_modules`
- [x] Module Manager
- [x] Lectura de módulos desde `/modules`
- [x] Sincronización con BD
- [x] Activación/desactivación de módulos

---

## ✅ FASE 2 — Sistema de permisos
- [x] Tabla `core_users`
- [x] Tabla `core_roles`
- [x] Tabla `core_permissions`
- [x] Relaciones (`user_roles`, `role_permissions`)
- [x] Auth básico (simulado)
- [x] PermissionService
- [x] Control de acceso por módulo

---

## ✅ FASE 3 — Router y Layout
- [x] Router funcional
- [x] Soporte basePath
- [x] Layout base
- [x] Render con `$content`
- [x] Menú dinámico
- [x] Filtro por permisos

---

## ✅ FASE 4 — Módulo de prueba
- [x] Módulo `welcome`
- [x] `module.json`
- [x] `menu.php`
- [x] `index.php`
- [x] Ruta `/welcome`
- [x] Render dentro del layout

---

## ✅ FASE 5 — UX inicial
- [x] Menú dinámico
- [x] Módulo activo resaltado
- [x] Usuario visible en layout

---

# 🚧 FASE ACTUAL

👉 CONSOLIDACIÓN DEL CORE ADMINISTRATIVO

Antes de integrar aplicaciones reales, se completa toda la base común del sistema.

---

# 📍 FASE 6 — CORE ADMINISTRATIVO

## 🔐 6.1 Login y sesión
- [ x] Formulario de login
- [ x] Validación de usuario
- [x ] Password hash (bcrypt)
- [ x] Sesión PHP
- [ x] Logout
- [ x] Protección de rutas
- [ x] Redirección automática si no autenticado

---

## 👤 6.2 Gestión de usuarios
- [x ] Listado de usuarios
- [x ] Crear usuario
- [ x] Editar usuario
- [ x] Activar/desactivar usuario
- [ x] Asignar roles
- [ x] Cambio de contraseña

---

## 🧩 6.3 Roles y permisos
- [x ] Listado de roles
- [ x] Crear rol
- [ x] Editar rol
- [ x] Asignar permisos a roles
- [ x] Permisos por módulo (`modulo.access`)

---
## 🔐 6.3-2 Seguridad básica
- [x] Protección de rutas por permisos
- [x] Validación de acceso en módulos internos

## ⚙️ 6.4 Gestión de módulos
- [ ] Listado de módulos instalados
- [ ] Activar/desactivar módulos
- [ ] Ver estado y versión
- [ ] Preparar dependencias futuras

---

## 🧭 6.5 Panel administrativo
- [ ] Módulo `admin-usuarios`
- [ ] Módulo `admin-roles`
- [ ] Módulo `admin-modulos`
- [ ] Acceso solo admin
- [ ] Navegación interna

---

# 📍 FASE 7 — PRIMER MÓDULO REAL

## 🎥 Integración de cámaras (modo puente)

Objetivo:
Integrar la aplicación existente sin modificarla inicialmente.

- [ ] Crear módulo `camaras`
- [ ] Añadir `module.json`
- [ ] Añadir `menu.php`
- [ ] Añadir permisos (`camaras.access`)
- [ ] Integración vía iframe o redirección
- [ ] Control de acceso por permisos

---

# 📍 FASE 8 — EVOLUCIÓN DE MÓDULOS

- [ ] Migración progresiva de cámaras al sistema modular
- [ ] Integración KPI
- [ ] Integración mantenimiento
- [ ] Integración ERP

---

# 📍 FASE 9 — ENTORNO INDUSTRIAL

- [ ] Rapid Pack
- [ ] AAF
- [ ] Node-RED
- [ ] Eventos en tiempo real

---

# 📏 NORMAS DEL PROYECTO

## 🔒 Seguridad
- Nunca subir `.env`
- Nunca subir credenciales
- Todo dato sensible fuera del repo

---

## 🧩 Estructura de módulos

Todo módulo debe tener:

- `module.json`
- `menu.php`
- `permissions.php`
- `index.php`

---

## 🧠 Core

- No contiene lógica de negocio
- Solo orquesta módulos

---

## 🔗 Dependencias

- Los módulos no dependen entre sí directamente
- Comunicación solo a través del core o servicios comunes

---

# ⚠️ REGLAS CRÍTICAS

1. No romper el core
2. No mezclar lógica entre módulos
3. Documentar cambios importantes
4. Mantener coherencia estructural
5. Validar antes de escalar

---

# 🧭 FILOSOFÍA

- Primero estructura, luego funcionalidad
- Primero simple, luego potente
- Modular siempre
- Evitar dependencias ocultas
- Todo el codigo bien comentado para que quede claro a posteriori lo lea quien lo lea

---

# 🧠 NOTAS

Este documento es la referencia principal del proyecto.

Debe actualizarse:
- al completar cada fase
- antes de iniciar una nueva fase