# Diseño del editor visual de cámaras

Fecha: 2026-05-10
Proyecto: easySeri / cámaras-ubicación

---

## 1. Objetivo

Crear una administración visual de cámaras que mantenga toda la funcionalidad dinámica de la app vieja, pero con una interfaz más intuitiva, rápida y agradable.

La cámara no debe tratarse como un simple registro con nombre y código. Una cámara representa una estructura física formada por:

- datos generales
- planta / almacén
- matriz de posiciones
- tipos de celda
- niveles de apilado
- punto de entrada
- filas reales / grupos
- celdas asignadas a cada fila real

El nuevo editor debe mejorar la experiencia sin romper las tablas existentes.

---

## 2. Tablas que NO se deben romper

El editor debe seguir trabajando con las tablas actuales:

```txt
cameras
camera_positions
camera_row_groups
camera_row_cells
placements
```

Regla importante:

```txt
No cambiar la lógica de escaneo hasta validar primero la administración visual.
```

---

## 3. Flujo recomendado

El proceso de creación/edición debe funcionar como un asistente visual por fases.

```txt
1. Datos generales
2. Dimensiones iniciales
3. Plano visual
4. Filas reales / grupos
5. Resumen y validación
```

---

## 4. Paso 1 — Datos generales

Campos:

- Nombre visible.
- Código.
- Planta / almacén.
- Prioridad.
- Notas.

Reglas:

- La planta se carga desde `core_plants`.
- El usuario solo puede crear cámaras en plantas que tenga asignadas.
- Toda cámara debe guardar `cameras.plant_code`.
- No debe existir cámara nueva sin planta.

---

## 5. Paso 2 — Dimensiones iniciales

Campos:

- Filas del plano.
- Columnas del plano.
- Niveles por defecto.

Al guardar:

- Se crea el registro en `cameras`.
- Se generan automáticamente las posiciones en `camera_positions`.
- Todas las posiciones nacen como `almacenaje`.
- Todas las posiciones nacen con `max_levels` indicado.
- Se abre el editor visual.

Validaciones:

- Filas mínimo 1.
- Columnas mínimo 1.
- Niveles mínimo 1.
- Evitar dimensiones absurdamente grandes.

---

## 6. Paso 3 — Editor visual del plano

Debe ser una pantalla visual, no una tabla técnica.

Cada celda del plano debe mostrar:

- fila / columna
- tipo de celda
- niveles
- si es punto de entrada
- si pertenece a un grupo/fila real

Tipos de celda:

```txt
almacenaje
pasillo
bloqueada
```

Herramientas mínimas:

- Pincel almacenaje.
- Pincel pasillo.
- Pincel bloqueada.
- Cambiar niveles.
- Marcar punto de entrada.
- Seleccionar fila completa.
- Seleccionar columna completa.
- Seleccionar todo.
- Quitar selección.
- Zoom.
- Mostrar/ocultar etiquetas.

Mejoras visuales deseadas:

- Botones grandes.
- Leyenda clara.
- Colores suaves y consistentes.
- Cabecera fija con cámara/planta.
- Resumen visible de capacidad.
- Mensajes claros de éxito/error.

---

## 7. Paso 4 — Filas reales / grupos

El operario no trabaja con coordenadas técnicas; trabaja con filas reales.

Por eso el editor debe permitir:

- seleccionar varias celdas del plano
- crear una fila real/grupo
- poner etiqueta visible
- indicar orden
- indicar orientación
- eliminar grupos
- resaltar grupos existentes

Tablas afectadas:

```txt
camera_row_groups
camera_row_cells
```

Campos principales:

- `camera_row_groups.camera_id`
- `camera_row_groups.label`
- `camera_row_groups.order_index`
- `camera_row_groups.orientation`
- `camera_row_cells.row_group_id`
- `camera_row_cells.position_id`

Orientaciones:

```txt
vertical
horizontal
mixed
```

---

## 8. Paso 5 — Resumen y validación

Antes de considerar una cámara preparada, mostrar:

- planta
- nombre
- código
- dimensiones
- número de posiciones de almacenaje
- número de pasillos
- número de bloqueadas
- capacidad total
- punto de entrada
- filas reales creadas

Validaciones recomendadas:

- La cámara debe tener al menos una posición de almacenaje.
- La cámara debe tener al menos una fila real/grupo para ser útil en escaneo.
- Debe avisar si no hay punto de entrada.
- Debe avisar si hay posiciones de almacenaje sin asignar a ninguna fila real.

---

## 9. Funciones futuras recomendadas

### 9.1 Duplicar cámara

Muy importante para A2.

Permitir duplicar una cámara existente:

```txt
Cámara A1
↓
Duplicar estructura
↓
Nueva cámara A2
```

Debe copiar:

- dimensiones
- `camera_positions`
- tipos de celda
- niveles
- grupos de filas reales
- celdas de grupos

No debe copiar:

- `placements`
- ocupación actual
- movimientos

### 9.2 Plantillas

A futuro, poder guardar una estructura como plantilla:

```txt
Plantilla cámara descarga estándar
Plantilla campa
Plantilla cámara pequeña
```

### 9.3 Editor por arrastre

Mejora visual futura:

- selección rectangular arrastrando
- pintar arrastrando
- atajos de teclado

---

## 10. Prioridad de implementación

### Fase A — Base funcional

- Listado de cámaras.
- Alta dinámica con planta.
- Guardado de matriz en `camera_positions`.
- Editor visual inicial.
- Pintar tipos.
- Cambiar niveles.
- Marcar entrada.
- Crear/eliminar filas reales.

Estado: iniciado.

### Fase B — Mejorar UX

- Rediseño visual.
- Panel lateral de herramientas.
- Resumen de capacidad.
- Validaciones de cámara incompleta.
- Mejor agrupación visual de filas reales.

### Fase C — Productividad

- Duplicar cámara.
- Plantillas.
- Selección por arrastre.
- Clonado A1 → A2.

---

## 11. Regla de seguridad

No tocar `scan_confirm.php` ni `move_confirm.php` para esta fase, salvo para validaciones futuras de planta, y solo después de validar la administración de cámaras.

---

## 12. Siguiente paso técnico

Rediseñar `modules/camaras-ubicacion/camaras/plano.php` para convertirlo en una pantalla más clara:

- cabecera limpia
- panel lateral de herramientas
- plano central amplio
- resumen inferior/superior
- validaciones visibles
- acciones agrupadas

Manteniendo la escritura en las tablas actuales.
