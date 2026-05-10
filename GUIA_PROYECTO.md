# 🧭 GUÍA DEL PROYECTO - easySeri

Última revisión: 2026-05-10

---

## 1. Norma principal de trabajo

En este proyecto no se debe suponer nada.

Antes de modificar código, base de datos, rutas, permisos, endpoints o lógica de negocio, se debe comprobar el estado real en:

- Archivos del repositorio GitHub.
- Código PHP real.
- JSON reales.
- SQL / estructura de base de datos real.
- Archivos legacy reales.
- Pruebas manuales o validaciones en planta.

Si algo no se puede comprobar, debe quedar marcado como **NO VERIFICADO**.

Reglas prácticas:

- No romper core.
- No reescribir legacy de golpe.
- Hacer cambios pequeños y seguros.
- Validar por fases.
- Documentar cada decisión importante.
- Separar claramente: hecho, pendiente, riesgo y siguiente paso.

---

## 2. Estado actual en vivo

**FASE ACTUAL:** FASE 4.2 — Mejorar editor visual de cámaras sin perder funcionalidad legacy.

### Estado real comprobado / documentado

- ✔ Core easySeri funcionando.
- ✔ Login / logout funcionando.
- ✔ Usuarios, roles, permisos y módulos funcionando.
- ✔ Módulo `camaras-ubicacion` creado.
- ✔ Ruta `/easyseri/camaras-ubicacion` funcionando.
- ✔ Ruta `/easyseri/camaras-ubicacion/scan` funcionando.
- ✔ Integración legacy funcionando técnicamente.
- ✔ Módulo común `admin-plantas` creado.
- ✔ Gestión dinámica de plantas creada.
- ✔ Asignación de plantas a usuarios creada.
- ✔ Selector de planta activa creado.
- ✔ `cameras.php` ya filtra cámaras por planta activa.
- ✔ Administración inicial de cámaras adaptada desde la app vieja.
- ✔ Alta dinámica de cámara crea `camera_positions` automáticamente.
- ✔ Editor de plano inicial permite pintar celdas, niveles, entrada y filas reales.
- ✔ Documento específico creado: `docs/DISENO_EDITOR_CAMARAS.md`.
- ⚠ El editor visual actual funciona como base, pero su diseño no es definitivo.
- ⚠ Pendiente mejorar estética, usabilidad, velocidad y validaciones.
- ⚠ Pendiente crear cámaras A2 reales.
- ⚠ Pendiente validar en planta el filtrado de cámaras por planta activa.
- ⚠ Pendiente validar escaneo real en planta A2.
- ⚠ Pendiente adaptar flujo específico de plegado individual.

---

## 3. Documentos de referencia

- `GUIA_PROYECTO.md`: guía viva general del proyecto.
- `docs/DISENO_EDITOR_CAMARAS.md`: diseño funcional/técnico del nuevo editor visual de cámaras.

---

## 4. Separación real de bases de datos

### Base de datos core easySeri

Nombre: `easyseri`.

Contiene tablas del core:

- `core_users`
- `core_roles`
- `core_permissions`
- `core_modules`
- `core_plants`
- `core_user_plants`

Modelo multi-planta definido:

```sql
core_plants
core_user_plants
core_users.default_plant_id
```

### Base de datos cámaras / legacy

Nombre: `ubicacion`.

Contiene tablas del módulo cámaras:

- `cameras`
- `placements`
- `camera_positions`
- `camera_row_groups`
- `camera_row_cells`
- `erp_plegados_mirror`
- `erp_palets_mirror`
- `erp_entradas_mirror`
- `erp_entries_pending`
- `moves_log`

---

## 5. Estado real verificado de cámaras

Tabla: `ubicacion.cameras`.

Columnas verificadas inicialmente:

- `id`
- `name`
- `code`
- `priority`
- `entry_row`
- `entry_col`
- `notes`

Cambio multi-planta aplicado:

- ✔ Se añade `plant_code` en `ubicacion.cameras` mediante migración.
- ✔ Las cámaras existentes deben quedar marcadas como `A1`.
- ✔ `modules/camaras-ubicacion/legacy/api/cameras.php` filtra por `plant_code` usando la planta activa del usuario.

Administración dinámica creada:

- `modules/camaras-ubicacion/camaras/index.php`
- `modules/camaras-ubicacion/camaras/crear.php`
- `modules/camaras-ubicacion/camaras/guardar.php`
- `modules/camaras-ubicacion/camaras/plano.php`

Cámaras actuales verificadas antes de crear A2:

| id | name | code | priority | Observación |
|---:|---|---|---:|---|
| 1 | Camara 4 descarga | Descarga4 | 10 | A1 actual |
| 2 | Camara 3 descarga | Descarga3 | 9 | A1 actual |
| 4 | Camara 1 descarga | Descarga1 | 7 | A1 actual |
| 3 | Camara 2 descarga | Descarga2 | 0 | A1 actual |
| 5 | Campa | CampaA1 | 0 | A1 actual |

Conclusión:

- ✔ Ahora mismo solo hay cámaras de A1 cargadas.
- ✔ Ya existe preparación para filtrar por planta activa.
- ✔ Ya existe administración inicial de cámaras desde easySeri.
- ⚠ El diseño visual todavía debe mejorarse.
- ⚠ No se puede validar el flujo completo en casa porque requiere prueba real de planta.

---

## 6. Estado real verificado de filas de cámara

Endpoint revisado:

- `modules/camaras-ubicacion/legacy/api/camera_rows.php`

Funcionamiento actual:

- Recibe `camera_id`.
- Busca grupos en `camera_row_groups` por `camera_id`.
- Calcula huecos libres usando `camera_row_cells`, `camera_positions` y `placements`.

Conclusión:

- ✔ Las filas dependen de la cámara seleccionada.
- ✔ Si las cámaras están bien separadas por `plant_code`, las filas quedan separadas indirectamente.
- ⚠ Aun así, más adelante habrá que validar en `scan_confirm.php` y `move_confirm.php` que la cámara destino pertenece a la planta activa, para evitar envíos manipulados o errores.

---

## 7. Estado real verificado de placements

Tabla: `ubicacion.placements`.

Dato importante:

- ✔ `source_type` existe.
- ✔ `source_type` permite `entrada` o `plegado`.

Conclusión:

- ✔ La tabla ya está preparada parcialmente para distinguir origen: campo/entrada frente a plegado.
- ❌ `placements` no tiene campo de planta.
- ✔ La planta puede deducirse por la cámara mediante `cameras.plant_code`.
- Recomendación: no duplicar planta en `placements` de momento salvo necesidad real.

---

## 8. Estado real verificado de plegados

Tabla: `ubicacion.erp_plegados_mirror`.

Valor real verificado:

- `SELECT DISTINCT almacen FROM erp_plegados_mirror` devuelve: `02`.

Conclusión:

- ✔ En este momento los plegados vienen con `almacen = 02`.
- ⚠ No está confirmado todavía si `02` significa A2 de forma oficial.
- Hipótesis razonable pendiente de confirmar: `02` podría corresponder a planta/almacén A2.
- Para la prueba A2 con plegados se debe validar que `almacen = 02` representa A2 antes de automatizar reglas definitivas.

---

## 9. Endpoints funcionales / modificados

### Lectura

- ✔ `pallet_status.php`
- ✔ `entry_counts.php`
- ✔ `camera_rows.php`
- ✔ `cameras.php` modificado para filtrar por planta activa.

### Escritura

- ✔ `scan_confirm.php`
- ✔ `move_confirm.php`

Nota importante:

- `entry_counts.php` está orientado a entradas de campo (`erp_palets_mirror`).
- Para plegados individuales, no se debe depender de `entrada_num` como si fuese campo.
- `cameras.php` requiere que exista `ubicacion.cameras.plant_code`.

---

## 10. Decisiones importantes tomadas

### Plantas / almacenes

- ✔ Se crea módulo común `admin-plantas`.
- ✔ Las plantas/almacenes de trabajo son dinámicas.
- ✔ Un usuario puede tener una o varias plantas asignadas.
- ✔ Debe existir una planta activa de trabajo.
- ✔ Cámaras debe consumir esa planta activa.
- ✔ Al crear cámaras nuevas, se debe seleccionar planta/almacén.
- ✔ No se debe hardcodear que todas las cámaras son A1.
- ✔ No se debe hardcodear que A2 equivale siempre a `almacen = 02` hasta validarlo en planta/ERP.

### Administración de cámaras

- ✔ La creación de cámaras debe ser dinámica como en la app vieja.
- ✔ No basta con un CRUD simple de nombre/código/planta.
- ✔ Al crear cámara se deben crear posiciones en `camera_positions`.
- ✔ El editor debe mantener pintura de celdas, niveles, entrada y filas reales.
- ✔ El diseño visual actual debe mejorarse antes de considerarlo definitivo.

### Base de datos legacy cámaras

- ❌ No usar `db()` del core en legacy.
- ✔ Usar `camaras_db()` siempre.

### Estrategia legacy

- ❌ No reescribir lógica legacy de golpe.
- ✔ Adaptar progresivamente.
- ✔ Mantener funcionalidad existente mientras se integra en easySeri.

---

## 11. Problemas detectados / pendientes

### 11.1 Datos inconsistentes de prueba

Estado: **PENDIENTE**.

Acción futura:

- Hacer reset controlado de datos.
- Limpiar `placements` de prueba.
- Limpiar estados incoherentes.
- Mantener estructura de tablas.
- No borrar nada sin copia previa y revisión de tablas reales.

### 11.2 Validación de selector/planta activa en cámaras

Estado: **PENDIENTE DE VALIDACIÓN EN PLANTA**.

Hecho:

- `PlantService` existe.
- Selector de planta activa existe.
- `cameras.php` usa planta activa.

Pendiente:

- Probar en planta con usuario asignado a A1.
- Probar en planta con usuario asignado a A2.
- Confirmar que con planta A1 aparecen cámaras A1.
- Confirmar que con planta A2 no aparecen cámaras A1.
- Crear cámaras A2 y confirmar que aparecen solo en A2.

### 11.3 Editor visual de cámaras

Estado: **BASE FUNCIONAL CREADA / PENDIENTE DE REDISEÑO UX**.

Necesidad:

- Mejorar estructura visual.
- Mejorar estética.
- Mejorar velocidad de edición.
- Mejorar claridad de herramientas.
- Añadir resumen de capacidad.
- Añadir validaciones de cámara incompleta.
- Preparar opción futura de duplicar cámara.

Documento específico:

- `docs/DISENO_EDITOR_CAMARAS.md`

### 11.4 Planta de cámaras

Estado: **PENDIENTE DE COMPLETAR CON A2**.

Hecho verificado:

- Todas las cámaras actuales son A1.
- `cameras.php` filtra por planta activa.

Acción futura recomendada:

- Crear cámaras A2 reales desde la administración.
- Definir códigos.
- Definir prioridades.
- Definir filas/posiciones si aplica.

### 11.5 Flujo plegado individual

Estado: **PENDIENTE**.

Hecho verificado:

- `erp_plegados_mirror` existe.
- `placements.source_type` admite `plegado`.
- El flujo actual de ubicación está más orientado a entrada de campo.

Acción futura recomendada:

- Crear/adaptar flujo de ubicación de plegado individual.
- Insertar `placements` con `source_type = 'plegado'`.
- Usar `entrada_num = ''` o revisar si conviene permitir `NULL` en el futuro.

---

## 12. Arquitectura actual

### Core easySeri

- Auth.
- Permisos.
- Router.
- Layout.
- Módulos dinámicos.
- Menú por permisos.
- `PlantService` para plantas permitidas y planta activa.

### Módulo plantas

Módulo: `admin-plantas`.

Responsabilidad:

- Gestión del catálogo de plantas.
- Alta de plantas.
- Edición de plantas.
- Activación/desactivación de plantas.
- Asignación de plantas a usuarios.
- Definición de planta por defecto del usuario.
- Selección de planta activa.

### Módulo cámaras

- Módulo `camaras-ubicacion` integrado.
- Pantalla legacy embebida en iframe.
- APIs legacy adaptadas.
- Administración dinámica inicial de cámaras.
- Base de datos independiente para cámaras.
- Usuario easySeri usado en operaciones adaptadas.
- `cameras.php` consume la planta activa definida por `PlantService`.
- Pendiente rediseño UX del editor visual.

---

## 13. Flujo real validado / pendiente

### Flujo administración de cámaras

```txt
/camaras-ubicacion/camaras
  ↓
/camaras-ubicacion/camaras/crear
  ↓
/camaras-ubicacion/camaras/guardar
  ↓
INSERT cameras
  ↓
INSERT camera_positions
  ↓
/camaras-ubicacion/camaras/plano?id=...
  ↓
pintar celdas / niveles / entrada / filas reales
```

Estado:

- ✔ Base funcional creada.
- ⚠ Pendiente mejorar diseño UX.
- ⚠ Pendiente probar a fondo con cámara pequeña.

### Flujo scan campo actual

```txt
scan.php
  ↓
pallet_status.php
  ↓
entry_counts.php
  ↓
cameras.php filtrado por planta activa
  ↓
camera_rows.php
  ↓
scan_confirm.php
  ↓
INSERT placements
```

Estado:

- ⚠ Pendiente validar en planta después del cambio de `cameras.php`.

### Flujo pendiente para plegado A2

```txt
usuario easySeri
  ↓
obtener plantas permitidas del usuario
  ↓
si solo tiene una planta: usarla como planta activa
  ↓
si tiene varias plantas: elegir planta activa
  ↓
escaneo de palet
  ↓
detectar source = plegado
  ↓
leer erp_plegados_mirror.almacen = 02
  ↓
confirmar equivalencia 02 = A2
  ↓
mostrar solo cámaras/filas de la planta activa
  ↓
confirmar ubicación individual
  ↓
INSERT placements con source_type = plegado
```

---

## 14. Siguiente fase

**FASE 4.2 — Rediseño UX del editor visual de cámaras**

Objetivo:

```txt
Convertir el editor funcional actual en una herramienta visual intuitiva, rápida y agradable para crear cámaras reales sin perder funcionalidad.
```

---

## 15. Tareas siguientes

### 15.1 Rediseñar editor visual

Prioridad: **ALTA**.

Pendiente:

- Cabecera limpia con cámara/planta.
- Panel lateral de herramientas.
- Plano central amplio.
- Resumen de capacidad.
- Validaciones visibles.
- Acciones agrupadas.
- Mejor visibilidad de grupos/fila real.
- Mejor estética general.

### 15.2 Validar alta dinámica

Prioridad: **ALTA**.

Pendiente:

- Crear cámara pequeña de prueba.
- Confirmar que se generan posiciones.
- Pintar celdas.
- Crear filas reales.
- Marcar entrada.
- Verificar datos en tablas.

### 15.3 Crear cámaras A2

Prioridad: **ALTA**.

Pendiente:

- Definir cámaras reales de A2.
- Definir códigos.
- Definir prioridades.
- Definir filas/posiciones si aplica.

### 15.4 Adaptar flujo plegado individual

Prioridad: **ALTA**.

Pendiente:

- Si `pallet_status.php` devuelve `mode = plegado`, no usar flujo de entrada completa.
- Mostrar datos de plegado.
- Permitir ubicar un único palet plegado.
- Insertar `source_type = plegado`.

---

## 16. Riesgos conocidos

### Riesgo 1 — Datos de prueba mezclados

Mitigación:

- Reset controlado.
- Copia previa.
- SQL documentado.

### Riesgo 2 — Legacy funcional pero no completamente integrado

Mitigación:

- Mantener iframe hasta validar en planta.
- Integrar progresivamente.

### Riesgo 3 — Mezcla futura de plantas A1/A2

Ahora no hay mezcla porque solo existen cámaras A1.

El riesgo aparecerá al crear cámaras A2.

Mitigación:

- Asociar cámaras a planta.
- Asociar usuarios a una o varias plantas.
- Trabajar siempre con planta activa.
- Filtrar cámaras/filas por planta activa.
- Validar también en `scan_confirm.php` y `move_confirm.php` que la cámara destino pertenece a la planta activa.

### Riesgo 4 — Mezcla de origen plegado/campo

Mitigación:

- Usar `source_type`.
- Validar mirrors reales antes de tocar código.

---

## 17. Filosofía del proyecto

```txt
Primero funcional.
Luego estable.
Luego limpio.
Luego refactor.
```

No se debe refactorizar algo crítico si todavía no está validado en planta.

---

## 18. Conclusión actual

El módulo `camaras-ubicacion`:

- ✔ Ya funciona.
- ✔ Ya se integra con easySeri.
- ✔ Ya carga la pantalla de escaneo.
- ✔ Ya consulta endpoints.
- ✔ Ya inserta ubicaciones para flujo de entrada.
- ✔ Ya mueve palets.
- ✔ Ya tiene `source_type` preparado para `entrada` y `plegado`.
- ✔ Ya tiene filtro de cámaras por planta activa en `cameras.php`.
- ✔ Ya tiene administración dinámica inicial de cámaras.
- ⚠ Falta rediseñar el editor visual para que sea realmente usable.

Ahora toca:

```txt
mejorar el editor visual de cámaras sin cambiar las tablas ni perder funcionalidad legacy.
```

---

## 19. Siguiente paso recomendado

Opción recomendada:

```txt
REDISEÑAR PLANO.PHP COMO EDITOR VISUAL INDUSTRIAL LIMPIO
```

Antes de seguir con escaneo A2:

1. Mejorar editor visual.
2. Probar cámara pequeña.
3. Validar tablas generadas.
4. Crear cámaras A2 reales.
5. Validar que `cameras.php` solo devuelve cámaras de la planta activa.
