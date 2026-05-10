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

**FASE ACTUAL:** FASE 4.4 — Preparar flujo de plegado individual para prueba A2.

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
- ✔ Administración dinámica de cámaras adaptada desde la app vieja.
- ✔ Alta dinámica de cámara crea `camera_positions` automáticamente.
- ✔ Editor de plano V2 creado con plano físico bajo y a ancho completo.
- ✔ Edición de datos generales de cámara creada.
- ✔ Duplicar cámara creado.
- ✔ Duplicar cámara copia estructura sin copiar ubicaciones ni ocupación.
- ✔ Prueba desde casa realizada: crear cámara pequeña funciona.
- ✔ Prueba desde casa realizada: editar plano funciona.
- ✔ Prueba desde casa realizada: duplicar cámara funciona.
- ✔ Prueba desde casa realizada: editar datos generales funciona.
- ✔ Protección multi-planta en confirmación de escaneo creada.
- ✔ Protección/corrección de movimientos multi-planta creada.
- ✔ Aviso visual creado para palets ubicados en otra planta.
- ✔ Documento específico creado: `docs/DISENO_EDITOR_CAMARAS.md`.
- ⚠ Pendiente crear cámaras A2 reales.
- ⚠ Pendiente validar en planta el filtrado de cámaras por planta activa.
- ⚠ Pendiente validar escaneo real en planta A2.
- ⚠ Pendiente adaptar flujo específico de plegado individual.

---

## 3. Documentos de referencia

- `GUIA_PROYECTO.md`: guía viva general del proyecto.
- `docs/DISENO_EDITOR_CAMARAS.md`: diseño funcional/técnico del editor visual de cámaras.

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

## 5. Archivos principales tocados / creados

### Administración de cámaras

```txt
modules/camaras-ubicacion/camaras/index.php
modules/camaras-ubicacion/camaras/crear.php
modules/camaras-ubicacion/camaras/guardar.php
modules/camaras-ubicacion/camaras/editar.php
modules/camaras-ubicacion/camaras/editar_guardar.php
modules/camaras-ubicacion/camaras/plano.php       respaldo
modules/camaras-ubicacion/camaras/plano_v2.php    activo
modules/camaras-ubicacion/camaras/duplicar.php
modules/camaras-ubicacion/camaras/duplicar_guardar.php
```

### Multi-planta / guards

```txt
modules/camaras-ubicacion/includes/plant_guard.php
modules/camaras-ubicacion/legacy/api/cameras.php
modules/camaras-ubicacion/legacy/api/scan_confirm.php
modules/camaras-ubicacion/legacy/api/move_confirm.php
```

### Aviso de traslado entre plantas

```txt
modules/camaras-ubicacion/legacy/api/pallet_status.php
modules/camaras-ubicacion/legacy/scan.php
```

### Rutas

```txt
index.php
```

---

## 6. Rutas actuales importantes

### Módulo cámaras

```txt
/easyseri/camaras-ubicacion
/easyseri/camaras-ubicacion/scan
```

### Administración de cámaras

```txt
/easyseri/camaras-ubicacion/camaras
/easyseri/camaras-ubicacion/camaras/crear
/easyseri/camaras-ubicacion/camaras/guardar
/easyseri/camaras-ubicacion/camaras/editar?id=...
/easyseri/camaras-ubicacion/camaras/editar/guardar
/easyseri/camaras-ubicacion/camaras/duplicar?id=...
/easyseri/camaras-ubicacion/camaras/duplicar/guardar
/easyseri/camaras-ubicacion/camaras/plano?id=...
```

### Plantas

```txt
/easyseri/admin-plantas
/easyseri/admin-plantas/crear
/easyseri/admin-plantas/editar?id=...
/easyseri/admin-plantas/usuarios
/easyseri/admin-plantas/seleccionar
```

---

## 7. Estado de administración dinámica de cámaras

### Crear cámara

Estado: **PROBADO Y FUNCIONA**.

Crea:

- Registro en `cameras`.
- Matriz inicial en `camera_positions`.
- Todas las celdas nacen como `almacenaje`.
- Todas las celdas nacen con `max_levels` indicado.
- Cámara asociada a `plant_code`.

Campos:

- Planta / almacén.
- Nombre.
- Código.
- Prioridad.
- Filas.
- Columnas.
- Niveles.
- Notas.

### Editor visual `plano_v2.php`

Estado: **PROBADO Y FUNCIONA**.

Permite:

- Pintar celdas como `almacenaje`, `pasillo`, `bloqueada`.
- Marcar punto de entrada.
- Cambiar niveles por celda.
- Seleccionar filas/columnas.
- Crear filas reales / grupos.
- Eliminar filas reales / grupos.
- Resaltar grupos.
- Ver capacidad, almacenaje, pasillos, bloqueadas y celdas en grupos.

Decisión visual tomada:

```txt
Los módulos de resumen/herramientas/filas reales quedan arriba.
El plano físico queda debajo, ocupando todo el ancho disponible.
El plano físico es la parte más importante de la pantalla.
```

### Editar cámara

Estado: **PROBADO Y FUNCIONA**.

Permite editar:

- Nombre.
- Código.
- Planta / almacén.
- Prioridad.
- Notas.

No toca:

- `camera_positions`.
- `camera_row_groups`.
- `camera_row_cells`.
- `placements`.

### Duplicar cámara

Estado: **PROBADO Y FUNCIONA**.

Copia:

- Datos generales básicos.
- `camera_positions`.
- Tipos de celda.
- Niveles por posición.
- `camera_row_groups`.
- `camera_row_cells` reconstruyendo referencias a las nuevas posiciones.
- Punto de entrada.

No copia:

- `placements`.
- Ocupación actual.
- Movimientos.
- Histórico.

Uso recomendado:

```txt
Duplicar cámara A1 → nueva cámara A2 → revisar plano → ajustar diferencias físicas → validar en planta.
```

---

## 8. Estado real de cámaras y A2

Cámaras actuales verificadas inicialmente antes de crear A2:

| id | name | code | priority | Observación |
|---:|---|---|---:|---|
| 1 | Camara 4 descarga | Descarga4 | 10 | A1 actual |
| 2 | Camara 3 descarga | Descarga3 | 9 | A1 actual |
| 4 | Camara 1 descarga | Descarga1 | 7 | A1 actual |
| 3 | Camara 2 descarga | Descarga2 | 0 | A1 actual |
| 5 | Campa | CampaA1 | 0 | A1 actual |

Conclusión:

- ✔ Actualmente solo se habían verificado cámaras A1 reales.
- ✔ Ya se puede crear o duplicar cámaras A2 desde easySeri.
- ⚠ Pendiente crear cámaras A2 reales definitivas.
- ⚠ Pendiente validar en planta que al seleccionar A2 no aparecen cámaras A1.

---

## 9. Estado multi-planta en escaneo / movimientos

### Listado de cámaras

`modules/camaras-ubicacion/legacy/api/cameras.php`:

- Usa `PlantService`.
- Lee planta activa del usuario.
- Devuelve solo cámaras con `cameras.plant_code = planta_activa`.

### Confirmación de ubicación nueva

`modules/camaras-ubicacion/legacy/api/scan_confirm.php`:

- Valida que `camera_id` destino pertenece a la planta activa.
- Valida que `row_group_id` pertenece a esa cámara.
- Evita insertar ubicaciones en cámaras de otra planta por JSON manipulado o error de frontend.

### Movimiento de palets

`modules/camaras-ubicacion/legacy/api/move_confirm.php`:

Regla final correcta:

```txt
Destino: siempre debe pertenecer a la planta activa.
Origen: puede venir de otra planta.
Mover fila completa: solo dentro de la planta activa.
```

Motivo:

- Puede ocurrir que fruta ubicada en A2 se traslade físicamente a A1.
- Al leerla en A1 debe poder reubicarse en A1.
- El sistema debe avisar, cerrar la ubicación anterior y crear la nueva en la planta activa.

---

## 10. Aviso de palet ubicado en otra planta

Estado: **CREADO / PENDIENTE DE VALIDACIÓN EN PLANTA**.

`pallet_status.php` ahora devuelve `place_context` cuando el palet tiene ubicación activa:

```json
{
  "place_context": {
    "camera_id": 1,
    "camera_name": "Camara 4 descarga",
    "camera_code": "Descarga4",
    "plant_code": "A2",
    "row_idx": 10,
    "col_idx": 3,
    "level_idx": 1,
    "row_group_id": 5,
    "row_label": "Fila 1",
    "row_count": 12,
    "active_plant_code": "A1",
    "same_as_active_plant": false
  }
}
```

`scan.php` muestra aviso si el palet está en una planta diferente a la activa:

```txt
⚠ Atención: este palet ya estaba ubicado en otra planta.
Ubicación actual: Planta A2 / Cámara X / Fila Y / F10-C3 · Nivel 1.
Si confirmas, se cerrará esa ubicación anterior y se reubicará en la planta activa A1.
```

También repite el aviso en la pantalla de confirmación.

---

## 11. Estado real verificado de plegados

Tabla: `ubicacion.erp_plegados_mirror`.

Valor real verificado por el usuario:

```sql
SELECT DISTINCT almacen FROM erp_plegados_mirror;
```

Resultado:

```txt
02
```

Conclusión:

- ✔ En este momento los plegados vienen con `almacen = 02`.
- ⚠ No está confirmado todavía si `02` significa A2 de forma oficial.
- Hipótesis razonable pendiente de confirmar: `02` podría corresponder a planta/almacén A2.
- Para la prueba A2 con plegados se debe validar que `almacen = 02` representa A2 antes de automatizar reglas definitivas.

---

## 12. Estado de flujo plegado individual

Estado: **PENDIENTE**.

Hecho:

- `erp_plegados_mirror` existe.
- `pallet_status.php` detecta `mode = plegado`.
- `placements.source_type` admite `plegado`.
- `move_confirm.php` ya contempla `source_type = plegado` en movimiento.

Pendiente importante:

- Cuando `pallet_status.php` devuelve `mode = plegado`, no debe tratarse como entrada completa.
- Debe ubicarse solo el palet plegado leído.
- Debe mostrarse información clara del plegado.
- Debe insertarse en `placements` con `source_type = 'plegado'`.
- Hay que revisar si `entrada_num` se deja como cadena vacía para plegado o si conviene permitir `NULL` en futura migración.

Este es el siguiente bloque de desarrollo recomendado.

---

## 13. Estado de endpoints

### Lectura

- ✔ `pallet_status.php` — detecta campo/plegado y devuelve contexto de ubicación.
- ✔ `entry_counts.php` — orientado a entradas de campo.
- ✔ `camera_rows.php` — filas por cámara.
- ✔ `cameras.php` — filtra por planta activa.

### Escritura

- ✔ `scan_confirm.php` — ubica entradas de campo y valida destino por planta activa.
- ✔ `move_confirm.php` — mueve palets con destino validado por planta activa y origen abierto para traslados entre plantas.

Pendiente:

- Adaptar `scan_confirm.php` o crear endpoint específico para ubicar plegado individual.

---

## 14. Pruebas realizadas desde casa

Realizadas y funcionando:

```txt
crear cámara pequeña
probar plano visual
probar duplicar cámara
probar editar datos generales
```

No validado todavía desde casa porque requiere planta/escaneo real:

```txt
selector A1/A2 en uso real de planta
escaneo físico con cámara del dispositivo
filtrado real de cámaras por planta activa
reubicación A2 → A1 con aviso
ubicación de plegados reales en A2
```

---

## 15. Pendientes para la próxima sesión

### 15.1 Adaptar flujo de plegado individual

Prioridad: **ALTA**.

Objetivo:

```txt
Poder escanear un palet de plegado en A2 y ubicar solo ese palet individual, sin tratarlo como entrada de campo.
```

Puntos a revisar al retomar:

- `modules/camaras-ubicacion/legacy/api/pallet_status.php`
- `modules/camaras-ubicacion/legacy/scan.php`
- `modules/camaras-ubicacion/legacy/api/scan_confirm.php`
- Posible endpoint nuevo: `scan_confirm_plegado.php` o ampliación segura de `scan_confirm.php`.

Reglas deseadas:

- Si `mode = plegado`, mostrar datos de `erp_plegados_mirror`.
- No llamar a `entry_counts.php` como si fuera campo.
- No requerir `entrada_num` real.
- Seleccionar cámara y fila de planta activa.
- Insertar una sola línea en `placements`.
- `source_type = 'plegado'`.
- `pallet_num = pallet_num` leído.
- `entrada_num` de momento podría ir como `''` si la columna sigue `NOT NULL`, salvo que se decida migración a `NULL`.

### 15.2 Crear cámaras A2 reales

Prioridad: **ALTA**.

Opciones:

- Crear desde cero con editor visual.
- Duplicar estructura A1 y ajustar diferencias.

Pendiente decidir:

- Nombres reales.
- Códigos reales.
- Prioridad.
- Filas/columnas/niveles.
- Pasillos/bloqueadas.
- Filas reales.

### 15.3 Validar en planta

Prioridad: **ALTA**.

Checklist en planta:

```txt
1. Seleccionar planta A1.
2. Confirmar que aparecen cámaras A1.
3. Seleccionar planta A2.
4. Confirmar que no aparecen cámaras A1.
5. Crear o duplicar cámara A2 real.
6. Escanear plegado real en A2.
7. Confirmar que muestra datos de plegado.
8. Confirmar que permite ubicar solo ese palet.
9. Probar traslado desde otra planta y verificar aviso.
10. Confirmar que no se mezclan destinos entre A1/A2.
```

---

## 16. Archivos a subir si no están subidos todavía

Últimos cambios relevantes:

```txt
GUIA_PROYECTO.md
modules/camaras-ubicacion/legacy/api/pallet_status.php
modules/camaras-ubicacion/legacy/scan.php
modules/camaras-ubicacion/legacy/api/move_confirm.php
```

Si no se subieron antes también:

```txt
modules/camaras-ubicacion/includes/plant_guard.php
modules/camaras-ubicacion/legacy/api/scan_confirm.php
modules/camaras-ubicacion/camaras/editar.php
modules/camaras-ubicacion/camaras/editar_guardar.php
modules/camaras-ubicacion/camaras/duplicar.php
modules/camaras-ubicacion/camaras/duplicar_guardar.php
modules/camaras-ubicacion/camaras/plano_v2.php
modules/camaras-ubicacion/camaras/index.php
index.php
```

---

## 17. Riesgos conocidos

### Riesgo 1 — Datos de prueba mezclados

Mitigación:

- Reset controlado.
- Copia previa.
- SQL documentado.

### Riesgo 2 — A2 todavía no validado físicamente

Mitigación:

- No dar por hecho equivalencias.
- Validar `almacen = 02` antes de automatizar reglas definitivas.
- Crear cámaras A2 reales y probar con palets reales.

### Riesgo 3 — Mezcla de plantas

Mitigación actual:

- Cámara destino siempre validada contra planta activa.
- Listado de cámaras filtrado por planta activa.
- Aviso visual si un palet viene ubicado desde otra planta.

### Riesgo 4 — Plegado tratado como campo

Mitigación pendiente:

- Adaptar flujo específico de plegado individual.

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

- ✔ Ya funciona dentro de easySeri.
- ✔ Ya tiene administración dinámica de cámaras.
- ✔ Ya tiene editor visual V2.
- ✔ Ya permite crear, editar, duplicar y configurar planos.
- ✔ Esas funciones administrativas ya fueron probadas desde casa y funcionan.
- ✔ Ya tiene base multi-planta.
- ✔ Ya filtra cámaras por planta activa.
- ✔ Ya valida destino por planta activa.
- ✔ Ya permite reubicar desde otra planta avisando.
- ⚠ Falta adaptar el flujo de plegado individual.
- ⚠ Falta crear/probar cámaras A2 reales.
- ⚠ Falta validar todo en planta.

---

## 20. Siguiente paso recomendado

Opción recomendada para la próxima sesión:

```txt
ADAPTAR FLUJO DE PLEGADO INDIVIDUAL
```

Motivo:

```txt
La prueba en planta A2 ahora mismo será con plegados.
Si no adaptamos este flujo, el sistema puede intentar tratar un plegado como entrada de campo.
```

Orden sugerido al retomar:

```txt
1. Revisar estado real de scan.php y pallet_status.php.
2. Confirmar cómo responde ahora un palet de plegado.
3. Adaptar interfaz para modo plegado.
4. Adaptar confirmación para insertar un único palet con source_type='plegado'.
5. Probar desde casa con datos de mirror si hay palet de plegado conocido.
6. Dejar pendiente solo validación física en A2.
```
