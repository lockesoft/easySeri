# 🧠 DOCUMENTO MAESTRO - SERIFRUIT CORE

## 🎯 OBJETIVO

Unificar todas las aplicaciones en una única plataforma modular, donde cada funcionalidad sea un módulo independiente activable/desactivable.

---

## 🏗️ ARQUITECTURA GENERAL

- Aplicación única (core)
- Sistema de módulos desacoplados
- Base de datos compartida (con separación lógica)
- Frontend único
- Sistema de eventos interno

---

## 🧩 CORE (NUCLEO)

Responsabilidades:

- Autenticación (login)
- Usuarios
- Roles y permisos
- Configuración global
- Logs
- Gestión de módulos
- Sistema de eventos

⚠️ REGLA:
El core NO contiene lógica de negocio de ningún módulo.

---

## 🧱 MÓDULOS

Cada módulo es independiente.

Ejemplo actual:
- camaras-ubicacion (EN DESARROLLO)

Ejemplos futuros:
- rapidpack
- aaf
- kpi
- mantenimiento
- erp-sync

---

## 🔌 SISTEMA DE MÓDULOS

Cada módulo debe tener:

- backend propio
- frontend propio
- config (module.json)
- permisos
- migraciones

---

## 🔄 ACTIVACIÓN DE MÓDULOS

Los módulos pueden:

- activarse
- desactivarse

Si están desactivados:
- no cargan rutas
- no aparecen en UI
- no ejecutan lógica

---

## 🧠 SISTEMA DE EVENTOS

Comunicación desacoplada entre módulos.

Ejemplo:
- RAPIDPACK_LLENO
- AAF_CAMBIO_DESTINO

---

## 🗄️ BASE DE DATOS

Una sola base, con separación lógica por módulo.

Ejemplo:

- core_users
- camaras_ubicacion
- kpi_data

---

## 📏 NORMAS IMPORTANTES

1. No mezclar lógica entre módulos
2. No meter lógica de negocio en el core
3. Todo módulo debe poder desactivarse
4. Toda decisión importante se documenta
5. Nada “provisional” sin documentar

---

## ⚠️ COSAS QUE NO SE DEBEN ROMPER

- Sistema de permisos
- Sistema de módulos
- Integridad del core