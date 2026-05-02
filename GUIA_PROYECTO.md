# 🧭 GUÍA DEL PROYECTO - easySeri

---

# 🧭 ESTADO ACTUAL EN VIVO

FASE ACTUAL: FASE 1 — SANEAMIENTO (camaras-ubicacion)

ESTADO REAL:

✔ Fix user_id → COMPLETADO  
✔ Fix lógica sync_sap (pendientes reales) → COMPLETADO  
⏸ moves_log → CONGELADO (pendiente futuro)  
⏸ pruebas reales → PENDIENTE (no en planta)  

SIGUIENTE PASO:
→ Crear módulo camaras-ubicacion en easySeri (FASE 2)

---

# 🎯 OBJETIVO

Construir una plataforma modular única (easySeri) que unifique todas las aplicaciones existentes en un solo sistema, con:

- Control de usuarios
- Sistema de permisos
- Módulos independientes
- Activación/desactivación dinámica

---

# 🧱 ARQUITECTURA BASE

- Core central (sin lógica de negocio)
- Módulos independientes
- Base de datos común
- Sistema de permisos por roles
- Menú dinámico
- Routing centralizado

---

# 📊 ESTADO GLOBAL DEL PROYECTO

## ✅ FASE 0 — Base
- [x] Estructura
- [x] Repo Git
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
- [x] Relaciones
- [x] Auth
- [x] PermissionService

---

## ✅ FASE 3 — Router/Layout
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

## 🟡 FASE 6 — Core administrativo
(REALMENTE YA TERMINADO EN PRÁCTICA)

---

# 🚀 FASE 7 — MÓDULO REAL: CAMARAS-UBICACION

---

## 📘 DOCUMENTO MAESTRO — CAMARAS-UBICACION

### 🎯 OBJETIVO

Sistema real de:

- Escaneo de palets
- Ubicación en cámaras
- Integración ERP
- Movimientos
- Control físico de almacén

---

## 🧠 ARQUITECTURA

### Flujo principal
ESCANEO
→ lookup ERP mirror
→ entrada
→ pendientes
→ sugerencia ubicación
→ confirmación
→ placement

---

### Flujo SAP
SAP (sin internet)
→ SQL Server
→ sync_sap.php
→ MariaDB
→ easySeri

⚠️ Nunca conectar easySeri directamente a SAP

---

## 🗃️ BASE DE DATOS

### Configuración
- cameras
- camera_positions
- camera_row_groups
- camera_row_cells

### Ubicación
- placements
- moves_log

### ERP mirror
- erp_entradas_mirror
- erp_palets_mirror
- erp_plegados_mirror
- erp_entries_pending

---

## ⚠️ PROBLEMAS DETECTADOS

### ✔ 1. user_id incorrecto → SOLUCIONADO

Antes:$_SESSION['user_id']
ahora: $_SESSION['user']['id']

---

### ✔ 2. sync_sap incorrecto → SOLUCIONADO

Antes:si existe 1 placement → entrada completa
Ahora: placed = COUNT placements activos
remaining = total - placed

⚠️ Compatible con status actual:
- pending
- complete
- stale

---

### ⏸ 3. moves_log inconsistente → PENDIENTE

NO TOCAR AÚN

---

### ⚠️ 4. credenciales en código → PENDIENTE

Mover a .env

---

### ⚠️ 5. endpoints duplicados → PENDIENTE

plegado_confirm / plegado_place

---

### ⚠️ 6. archivos inseguros → PENDIENTE

*_old  
*_mock  
tools  

---

## 🧩 INTEGRACIÓN EN easySeri

### Estructura módulo
modules/camaras-ubicacion/
├── module.json
├── index.php
├── scan.php
├── api/
├── services/
├── assets/

---

### Permisos
camaras-ubicacion.access
camaras-ubicacion.scan
camaras-ubicacion.move
camaras-ubicacion.admin
camaras-ubicacion.reports

---

## 🛠️ PLAN REAL

### 🟢 FASE 0 — Auditoría
✔ COMPLETADO

---

### 🟡 FASE 1 — Saneamiento
✔ user_id  
✔ sync_sap  
⏸ moves_log  

---

### 🟡 FASE 2 — Crear módulo
- [ ] module.json
- [ ] ruta
- [ ] menú
- [ ] permisos

---

### 🟡 FASE 3 — Integración mínima
- [ ] scan.php dentro módulo
- [ ] layout core
- [ ] eliminar login antiguo

---

### 🟡 FASE 4 — APIs
- [ ] scan_lookup
- [ ] scan_confirm
- [ ] camera_rows

---

### 🟡 FASE 5 — Movimientos
- [ ] move_entry
- [ ] move_pallet

---

### 🟡 FASE 6 — Admin
- [ ] cámaras
- [ ] filas

---

### 🟡 FASE 7 — Sync SAP
✔ lógica corregida  
[ ] documentar  
[ ] validar  

---

### 🟡 FASE 8 — PRUEBAS
⏸ pendiente planta

---

# 📏 NORMAS

- No romper core
- No tocar producción sin control
- No suponer estructuras
- Documentar TODO cambio
- Modular siempre

---

# 🧠 FILOSOFÍA

- Primero estabilidad
- Luego integración
- Luego refactor

---

# 🧭 NOTAS

Este documento es la referencia principal.

Siempre actualizar:
- al terminar fase
- antes de empezar la siguiente