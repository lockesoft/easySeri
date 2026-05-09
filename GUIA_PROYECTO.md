# 🧭 GUÍA DEL PROYECTO - easySeri

Última revisión: 2026-05-09

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

**FASE ACTUAL:** FASE 3 — Integración funcional del módulo `camaras-ubicacion`.

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

---

## 3. Integración legacy validada

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

## 4. Endpoints funcionales

### Lectura

- ✔ `pallet_status.php`
- ✔ `entry_counts.php`
- ✔ `cameras.php`
- ✔ `camera_rows.php`

### Escritura

- ✔ `scan_confirm.php`
- ✔ `move_confirm.php`

---

## 5. Validaciones realizadas

### Escaneo y lectura

- ✔ Escaneo manual simulado.
- ✔ Detección de palet OK.
- ✔ Detección de entrada OK.
- ✔ Cálculo de pendientes OK.
- ✔ Listado de cámaras OK.
- ✔ Listado de filas OK.

### Inserción de ubicación

- ✔ Inserción controlada (`limit=1`) OK.
- ✔ Inserción múltiple preparada.
- ✔ `placed_by` correcto usando usuario easySeri.

### Movimiento de palets

- ✔ Movimiento controlado (`max_move=1`) OK.
- ✔ Cierre correcto de placement anterior (`removed_at`).
- ✔ Inserción de nueva posición OK.
- ✔ Integridad de datos mantenida.

### Estado de entradas pendientes

- ✔ Actualización de `erp_entries_pending` OK.

---

## 6. Decisiones importantes tomadas

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

- ✔ El sistema debe contemplar que los palets puedan pertenecer o ubicarse en planta A1 o planta A2.
- ✔ Hasta ahora la funcionalidad se había trabajado principalmente sobre A1.
- ✔ La prueba real actual se realizará en planta A2.
- ✔ El diseño no debe quedar fijo a una sola planta.
- ✔ Cada usuario podrá estar asociado a una planta de trabajo, por ejemplo A1 o A2.
- ✔ Las búsquedas, listados, cámaras, ubicaciones y validaciones deberán tener en cuenta la planta del usuario o la planta seleccionada.

### Tipos de palets / origen operativo

- ✔ En la prueba actual de planta solo hay palets de plegado.
- ✔ A futuro el sistema debe soportar tanto palets de plegado como palets de campo.
- ✔ Ambos tipos podrán existir en A1 o en A2.
- ✔ No se debe asumir que un palet pertenece a A1 por defecto.
- ✔ No se debe asumir que todos los palets son de campo.
- ✔ No se debe asumir que todos los palets son de plegado.

---

## 7. Problemas detectados / pendientes

### 7.1 Datos inconsistentes de prueba

Estado: **PENDIENTE**.

Motivos esperados:

- Pruebas manuales.
- Entorno no real.
- Inserciones de test.

Acción futura:

- Hacer reset controlado de datos.
- Limpiar `placements` de prueba.
- Limpiar estados incoherentes.
- Mantener estructura de tablas.
- No borrar nada sin copia previa y revisión de tablas reales.

---

### 7.2 `moves_log` incompleto

Estado: **PENDIENTE**.

Situación actual:

- `scan_confirm.php` no registra logs.
- `move_confirm.php` registra parcialmente.

Acción futura:

- Revisar estructura real de `moves_log`.
- Confirmar tipos válidos.
- Decidir qué acciones deben registrarse.
- Añadir auditoría solo después de verificar estructura y flujo.

---

### 7.3 Endpoints legacy extra

Estado: **NO INTEGRADOS AÚN**.

Detectados:

- `plegados`
- `tools`
- endpoints antiguos

Acción futura:

- Revisar cada endpoint real.
- Clasificar como: necesario, obsoleto o pendiente.
- No eliminar nada sin comprobar dependencias.

---

### 7.4 Planta del usuario / planta del palet

Estado: **PENDIENTE DE DISEÑO Y VERIFICACIÓN EN CÓDIGO**.

Necesidad:

- Definir cómo se guarda la planta del usuario.
- Definir cómo se guarda o deduce la planta del palet.
- Definir si la cámara pertenece a A1, A2 o ambas.
- Definir si el usuario puede cambiar de planta manualmente o queda fijado por perfil.
- Definir cómo evitar que un usuario de A2 ubique por error en una cámara de A1, salvo permiso especial.

Acción futura:

- Revisar estructura real de tablas de usuarios.
- Revisar estructura real de cámaras.
- Revisar mirror de plegados/campo.
- Añadir campos de planta solo después de verificar estructura actual.

---

## 8. Arquitectura actual

### Core easySeri

- Auth.
- Permisos.
- Router.
- Layout.
- Módulos dinámicos.
- Menú por permisos.

### Módulo cámaras

- Módulo `camaras-ubicacion` integrado.
- Pantalla legacy embebida en iframe.
- APIs legacy adaptadas.
- Base de datos independiente para cámaras.
- Usuario easySeri usado en operaciones adaptadas.

### Requisito nuevo: multi-planta

El módulo cámaras debe evolucionar para trabajar correctamente con:

- Planta A1.
- Planta A2.
- Usuarios asociados a una planta.
- Palets de plegado en A1/A2.
- Palets de campo en A1/A2.
- Cámaras asociadas a planta.

---

## 9. Flujo real validado

### Flujo scan

```txt
scan.php
  ↓
pallet_status.php
  ↓
entry_counts.php
  ↓
cameras.php
  ↓
camera_rows.php
  ↓
scan_confirm.php
  ↓
INSERT placements
```

### Flujo move

```txt
scan.php / flujo movimiento
  ↓
move_confirm.php
  ↓
UPDATE placement anterior con removed_at
  ↓
INSERT nueva posición
```

### Flujo pendiente multi-planta

Antes de validar en planta A2, el flujo deberá contemplar:

```txt
usuario easySeri
  ↓
planta del usuario o planta seleccionada
  ↓
escaneo de palet
  ↓
detectar origen: plegado o campo
  ↓
detectar/confirmar planta del palet: A1 o A2
  ↓
mostrar solo cámaras/filas válidas para esa planta
  ↓
confirmar ubicación
```

---

## 10. Base de datos usada activamente

Tablas marcadas como activas:

- ✔ `placements`
- ✔ `cameras`
- ✔ `camera_positions`
- ✔ `camera_row_groups`
- ✔ `camera_row_cells`
- ✔ `erp_palets_mirror`
- ✔ `erp_entradas_mirror`
- ✔ `erp_entries_pending`

Tabla parcial / pendiente de revisión:

- ⚠ `moves_log`

Pendiente de comprobar para multi-planta:

- Si `core_users` tiene campo de planta.
- Si `cameras` tiene campo de planta.
- Si los mirrors de palets/entradas/plegados contienen almacén, planta o ubicación origen.
- Si `placements` necesita registrar planta explícitamente o se deduce por cámara.

---

## 11. Siguiente fase

**FASE 4 — Estabilizar, limpiar y preparar multi-planta**

Objetivo:

```txt
Hacer robusto lo que ya funciona técnicamente y prepararlo para A1/A2 sin romper A1.
```

---

## 12. Tareas de Fase 4

### 12.1 Reset controlado de datos

Prioridad: **ALTA**.

Tareas:

- Revisar tablas reales antes de tocar datos.
- Preparar copia/export previo.
- Limpiar `placements` de prueba.
- Limpiar estados incoherentes.
- Mantener estructura.
- Documentar SQL usado.
- Tener en cuenta que la prueba actual será en A2 y con plegados.

---

### 12.2 Validación real en planta A2

Prioridad: **ALTA**.

Condición actual:

- En este momento la prueba en planta será en A2.
- En este momento en planta solo hay palets de plegado disponibles para probar.
- La funcionalidad anterior se había trabajado principalmente pensando en A1.

Tareas:

- Escaneo real con cámara o lector.
- Validación con palet real de plegado.
- Validación de planta A2.
- Confirmación de ubicación real en cámara/fila de A2.
- Movimiento real entre posiciones de A2.
- Comprobar que no se mezclan cámaras de A1 con A2.
- Comprobar tiempos de uso con operario.
- Detectar problemas de UX.

---

### 12.3 Revisión de logs y auditoría

Prioridad: **MEDIA / ALTA**.

Tareas:

- Revisar `moves_log` real.
- Definir qué eventos se registran.
- Registrar movimientos sin duplicar datos.
- Evitar romper tipos existentes.
- Incluir planta en auditoría si la estructura lo permite o si se añade campo validado.

---

### 12.4 Permisos finos de cámaras

Prioridad: **MEDIA**.

Permisos propuestos:

- `camaras.scan`
- `camaras.move`
- `camaras.admin`
- `camaras.all_plants` para usuarios autorizados a operar A1 y A2.

Pendiente:

- Revisar sistema actual de permisos.
- Crear permisos en base de datos.
- Asignar permisos a roles.
- Aplicar permisos en rutas/endpoints.
- Definir si un usuario normal solo ve su planta.

---

### 12.5 Mejora de UI operario

Prioridad: **MEDIA**.

Tareas futuras:

- Eliminar iframe cuando sea seguro.
- Integrar scan directamente en layout easySeri.
- Simplificar pantalla para operario.
- Mostrar claramente planta activa: A1 o A2.
- Mostrar claramente tipo de palet: plegado o campo.
- Mejorar mensajes visuales.
- Mantener compatibilidad móvil/tablet.

---

### 12.6 Revisión de endpoints secundarios

Prioridad: **BAJA / MEDIA**.

Pendiente revisar:

- `plegados`
- informes
- administración de cámaras
- herramientas antiguas
- endpoints obsoletos

---

## 13. Riesgos conocidos

### Riesgo 1 — Datos de prueba mezclados

Puede provocar resultados incoherentes en validación real.

Mitigación:

- Reset controlado.
- Copia previa.
- SQL documentado.

### Riesgo 2 — Legacy funcional pero no completamente integrado

El iframe funciona, pero puede complicar permisos, estilos y mantenimiento.

Mitigación:

- Mantener iframe hasta validar en planta.
- Integrar progresivamente.

### Riesgo 3 — Logs incompletos

Puede faltar trazabilidad completa de operaciones.

Mitigación:

- Revisar `moves_log` antes de ampliar.

### Riesgo 4 — Permisos demasiado generales

Actualmente el acceso principal depende de `camaras-ubicacion.access`.

Mitigación:

- Añadir permisos finos en Fase 4.

### Riesgo 5 — Mezcla de plantas A1/A2

Si no se controla la planta, un usuario podría ubicar un palet en una cámara incorrecta.

Mitigación:

- Asociar cámaras a planta.
- Asociar usuario a planta.
- Filtrar cámaras/filas por planta activa.
- Permitir multi-planta solo con permiso específico.

### Riesgo 6 — Mezcla de origen plegado/campo

Si no se distingue el origen del palet, podrían aplicarse reglas incorrectas.

Mitigación:

- Detectar o registrar tipo de palet: plegado/campo.
- Validar mirrors reales antes de tocar código.

---

## 14. Filosofía del proyecto

```txt
Primero funcional.
Luego estable.
Luego limpio.
Luego refactor.
```

No se debe refactorizar algo crítico si todavía no está validado en planta.

---

## 15. Conclusión actual

El módulo `camaras-ubicacion`:

- ✔ Ya funciona.
- ✔ Ya se integra con easySeri.
- ✔ Ya carga la pantalla de escaneo.
- ✔ Ya consulta endpoints.
- ✔ Ya inserta ubicaciones.
- ✔ Ya mueve palets.

Ahora toca:

```txt
hacerlo robusto, limpio, multi-planta y usable en entorno real.
```

---

## 16. Siguiente paso recomendado

Opción recomendada:

```txt
REVISIÓN MULTI-PLANTA + RESET CONTROLADO + PRUEBA EN PLANTA A2 CON PLEGADOS
```

Antes de hacer el reset:

1. Revisar estructura real de tablas.
2. Comprobar si usuarios tienen planta.
3. Comprobar si cámaras tienen planta.
4. Comprobar si palets de plegado/campo tienen planta o almacén origen.
5. Hacer copia/export.
6. Preparar SQL reversible o muy controlado.
7. Ejecutar limpieza.
8. Probar con un flujo real completo en A2.

Opción alternativa:

```txt
seguir integrando administración de cámaras / UI / permisos
```

No recomendada antes de validar datos reales y multi-planta.
