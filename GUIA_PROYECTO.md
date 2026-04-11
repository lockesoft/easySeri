# 🧭 GUÍA DEL PROYECTO - easySeri

## 🎯 OBJETIVO

Construir una plataforma modular única (easySeri) que unifique todas las aplicaciones actuales en un solo sistema, con control de usuarios, permisos y módulos activables/desactivables.

---

# 🧱 ARQUITECTURA BASE

- Core central
- Módulos independientes
- Base de datos común
- Sistema de permisos
- Menú dinámico
- Routing centralizado

---

# 📊 ESTADO ACTUAL DEL PROYECTO

## ✅ FASE 0 — Base del proyecto
- [x] Estructura de carpetas creada
- [x] Proyecto inicial en local
- [x] Repositorio Git creado
- [x] Subida a GitHub
- [x] `.gitignore` configurado
- [x] `.env` protegido (no subido)

---

## ✅ FASE 1 — Core mínimo funcional
- [x] Conexión a base de datos
- [x] Tabla `core_modules`
- [x] Module Manager básico
- [x] Lectura de módulos desde `/modules`
- [x] Sincronización con BD
- [x] Activación/desactivación de módulos desde BD

---

## ✅ FASE 2 — Sistema de usuarios y permisos
- [x] Tabla `core_users`
- [x] Tabla `core_roles`
- [x] Tabla `core_permissions`
- [x] Tablas pivote (`user_roles`, `role_permissions`)
- [x] Auth básico (usuario simulado)
- [x] PermissionService funcional
- [x] Control de acceso a módulos

---

## ✅ FASE 3 — Router + Layout
- [x] Router básico funcional
- [x] Soporte basePath (servidor)
- [x] Layout base (header + menú + contenido)
- [x] Renderizado con `$content`
- [x] Menú dinámico por módulos activos
- [x] Menú filtrado por permisos

---

## ✅ FASE 4 — Módulo de prueba (welcome)
- [x] Módulo `welcome` creado
- [x] `module.json`
- [x] `menu.php`
- [x] `index.php`
- [x] Control de acceso funcionando
- [x] Render dentro del layout
- [x] Ruta `/welcome` funcional

---

## ✅ FASE 5 — Mejora UX inicial
- [x] Menú dinámico
- [x] Módulo activo resaltado
- [x] Información de usuario visible

---

# 🚧 FASE ACTUAL

👉 AFINADO DEL CORE ADMINISTRATIVO

Antes de integrar aplicaciones reales, se completará la base común del sistema para que cualquier módulo futuro entre sobre una estructura estable.

---

# 📍 SIGUIENTE FASE

## 🔜 FASE 6 — Core administrativo

### 6.1 Login real y sesión
- [ ] Formulario de login
- [ ] Validación de usuario
- [ ] Password hash real
- [ ] Sesión PHP
- [ ] Logout
- [ ] Protección de rutas

### 6.2 Gestión de usuarios
- [ ] Listado de usuarios
- [ ] Crear usuario
- [ ] Editar usuario
- [ ] Activar/desactivar usuario
- [ ] Asignar roles a usuario
- [ ] Cambio de contraseña

### 6.3 Gestión de roles y permisos
- [ ] Listado de roles
- [ ] Crear rol
- [ ] Editar rol
- [ ] Asignar permisos a rol
- [ ] Revisión de permisos por módulo

### 6.4 Gestión de módulos
- [ ] Listado de módulos instalados
- [ ] Activar/desactivar módulos
- [ ] Ver versión y estado
- [ ] Preparar dependencias de módulos

### 6.5 Panel administrativo base
- [ ] Menú de administración
- [ ] Acceso solo administrador
- [ ] Navegación interna entre módulos del core

---

# 📍 FASE POSTERIOR

## 🔜 FASE 7 — Integración de cámaras como primer módulo real

Objetivo:
Integrar la aplicación existente de cámaras una vez esté consolidado el core administrativo.

👉 PREPARACIÓN PARA INTEGRACIÓN DE APLICACIONES REALES

---






---

# 📏 NORMAS DEL PROYECTO

## 🔒 Seguridad
- Nunca subir `.env`
- Nunca subir credenciales
- Todo dato sensible fuera del repo

---

## 🧩 Módulos
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
- Un módulo no debe depender directamente de otro
- Solo a través del core o servicios compartidos

---

# ⚠️ REGLAS CRÍTICAS

1. No romper el core
2. No mezclar lógica entre módulos
3. Todo cambio importante se documenta
4. No improvisar estructura
5. Siempre validar antes de escalar

---

# 🧭 FILOSOFÍA

- Primero estructura, luego funcionalidad
- Primero simple, luego potente
- Modular siempre
- Evitar dependencias ocultas

---

# 🧠 NOTAS IMPORTANTES

Este documento es la referencia principal del proyecto.

Debe actualizarse:
- después de cada fase completada
- antes de iniciar una nueva fase