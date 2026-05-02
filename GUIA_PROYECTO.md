# 🧭 GUÍA DEL PROYECTO - easySeri

---

# 🧭 ESTADO ACTUAL EN VIVO

FASE ACTUAL: FASE 3 — INTEGRACIÓN MÍNIMA DEL MÓDULO `camaras-ubicacion`

ESTADO REAL:

✔ Core easySeri funcionando  
✔ Login / logout funcionando  
✔ Usuarios, roles, permisos y módulos funcionando  
✔ Módulo `camaras-ubicacion` creado  
✔ Módulo visible en `admin-modulos`  
✔ Módulo visible en menú con permiso `camaras-ubicacion.access`  
✔ Ruta `/easyseri/camaras-ubicacion` funcionando  
✔ Ruta `/easyseri/camaras-ubicacion/scan` funcionando  
✔ Botón desde módulo hacia escaneo funcionando  
✔ Pantalla legacy `scan.php` cargando dentro de iframe  
✔ Cámara cargando correctamente dentro del iframe  
✔ Adaptador de auth legacy conectado a Auth de easySeri  
✔ Credenciales legacy movidas a `.env`  
✔ `config.php` legacy compatible con PHP 7  
✔ Fix `user_id` realizado  
✔ Fix lógica `sync_sap` realizado  

⏸ `moves_log` congelado para más adelante  
⏸ pruebas reales de escaneo pendientes (no en planta)

SIGUIENTE PASO:
→ Revisar endpoints legacy uno a uno sin romper lógica  
→ Preparar pruebas controladas antes de uso real  

---

# 🎯 OBJETIVO

Construir una plataforma modular única (`easySeri`) que unifique aplicaciones internas en un solo sistema:

- Control de usuarios
- Sistema de roles
- Sistema de permisos
- Módulos independientes
- Activación/desactivación dinámica
- Menú dinámico
- Core sin lógica de negocio

---

# 🧱 ARQUITECTURA BASE

- Core central (sin lógica de negocio)
- Módulos independientes (`/modules`)
- Base de datos común
- Sistema de permisos por roles
- Routing centralizado
- Layout común
- `.env` fuera del repositorio

---

# 📊 ESTADO GLOBAL DEL PROYECTO

## ✅ FASE 0 — Base

- [x] Estructura
- [x] Git
- [x] GitHub
- [x] .gitignore
- [x] .env

---

## ✅ FASE 1 — Core técnico

- [x] DB
- [x] core_modules
- [x] ModuleManager
- [x] Sync módulos
- [x] Activación módulos

---

## ✅ FASE 2 — Permisos

- [x] core_users
- [x] core_roles
- [x] core_permissions
- [x] relaciones
- [x] Auth
- [x] PermissionService

---

## ✅ FASE 3 — Router / Layout

- [x] Router
- [x] basePath
- [x] Layout
- [x] Render
- [x] Menú dinámico

---

## ✅ FASE 4 — Módulo prueba

- [x] welcome

---

## ✅ FASE 5 — UX

- [x] Menú dinámico
- [x] Usuario visible

---

## ✅ FASE 6 — Core administrativo

- [x] Login
- [x] Logout
- [x] Sesiones
- [x] Password hash
- [x] Protección rutas
- [x] admin-usuarios
- [x] admin-roles
- [x] admin-modulos

---

# 🚀 FASE 7 — MÓDULO REAL: CAMARAS-UBICACION

---

# 📘 DOCUMENTO MAESTRO — CAMARAS-UBICACION

## 🎯 OBJETIVO

Sistema de:

- Escaneo de palets
- Ubicación en cámaras
- Integración ERP (mirror)
- Movimientos
- Control real de almacén

---

## 🧠 FLUJO FUNCIONAL

ESCANEO  
→ lookup ERP  
→ entrada  
→ pendientes  
→ sugerencia  
→ confirmación  
→ placement  

---

## 🔄 FLUJO SAP

SAP local  
→ SQL Server  
→ sync_sap.php  
→ MariaDB  
→ easySeri  

⚠️ easySeri NO conecta a SAP directamente

---

# 🗃️ BASE DE DATOS

## Configuración

- cameras
- camera_positions
- camera_row_groups
- camera_row_cells

## Ubicación

- placements
- moves_log

## ERP mirror

- erp_entradas_mirror
- erp_palets_mirror
- erp_plegados_mirror
- erp_entries_pending

---

# 🧩 ESTRUCTURA ACTUAL DEL MÓDULO
modules/camaras-ubicacion/
├── module.json
├── menu.php
├── index.php
├── scan.php
├── includes/
│ ├── auth.php
│ ├── config.php
│ ├── db.php
│ ├── header.php
│ ├── footer.php
│ └── helpers.php
└── legacy/
├── scan.php
├── api/
│ ├── pallet_status.php
│ ├── entry_counts.php
│ ├── cameras.php
│ ├── camera_rows.php
│ ├── scan_confirm.php
│ └── move_confirm.php
└── assets/
└── css/
└── custom.css

---

# 🔐 PERMISOS

- camaras-ubicacion.access ✔
- camaras-ubicacion.scan ⏸
- camaras-ubicacion.move ⏸
- camaras-ubicacion.admin ⏸
- camaras-ubicacion.reports ⏸

---

# ⚠️ PROBLEMAS Y ESTADO

## ✔ user_id → SOLUCIONADO

## ✔ sync_sap → SOLUCIONADO

## ⏸ moves_log → CONGELADO

## ✔ credenciales → SOLUCIONADO (.env)

## ⚠ endpoints duplicados → PENDIENTE

## ⚠ archivos legacy inseguros → PENDIENTE

---

# 🛠️ PLAN REAL

## ✅ FASE 0 — Auditoría

✔ hecha

---

## ✅ FASE 1 — Saneamiento

✔ user_id  
✔ sync_sap  
✔ credenciales  
⏸ moves_log  

---

## ✅ FASE 2 — Crear módulo

✔ module.json  
✔ ruta  
✔ menú  
✔ permisos  

---

## 🟡 FASE 3 — Integración mínima

✔ scan iframe  
✔ legacy cargado  
✔ auth adaptado  
✔ cámara funcionando  
✔ botón acceso  

Pendiente:

- [ ] probar lectura real
- [ ] probar confirmación
- [ ] validar endpoints

---

## 🟡 FASE 4 — APIs

- [ ] revisar endpoints uno a uno
- [ ] validar respuestas
- [ ] limpiar errores
- [ ] revisar permisos

---

## 🟡 FASE 5 — Movimientos

- [ ] revisar lógica
- [ ] revisar moves_log
- [ ] no tocar aún

---

## 🟡 FASE 6 — Admin cámaras

- [ ] migrar panel admin

---

## 🟡 FASE 7 — Informes

- [ ] migrar reports

---

## 🟡 FASE 8 — Sync SAP

- [ ] validar en producción real
- [ ] documentar

---

## 🟡 FASE 9 — PRUEBAS EN PLANTA

- [ ] escaneo real
- [ ] ubicación real
- [ ] movimientos
- [ ] volcados

---

# 📏 NORMAS

- No romper core
- No suponer nada
- Revisar archivo real siempre
- Cambios controlados
- Documentar todo

---

# 🧠 FILOSOFÍA

- Primero estabilidad
- Luego integración
- Luego refactor

---

# 🧭 NOTAS

Documento vivo del proyecto.

Actualizar siempre:
- al cerrar fase
- al detectar problema
- antes de avanzar