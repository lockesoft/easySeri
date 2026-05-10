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

**FASE ACTUAL:** FASE 4.0 — Crear base multi-planta común antes de prueba A2.

### Estado real comprobado / documentado

- ✔ Core easySeri funcionando.
- ✔ Login / logout funcionando.
- ✔ Usuarios, roles, permisos y módulos funcionando.
- ✔ Módulo `camaras-ubicacion` creado.
- ✔ Módulo visible en `admin-modulos`.
- ✔ Módulo visible en menú con permiso `camaras-ubicacion.access`.
- ✔ Ruta `/easyseri/camaras-ubicacion` funcionando.
- ✔ Ruta `/easyseri/camaras-ubicacion/scan` funcionando.
- ✔ Botón desde módulo hacia escaneo funcionando.
- ✔ Integración legacy funcionando técnicamente.
- ✔ Módulo común `admin-plantas` creado.
- ✔ Gestión dinámica de plantas creada.
- ✔ Asignación de plantas a usuarios creada.
- ✔ Selector de planta activa creado.
- ✔ `cameras.php` ya filtra cámaras por planta activa.
- ⚠ Pendiente validar en planta el filtrado de cámaras por planta activa.
- ⚠ Pendiente validar escaneo real en planta A2.
- ⚠ Pendiente adaptar flujo específico de plegado individual.

---

## 3. Separación real de bases de datos

### Base de datos core easySeri

Nombre: `easyseri`.

Contiene tablas del core:

- `core_users`
- `core_roles`
- `core_permissions`
- `core_modules`
- `core_plants`
- `core_user_plants`

Resultado verificado:

- `core_users` existe en `easyseri`.
- `core_users` no existe en `ubicacion`.
- Usuarios actuales verificados:
  - Pablo
  - Fabiola
  - Josep
  - Manolo
  - Vicente Taberner

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
- `erp_plegados_mirror`
- `erp_palets_mirror`
- `erp_entradas_mirror`
- `erp_entries_pending`
- `moves_log`

---

## 4. Estado real verificado de cámaras

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
- ⚠ No se puede validar en casa porque requiere flujo real de escaneo/cámaras.
- ⚠ Queda pendiente validarlo en planta.

---

## 5. Estado real verificado de placements

Tabla: `ubicacion.placements`.

Columnas verificadas:

- `id`
- `camera_id`
- `row_idx`
- `col_idx`
- `level_idx`
- `entrada_num`
- `source_type`
- `pallet_num`
- `placed_at`
- `placed_by`
- `removed_at`
- `removed_source`
- `created_at`
- `updated_at`

Dato importante:

- ✔ `source_type` existe.
- ✔ `source_type` permite `entrada` o `plegado`.

Conclusión:

- ✔ La tabla ya está preparada parcialmente para distinguir origen: campo/entrada frente a plegado.
- ❌ `placements` no tiene campo de planta.
- ✔ La planta puede deducirse por la cámara mediante `cameras.plant_code`.
- Recomendación: no duplicar planta en `placements` de momento salvo necesidad real.

---

## 6. Estado real verificado de plegados

Tabla: `ubicacion.erp_plegados_mirror`.

Columnas verificadas:

- `pallet_num`
- `tipo`
- `variedad`
- `calibres1`
- `kg_reales`
- `cajones`
- `fecha`
- `almacen`
- `comentario`
- `numero_volcador`
- `src_updated_at`
- `synced_at`

Valor real verificado:

- `SELECT DISTINCT almacen FROM erp_plegados_mirror` devuelve: `02`.

Conclusión:

- ✔ En este momento los plegados vienen con `almacen = 02`.
- ⚠ No está confirmado todavía si `02` significa A2 de forma oficial.
- Hipótesis razonable pendiente de confirmar: `02` podría corresponder a planta/almacén A2.
- Para la prueba A2 con plegados se debe validar que `almacen = 02` representa A2 antes de automatizar reglas definitivas.

---

## 7. Estado real verificado de entradas de campo

Tabla: `ubicacion.erp_entradas_mirror`.

Columnas verificadas:

- `entrada_num`
- `boleto_id`
- `boleto_cod`
- `almacen_nombre`
- `propietario`
- `conductor`
- `matricula`
- `kg_reales`
- `fecha_boleto`
- `synced_at`
- `src_updated_at`

Valor real verificado:

- `SELECT DISTINCT almacen_nombre FROM erp_entradas_mirror` devuelve: `ALMACEN A1`.

Conclusión:

- ✔ En este momento las entradas de campo visibles en mirror corresponden a `ALMACEN A1`.
- ✔ Esto confirma que la parte de campo venía trabajando con A1.
- ⚠ Para A2/campo hará falta comprobar qué valor real aparece cuando existan entradas de campo A2.

---

## 8. Estado real verificado de moves_log

Tabla: `ubicacion.moves_log`.

Columnas verificadas:

- `id`
- `moved_at`
- `moved_by`
- `type`
- `src_camera_id`
- `src_row_group_id`
- `dest_camera_id`
- `dest_row_group_id`
- `entrada_num`
- `pallets_count`
- `notes`

Tipos válidos:

- `move_row`
- `move_entry`
- `move_pallet`

Conclusión:

- ✔ `move_confirm.php` puede registrar movimientos usando tipos existentes.
- ❌ `scan_confirm.php` no debe registrar todavía `scan_case1`/`scan_case2` porque esos tipos no existen.
- ⚠ Si se quiere auditar ubicaciones iniciales, habrá que ampliar el enum o crear otra tabla de auditoría.

---

## 9. Integración legacy validada

- ✔ Pantalla legacy `scan.php` cargando en iframe.
- ✔ Cámara funcionando correctamente.
- ✔ Auth legacy adaptado a easySeri.
- ✔ DB legacy adaptada a `.env`.
- ✔ Eliminado conflicto `db()` vs core.
- ✔ Uso de `camaras_db()` correcto.

Decisión importante:

- ❌ No usar `db()` del core dentro del legacy de cámaras.
- ✔ Usar `camaras_db()` siempre en legacy cámaras.

---

## 10. Endpoints funcionales / modificados

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

## 11. Decisiones importantes tomadas

### Base de datos legacy cámaras

- ❌ No usar `db()` del core en legacy.
- ✔ Usar `camaras_db()` siempre.

### Logs de movimientos

- ❌ No tocar `moves_log` en `scan_confirm.php` por ahora.
- ✔ Permitir `moves_log` en `move_confirm.php` usando solo tipos válidos existentes.
- ⚠ Pendiente revisar estructura completa de `moves_log` antes de ampliar logs.

### Estrategia legacy

- ❌ No reescribir lógica legacy de golpe.
- ✔ Adaptar progresivamente.
- ✔ Mantener funcionalidad existente mientras se integra en easySeri.

### Integración con SAP / ERP

- ❌ No conectar easySeri directamente a SAP.
- ✔ Usar mirror mediante `sync_sap.php`.

### Plantas A1 / A2

- ✔ Ahora mismo solo hay cámaras A1 en `cameras`.
- ✔ La prueba real actual se realizará en planta A2.
- ✔ En planta A2 ahora solo hay palets de plegado para probar.
- ✔ El diseño debe contemplar que los palets puedan pertenecer o ubicarse en A1 o A2.
- ✔ Un usuario puede tener acceso a una sola planta.
- ✔ Un usuario puede tener acceso a varias plantas.
- ✔ No se debe modelar el acceso de usuario a planta únicamente con un campo simple obligatorio.
- ✔ Debe existir una planta activa de trabajo para la pantalla de escaneo.
- ✔ Si el usuario solo tiene una planta, la planta activa se seleccionará automáticamente.
- ✔ Si el usuario tiene varias plantas, deberá poder elegir planta activa o tener una planta por defecto.

### Módulo común de plantas

- ✔ Se decide crear un módulo propio `admin-plantas`.
- ✔ El módulo permite crear plantas de trabajo de forma dinámica.
- ✔ El módulo permite editar plantas.
- ✔ El módulo permite activar/desactivar plantas.
- ✔ El módulo permite asignar una o varias plantas a cada usuario.
- ✔ El módulo permite modificar las plantas asignadas a cada usuario.
- ✔ El módulo permite seleccionar planta activa.
- ✔ Esta gestión será común para todo easySeri, no exclusiva de cámaras.

### Tipos de palets / origen operativo

- ✔ En la prueba actual de planta solo hay palets de plegado.
- ✔ A futuro el sistema debe soportar tanto palets de plegado como palets de campo.
- ✔ Ambos tipos podrán existir en A1 o en A2.
- ✔ No se debe asumir que un palet pertenece a A1 por defecto.
- ✔ No se debe asumir que todos los palets son de campo.
- ✔ No se debe asumir que todos los palets son de plegado.

---

## 12. Problemas detectados / pendientes

### 12.1 Datos inconsistentes de prueba

Estado: **PENDIENTE**.

Acción futura:

- Hacer reset controlado de datos.
- Limpiar `placements` de prueba.
- Limpiar estados incoherentes.
- Mantener estructura de tablas.
- No borrar nada sin copia previa y revisión de tablas reales.

### 12.2 Validación de selector/planta activa en cámaras

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

Motivo de pendiente:

- El usuario está en casa y no puede hacer escaneo/prueba real de planta en este momento.

### 12.3 Planta de cámaras

Estado: **PENDIENTE DE COMPLETAR CON A2**.

Hecho verificado:

- Todas las cámaras actuales son A1.
- `cameras.php` filtra por planta activa.

Acción futura recomendada:

- Crear cámaras A2 reales.
- Definir códigos.
- Definir prioridades.
- Definir filas/posiciones si aplica.

### 12.4 Flujo plegado individual

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

## 13. Arquitectura actual

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

Este módulo forma parte del core funcional común de easySeri.

### Módulo cámaras

- Módulo `camaras-ubicacion` integrado.
- Pantalla legacy embebida en iframe.
- APIs legacy adaptadas.
- Base de datos independiente para cámaras.
- Usuario easySeri usado en operaciones adaptadas.
- `cameras.php` consume la planta activa definida por `PlantService`.

### Requisito multi-planta

El sistema debe evolucionar para trabajar correctamente con:

- Plantas dinámicas, no hardcodeadas exclusivamente a A1/A2.
- Usuarios asociados a una o varias plantas.
- Planta activa de trabajo.
- Palets de plegado por planta.
- Palets de campo por planta.
- Cámaras asociadas a planta.

---

## 14. Flujo real validado / pendiente

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

### Flujo move actual

```txt
scan.php / flujo movimiento
  ↓
move_confirm.php
  ↓
UPDATE placement anterior con removed_at
  ↓
INSERT nueva posición
```

Estado:

- ⚠ Pendiente validar que el destino no permita mezcla de plantas cuando existan cámaras A2.

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

## 15. Siguiente fase

**FASE 4.1 — Validar planta activa en cámaras y preparar flujo plegado individual**

Objetivo:

```txt
Validar que cámaras respeta planta activa y preparar el flujo específico de plegados para A2.
```

---

## 16. Tareas siguientes

### 16.1 Validar en planta filtrado por planta activa

Prioridad: **ALTA**.

Pendiente:

- Subir `cameras.php` modificado.
- Confirmar que existe `core/plants/PlantService.php` en servidor.
- Confirmar que existe `ubicacion.cameras.plant_code`.
- Seleccionar A1 y comprobar que aparecen cámaras A1.
- Seleccionar A2 y comprobar que no aparecen cámaras A1.
- Crear cámaras A2 y comprobar que aparecen solo con A2.

### 16.2 Crear cámaras A2

Prioridad: **ALTA**.

Pendiente:

- Definir cámaras reales de A2.
- Definir códigos.
- Definir prioridades.
- Definir filas/posiciones si aplica.

### 16.3 Adaptar flujo plegado individual

Prioridad: **ALTA**.

Pendiente:

- Si `pallet_status.php` devuelve `mode = plegado`, no usar flujo de entrada completa.
- Mostrar datos de plegado.
- Permitir ubicar un único palet plegado.
- Insertar `source_type = plegado`.

### 16.4 Validación real en A2

Prioridad: **ALTA**.

Pendiente:

- Escaneo real de palet plegado.
- Ubicación en cámara/fila A2.
- Movimiento real entre posiciones A2.
- Confirmar que A1 no se ve ni se usa desde usuario solo A2.
- Confirmar que usuarios multi-planta pueden elegir A1/A2 correctamente.

---

## 17. Riesgos conocidos

### Riesgo 1 — Datos de prueba mezclados

Mitigación:

- Reset controlado.
- Copia previa.
- SQL documentado.

### Riesgo 2 — Legacy funcional pero no completamente integrado

Mitigación:

- Mantener iframe hasta validar en planta.
- Integrar progresivamente.

### Riesgo 3 — Logs incompletos

Mitigación:

- Revisar `moves_log` antes de ampliar.

### Riesgo 4 — Permisos demasiado generales

Mitigación:

- Añadir permisos finos.

### Riesgo 5 — Mezcla futura de plantas A1/A2

Ahora no hay mezcla porque solo existen cámaras A1.

El riesgo aparecerá al crear cámaras A2.

Mitigación:

- Asociar cámaras a planta.
- Asociar usuarios a una o varias plantas.
- Trabajar siempre con planta activa.
- Filtrar cámaras/filas por planta activa.
- Validar también en `scan_confirm.php` y `move_confirm.php` que la cámara destino pertenece a la planta activa.

### Riesgo 6 — Mezcla de origen plegado/campo

Mitigación:

- Usar `source_type`.
- Validar mirrors reales antes de tocar código.

---

## 18. Filosofía del proyecto

```txt
Primero funcional.
Luego estable.
Luego limpio.
Luego refactor.
```

No se debe refactorizar algo crítico si todavía no está validado en planta.

---

## 19. Conclusión actual

El módulo `camaras-ubicacion`:

- ✔ Ya funciona.
- ✔ Ya se integra con easySeri.
- ✔ Ya carga la pantalla de escaneo.
- ✔ Ya consulta endpoints.
- ✔ Ya inserta ubicaciones para flujo de entrada.
- ✔ Ya mueve palets.
- ✔ Ya tiene `source_type` preparado para `entrada` y `plegado`.
- ✔ Ya tiene filtro de cámaras por planta activa en `cameras.php`.

Ahora toca:

```txt
validar en planta el filtro por planta activa, crear cámaras A2 y preparar el flujo específico de plegado individual.
```

---

## 20. Siguiente paso recomendado

Opción recomendada:

```txt
DETENER CAMBIOS EN CÁMARAS HASTA VALIDAR EN PLANTA EL FILTRO POR PLANTA ACTIVA
```

Cuando se vuelva a planta:

1. Probar selector de planta activa.
2. Probar escaneo con planta A1.
3. Confirmar listado de cámaras A1.
4. Seleccionar A2.
5. Confirmar que no aparecen cámaras A1.
6. Crear cámaras A2.
7. Probar plegado real en A2.
