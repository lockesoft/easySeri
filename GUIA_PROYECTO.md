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
- [x ] Listado de módulos instalados
- [ x] Activar/desactivar módulos
- [x ] Ver estado y versión
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
 Añado nuevo orden y documento amestro para la parte especial de camaras
📘 DOCUMENTO MAESTRO — MÓDULO CAMARAS-UBICACION (easySeri)
📍 Estado general del proyecto
PROYECTO: easySeri
MÓDULO: camaras-ubicacion
ESTADO: Fase 0 — Auditoría completada / Inicio integración
🎯 OBJETIVO DEL MÓDULO

Sistema de ubicación de palets en cámaras frigoríficas con:

✔ Escaneo con lector de códigos (ZXing)
✔ Integración con ERP (SAP vía SQL Server → mirror MariaDB)
✔ Ubicación automática por capacidad
✔ Control de duplicados
✔ Movimientos de palets / entradas
✔ Gestión de cámaras físicas
✔ Sistema simple para operarios (modo kiosco)
🧠 ARQUITECTURA GENERAL
Flujo principal
ESCANEO PALLET
→ lookup en erp_palets_mirror
→ obtener entrada
→ calcular pendientes
→ sugerir cámara/fila
→ confirmar ubicación
→ guardar en placements
→ registrar en moves_log
Flujo de datos ERP
SERVIDOR SAP (SIN INTERNET)
↓ ODBC
SQL SERVER (SERIFRUIT)
↓ sync_sap.php
MariaDB (ubicacion)
↓
easySeri (lectura local)

⚠️ Importante:

easySeri NO accede directamente a SAP

🗃️ BASE DE DATOS (ACTUAL)
Configuración física
cameras
camera_positions
camera_row_groups
camera_row_cells
camera_conditions
Ubicación real
placements
moves_log
Datos espejo ERP
erp_entradas_mirror
erp_palets_mirror
erp_plegados_mirror
erp_entries_pending
erp_entries_suppressed
⚠️ A ELIMINAR
users (NO se usará — sustituido por core_users)
⚠️ PROBLEMAS DETECTADOS (CRÍTICOS)
🔴 1. user_id incorrecto
$_SESSION['user']['id'] vs $_SESSION['user_id']

➡ placements puede estar guardando NULL

🔴 2. moves_log inconsistente
ENUM:
move_row
move_entry
move_pallet

Pero el código usa:

scan_case1
scan_case2

➡ riesgo de fallo silencioso

🔴 3. sync_sap lógica incorrecta
Si existe 1 placement → marca entrada completa

➡ INCORRECTO

Debe ser:

placed = COUNT placements
remaining = total - placed
🔴 4. credenciales en código
includes/config.php

➡ mover a .env

🔴 5. endpoints duplicados / incoherentes
plegado_confirm.php
plegado_place.php

➡ unificar lógica

🔴 6. archivos peligrosos en producción
*_old.php
*_mock.php
tools/*
_selftest_delete.php

➡ eliminar o proteger

🧩 INTEGRACIÓN CON easySeri
Estructura del módulo
modules/camaras-ubicacion/
├── module.json
├── index.php
├── scan.php
├── move.php
├── admin/
├── reports/
├── api/
├── services/
├── assets/
└── legacy/ (temporal)
Permisos
camaras-ubicacion.access
camaras-ubicacion.scan
camaras-ubicacion.move
camaras-ubicacion.admin
camaras-ubicacion.reports
🛠️ PLAN DE DESARROLLO (CHECKLIST)
🟢 FASE 0 — Auditoría (COMPLETADO)
 Revisar SQL
 Revisar sync_sap.php
 Revisar app completa
 Detectar problemas críticos
🟡 FASE 1 — Saneamiento (ANTES DE INTEGRAR)
 Corregir user_id
 Corregir moves_log
 Arreglar lógica de pendientes en sync
 Eliminar credenciales hardcode
 Limpiar archivos mock/test
 Unificar endpoints plegado
🟡 FASE 2 — Crear módulo en easySeri
 Crear carpeta modules/camaras-ubicacion
 Crear module.json
 Añadir ruta en router
 Ver módulo en menú
 Proteger con permisos
🟡 FASE 3 — Integración mínima
 Mover scan.php al módulo
 Adaptar layout (renderLayout)
 Adaptar conexión DB a db()
 Eliminar login antiguo
🟡 FASE 4 — APIs
 Migrar scan_lookup
 Migrar scan_confirm
 Migrar camera_rows
 Migrar entry_counts
 Validar duplicados
🟡 FASE 5 — Movimientos
 move_entry
 move_pallet
 logs correctos
🟡 FASE 6 — Admin
 cámaras
 filas
 posiciones
🟡 FASE 7 — Sync SAP
 corregir lógica
 documentar ejecución
 validar volcados
🟡 FASE 8 — Pruebas reales
 escaneo real
 duplicados
 ubicaciones
 movimientos
 volcados ERP
🧠 DECISIONES IMPORTANTES
✔ NO reescribir desde cero
✔ Mantener tablas actuales
✔ Mantener sync_sap externo
✔ Migración progresiva
✔ Primero estabilidad, luego refactor
🚀 SIGUIENTE PASO INMEDIATO

👉 Empezar por FASE 1 — Saneamiento

Concretamente:

1. Arreglar user_id
2. Arreglar moves_log
3. Arreglar lógica pendientes sync 

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