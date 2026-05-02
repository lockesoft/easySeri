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
✔ Pantalla legacy `scan.php` cargando dentro de iframe  
✔ Cámara cargando correctamente dentro del iframe  
✔ Adaptador de auth legacy conectado a Auth de easySeri  
✔ Credenciales legacy movidas a `.env`  
✔ `config.php` legacy compatible con PHP 7  
✔ Fix `user_id` realizado  
✔ Fix lógica `sync_sap` realizado  
⏸ `moves_log` congelado para más adelante  
⏸ pruebas reales de escaneo pendientes por no estar en planta  

SIGUIENTE PASO:
→ Probar flujo básico con lectura/entrada cuando sea posible  
→ Mientras tanto, continuar integración controlada sin tocar lógica crítica

---

# 🎯 OBJETIVO

Construir una plataforma modular única llamada `easySeri` que unifique aplicaciones internas existentes en un solo sistema, con:

- Control de usuarios
- Sistema de roles
- Sistema de permisos
- Módulos independientes
- Activación/desactivación dinámica
- Menú dinámico
- Core común sin lógica de negocio

---

# 🧱 ARQUITECTURA BASE

- Core central sin lógica de negocio
- Módulos independientes en `/modules`
- Base de datos común para core
- Sistema de permisos por roles
- Menú dinámico
- Routing centralizado
- Layout común
- `.env` separado del repositorio

---

# 📊 ESTADO GLOBAL DEL PROYECTO easySeri

## ✅ FASE 0 — Base

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
- [x] `ModuleManager`
- [x] Lectura de módulos desde `/modules`
- [x] Sincronización con BD
- [x] Activación/desactivación de módulos

---

## ✅ FASE 2 — Sistema de permisos

- [x] Tabla `core_users`
- [x] Tabla `core_roles`
- [x] Tabla `core_permissions`
- [x] Tabla `core_user_roles`
- [x] Tabla `core_role_permissions`
- [x] Auth funcional
- [x] `PermissionService`
- [x] Control de acceso por módulo

---

## ✅ FASE 3 — Router y layout

- [x] Router funcional
- [x] Soporte `basePath`
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

## ✅ FASE 6 — Core administrativo

- [x] Login
- [x] Logout
- [x] Sesión PHP
- [x] Password hash bcrypt
- [x] Protección de rutas
- [x] Módulo `admin-usuarios`
- [x] Módulo `admin-roles`
- [x] Módulo `admin-modulos`
- [x] Activar/desactivar usuarios
- [x] Asignar roles
- [x] Asignar permisos
- [x] Activar/desactivar módulos

---

# 🚀 FASE 7 — MÓDULO REAL: `camaras-ubicacion`

---

# 📘 DOCUMENTO MAESTRO — MÓDULO CAMARAS-UBICACION

## 🎯 Objetivo del módulo

Sistema real de ubicación de palets en cámaras frigoríficas con:

- Escaneo de palets
- Uso con lector de códigos / cámara
- Integración con ERP/SAP mediante datos espejo
- Ubicación en cámaras
- Gestión de filas y posiciones
- Movimientos de palets
- Movimientos de entradas
- Informes
- Control operativo para almacén

---

## 🧠 Arquitectura funcional

### Flujo principal

ESCANEO  
→ lookup en ERP mirror  
→ identificación de palet  
→ identificación de entrada  
→ cálculo de pendientes  
→ sugerencia de ubicación  
→ confirmación  
→ inserción en `placements`  
→ trazabilidad / movimientos  

---

### Flujo SAP

SAP / SQL Server local  
→ `sync_sap.php`  
→ MariaDB  
→ tablas mirror  
→ módulo `camaras-ubicacion` en easySeri  

⚠️ easySeri NO debe conectarse directamente a SAP.

Motivo:

- El servidor SAP no tiene acceso desde internet.
- La app debe trabajar contra datos locales sincronizados.
- El sistema debe seguir funcionando aunque SAP no esté disponible temporalmente.

---

# 🗃️ Base de datos actual cámaras

## Configuración física

- `cameras`
- `camera_conditions`
- `camera_positions`
- `camera_row_groups`
- `camera_row_cells`

## Ubicación real

- `placements`
- `moves_log`

## ERP mirror

- `erp_entradas_mirror`
- `erp_palets_mirror`
- `erp_plegados_mirror`
- `erp_entries_pending`
- `erp_entries_suppressed`

## Tablas antiguas/locales

- `users`
- `entries`
- `pallets`

⚠️ La tabla `users` de la app antigua NO debe usarse en easySeri.  
Debe sustituirse por `core_users` + `Auth`.

---

# 🧩 Estructura actual del módulo easySeri

Ruta:

`modules/camaras-ubicacion/`

Estructura actual:

```txt
modules/camaras-ubicacion/
├── module.json
├── menu.php
├── index.php
├── scan.php
├── includes/
│   ├── auth.php
│   ├── config.php
│   ├── db.php
│   ├── footer.php
│   ├── header.php
│   └── helpers.php
└── legacy/
    ├── scan.php
    ├── api/
    │   ├── pallet_status.php
    │   ├── entry_counts.php
    │   ├── cameras.php
    │   ├── camera_rows.php
    │   ├── scan_confirm.php
    │   └── move_confirm.php
    └── assets/
        └── css/
             └── custom.css
🔐 Permisos del módulo

Permiso principal ya usado:

camaras-ubicacion.access

Permisos previstos:

camaras-ubicacion.scan
camaras-ubicacion.move
camaras-ubicacion.admin
camaras-ubicacion.reports

Estado actual:

 camaras-ubicacion.access
 permisos internos finos pendientes
⚠️ Problemas detectados y estado
✔ 1. user_id incorrecto — SOLUCIONADO

Problema detectado:

La app antigua usaba dos formas distintas:

$_SESSION['user_id']
$_SESSION['user']['id']

Riesgo:

placements.placed_by podía quedar NULL
moves_log.moved_by podía quedar NULL

Solución aplicada:

Adaptación de current_user_id()
Integración temporal con Auth::userId()

Estado:

 Solucionado
✔ 2. sync_sap.php marcaba entradas completas demasiado pronto — SOLUCIONADO

Problema detectado:

La lógica anterior podía hacer:

si existe 1 placement activo de la entrada
→ entrada completa

Esto era peligroso porque una entrada parcialmente ubicada podía quedar como completa.

Lógica corregida:

placed_count = COUNT placements activos
remaining_count = total_palets_erp - placed_count

Compatible con enum real de erp_entries_pending.status:

pending
complete
stale

⚠️ No existe partial.

Criterio actual:

Parcial = pending
Completa = complete

Estado:

 Corregido en código
 Pendiente validar físicamente en planta
⏸ 3. moves_log inconsistente — CONGELADO

Problema detectado:

BD actual permite:

move_row
move_entry
move_pallet

Pero algunos códigos pueden intentar usar otros valores como:

scan_case1
scan_case2
plegado

Decisión actual:

NO tocar todavía.

Motivo:

Riesgo de romper últimas actualizaciones
Necesita revisión completa de todos los inserts reales
Mejor aplazar hasta tener integración mínima estable

Estado:

 Pendiente futuro
 Congelado por decisión
✔ 4. Credenciales en código — SOLUCIONADO EN LEGACY INTEGRADO

Problema detectado:

includes/config.php tenía credenciales directas.

Solución aplicada en módulo integrado:

modules/camaras-ubicacion/includes/config.php lee desde .env
.env no se sube al repo
.env.example documenta variables necesarias

Variables añadidas:

CAMARAS_DB_HOST=localhost
CAMARAS_DB_USER=usuario_camaras
CAMARAS_DB_PASS=password_camaras
CAMARAS_DB_NAME=ubicacion

Estado:

 Solucionado en integración actual
⚠️ 5. Endpoints duplicados / incoherentes — PENDIENTE

Detectados:

plegado_confirm.php
plegado_place.php

Estado:

 Pendiente revisar
 No tocar todavía
⚠️ 6. Archivos inseguros / antiguos — PENDIENTE

Detectados en app antigua:

*_old.php
*_mock.php
tools/*
_selftest_delete.php

Estado:

 No migrar sin revisar
 Proteger o eliminar más adelante
🛠️ Plan real camaras-ubicacion
✅ FASE 0 — Auditoría
 Revisar SQL
 Revisar estructura de app antigua
 Revisar sync_sap.php
 Identificar tablas principales
 Identificar endpoints principales
 Detectar riesgos críticos
✅ FASE 1 — Saneamiento previo
 Corregir user_id
 Corregir lógica de pendientes en sync_sap
 Sacar credenciales del legacy integrado
 Dejar moves_log congelado para más adelante
 Validar físicamente sync en planta
✅ FASE 2 — Crear módulo en easySeri
 Crear carpeta modules/camaras-ubicacion
 Crear module.json
 Crear menu.php
 Crear index.php
 Añadir ruta /camaras-ubicacion
 Activar módulo desde admin-modulos
 Asignar permiso camaras-ubicacion.access
 Ver módulo en menú
 Cargar pantalla base
🟡 FASE 3 — Integración mínima legacy scan
 Crear modules/camaras-ubicacion/scan.php
 Añadir ruta /camaras-ubicacion/scan
 Crear iframe hacia legacy scan
 Copiar legacy/scan.php
 Copiar includes necesarios
 Copiar endpoints mínimos usados por scan
 Copiar CSS necesario
 Adaptar auth legacy a easySeri
 Adaptar header legacy a usuario easySeri
 Adaptar config legacy a .env
 Corregir compatibilidad PHP 7 (str_starts_with)
 Ver cámara cargando
 Añadir botón desde pantalla principal del módulo a escaneo
 Probar lectura real de palet
 Probar respuesta de endpoints con datos reales
 Probar confirmación de ubicación
🟡 FASE 4 — APIs legacy

Estado actual:

Copiadas mínimas para scan:

 pallet_status.php
 entry_counts.php
 cameras.php
 camera_rows.php
 scan_confirm.php
 move_confirm.php

Pendiente:

 Revisar cada endpoint contra BD real
 Revisar requires/includes
 Revisar permisos internos
 Revisar respuestas JSON
 Revisar errores PHP
 Revisar rutas relativas
 Revisar logs
🟡 FASE 5 — Movimientos

Pendiente:

 Revisar move_confirm.php
 Revisar move_entry.php
 Revisar move_pallets.php
 Revisar movimiento de fila
 Revisar moves_log
 Decidir si ampliar enum de moves_log
 No tocar hasta tener pruebas mínimas
🟡 FASE 6 — Admin cámaras

Pendiente:

 Migrar listado de cámaras
 Migrar editor de cámaras
 Migrar filas
 Migrar posiciones
 Adaptar permisos admin
 Adaptar layout easySeri
🟡 FASE 7 — Informes

Pendiente:

 Revisar informes actuales
 Migrar reports
 Adaptar export CSV
 Añadir permisos camaras-ubicacion.reports
🟡 FASE 8 — Sync SAP

Estado:

 Mantener externo
 No conectar easySeri directamente a SAP
 Lógica de pendientes corregida
 Validar ejecución real
 Documentar cron/tarea programada
 Documentar servidor donde corre
 Documentar logs
 Documentar recuperación si falla
🟡 FASE 9 — Pruebas reales en planta

Pendiente porque actualmente no se está físicamente en planta.

Pruebas necesarias:

 Abrir módulo desde PC de planta
 Abrir /easyseri/camaras-ubicacion/scan
 Validar cámara/lector
 Escanear palet existente
 Validar entrada detectada
 Validar pendientes
 Validar cámaras
 Validar filas
 Confirmar ubicación
 Revisar placements
 Revisar erp_entries_pending
 Probar entrada parcial
 Probar entrada completa
 Probar palet ya ubicado
 Probar movimiento
 Probar salida/volcado desde sync
📌 Decisiones importantes tomadas
 No reescribir la app desde cero
 Integrar progresivamente
 Mantener tablas actuales de cámaras
 Mantener sync_sap.php externo
 No conectar easySeri directamente a SAP
 No tocar moves_log todavía
 Primero estabilidad, luego refactor
 Usar iframe temporal para integrar scan sin romper lógica
 Sacar credenciales del repo
 Documentar siempre antes de avanzar
📏 Normas del proyecto
Seguridad
Nunca subir .env
Nunca subir credenciales
Nunca subir dumps SQL reales
Nunca dejar herramientas de test accesibles en producción
Todo dato sensible fuera del repo
Forma de trabajo
No suponer estructuras
Revisar archivo real antes de indicar cambios
Indicar ruta exacta
Indicar bloque exacto a sustituir
No hacer refactors grandes sin necesidad
Validar por fases
Documentar cambios importantes
Estructura de módulos

Todo módulo debe tener como mínimo:

module.json
menu.php
index.php

Cuando aplique:

rutas en router principal
permisos en core_permissions
vistas internas
endpoints protegidos
assets propios
Core
No contiene lógica de negocio
Solo orquesta:
rutas
auth
permisos
layout
módulos
Módulos
Deben ser independientes
No deben romper otros módulos
No deben depender directamente de otros módulos
Deben usar servicios comunes del core cuando sea posible
🧠 Filosofía
Primero estructura
Luego funcionalidad
Primero estabilidad
Luego refactor
Modular siempre
Evitar dependencias ocultas
Código claro
Código comentado
Documentar el estado real, no el ideal
🧭 Notas

Este documento es la referencia principal del proyecto.

Debe actualizarse:

al completar cada fase
antes de iniciar una nueva fase
cuando se tome una decisión importante
cuando se detecte un riesgo
cuando se congele una tarea para más adelante