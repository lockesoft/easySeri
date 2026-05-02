# 🧭 GUÍA DEL PROYECTO - easySeri

---

# 🧭 ESTADO ACTUAL EN VIVO

FASE ACTUAL: FASE 3 — INTEGRACIÓN FUNCIONAL DEL MÓDULO `camaras-ubicacion`

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

---

# 🔥 INTEGRACIÓN LEGACY VALIDADA

✔ Pantalla legacy `scan.php` cargando en iframe  
✔ Cámara funcionando correctamente  
✔ Auth legacy adaptado a easySeri  
✔ DB legacy adaptada a `.env`  
✔ Eliminado conflicto `db()` vs core  
✔ Uso de `camaras_db()` correcto  

---

# ✅ ENDPOINTS COMPLETAMENTE FUNCIONALES

## Lectura

✔ `pallet_status.php`  
✔ `entry_counts.php`  
✔ `cameras.php`  
✔ `camera_rows.php`  

## Escritura

✔ `scan_confirm.php`  
✔ `move_confirm.php`  

---

# 🧪 VALIDACIONES REALIZADAS

✔ Escaneo manual simulado  
✔ Detección de palet OK  
✔ Detección de entrada OK  
✔ Cálculo de pendientes OK  
✔ Listado de cámaras OK  
✔ Listado de filas OK  

✔ Inserción controlada (`limit=1`) OK  
✔ Inserción múltiple preparada  
✔ `placed_by` correcto (usuario easySeri)  

✔ Movimiento controlado (`max_move=1`) OK  
✔ Cierre correcto de placement anterior (`removed_at`)  
✔ Inserción nueva posición OK  
✔ Integridad de datos mantenida  

✔ Actualización de `erp_entries_pending` OK  

---

# ⚠️ DECISIONES IMPORTANTES TOMADAS

✔ ❌ NO usar `db()` del core en legacy  
✔ ✔ usar `camaras_db()` siempre  

✔ ❌ NO tocar `moves_log` en scan  
✔ ✔ permitir `moves_log` en move_confirm (tipos válidos existentes)

✔ ❌ NO reescribir lógica legacy  
✔ ✔ adaptar progresivamente  

✔ ❌ NO conectar easySeri a SAP  
✔ ✔ usar mirror (`sync_sap.php`)

---

# ⚠️ PROBLEMAS DETECTADOS (PENDIENTES)

## ⏸ Datos inconsistentes (esperado)

Debido a:

```txt
✔ pruebas manuales
✔ entorno no real
✔ inserciones de test


Acción futura:

reset controlado de datos

⏸ moves_log incompleto
scan no registra logs
move sí registra parcialmente

Pendiente:revisión completa estructura moves_log


⚠ endpoints legacy extra

Detectados:

plegados
tools
old endpoints
Estado:

NO integrados aún

🧱 ARQUITECTURA ACTUAL
Core
Auth
Permisos
Router
Layout
Módulo cámaras
Legacy embebido
APIs adaptadas
DB independiente
🧠 FLUJO REAL VALIDADO

SCAN

→ pallet_status
→ entry_counts
→ cameras
→ camera_rows

→ scan_confirm (INSERT placements)

→ move_confirm (UPDATE + INSERT)
📊 BASE DE DATOS
Tablas usadas activamente
placements ✔
cameras ✔
camera_positions ✔
camera_row_groups ✔
camera_row_cells ✔
erp_palets_mirror ✔
erp_entradas_mirror ✔
erp_entries_pending ✔
Parcial
moves_log ⚠
🛠️ SIGUIENTE FASE (FASE 4)

OBJETIVO:
ESTABILIZAR Y LIMPIAR

🔧 Tareas
1. Reset controlado de datos
limpiar placements
limpiar estados incoherentes
mantener estructura
2. Validación real en planta
escaneo real
lector código barras
flujo completo
tiempos reales
3. UI mejora
eliminar iframe (futuro)
integrar scan en layout
simplificar UX operario
4. Permisos finos
camaras.scan
camaras.move
camaras.admin
5. Revisión endpoints secundarios
plegados
informes
admin cámaras
📏 NORMAS DEL PROYECTO
No romper core
No suponer código
Siempre revisar archivo real
Cambios pequeños y seguros
Validar por fases
🧠 FILOSOFÍA

✔ Primero funcional
✔ Luego estable
✔ Luego limpio
✔ Luego refactor

🧭 NOTAS

Sistema ya funcional a nivel técnico.

Falta:validación real en planta

🚀 CONCLUSIÓN

El módulo camaras-ubicacion:✔ YA FUNCIONA
✔ YA INSERTA
✔ YA MUEVE
✔ YA SE INTEGRA CON easySeri

Ahora toca:hacerlo robusto y usable en entorno real


---

# 🔥 Siguiente paso (te lo dejo claro)

Ahora tienes 2 caminos:

## Opción A (recomendada)

```txt
RESET CONTROLADO + PRUEBA EN PLANTA

Opción B seguir integrando (admin cámaras / UI / permisos)