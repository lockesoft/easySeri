# 0001 - Estructura inicial de easySeri

## Fecha
2026-04-11

## Decisión
Se adopta una arquitectura de monolito modular con:
- core común
- módulos independientes
- documentación viva dentro del repositorio

## Motivo
Permitir crecimiento progresivo sin mezclar lógica de negocio entre aplicaciones.

## Consecuencias
- El core no contendrá lógica específica de módulos
- Cada nueva aplicación entrará como módulo
- La documentación será parte del proyecto y no un elemento externo