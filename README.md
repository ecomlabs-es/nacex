# Nacex - Módulo PrestaShop

Módulo de transporte para PrestaShop que integra los servicios de mensajería de [Nacex](https://www.nacex.es). Permite generar expediciones e imprimir etiquetas enviando información a Nacex con un solo clic.

## Requisitos

- PrestaShop 1.7.8+ / 8.x / 9.x
- PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 / 8.4
- Extensión PHP cURL

## Funcionalidades

- Generación de expediciones unitarias y masivas
- Impresión de etiquetas
- Integración con puntos de recogida NacexShop
- Seguimiento de expediciones y actualización automática de estados
- Soporte multiservicio (nacional, internacional, premium, e-commerce...)
- Gestión de devoluciones
- Compatible con módulos OPC (OnePageCheckout, SuperCheckout)
- Configuración de tarifas y zonas de envío
- Logs de actividad

## Instalación

1. Descarga el zip desde la sección [Releases](../../releases)
2. En el backoffice de PrestaShop: **Módulos > Subir un módulo** y selecciona el zip
3. Configura las credenciales de Nacex desde la configuración del módulo

## Desarrollo

```bash
# Instalar dependencias
composer install

# Tests unitarios
composer test

# Análisis estático
composer stan

# Lint de sintaxis PHP
composer lint:php

# Code style (check)
composer lint:cs

# Code style (fix)
composer fix:cs

# QA completo (lint + phpstan + tests)
composer qa
```

## CI/CD

- **CI**: En cada push/PR a `main` se ejecutan lint, PHPStan, tests y CS Fixer en PHP 7.4 a 8.4.
- **Release**: Al crear un tag se genera automáticamente un release en GitHub con el módulo empaquetado en zip.

## Cambios respecto al módulo original

### Compatibilidad
- Soporte para PrestaShop 1.7.8, 8.x y 9.x
- Compatibilidad con PHP 7.4 a 8.4
- Sustitución de `utf8_encode` (eliminado en PHP 8.2) por `toUtf8()` con detección automática de encoding
- Cast a `(float)` en llamadas a `number_format()` (estricto en PHP 8+)
- Compatibilidad Doctrine DBAL 3.x (PS9): `executeQuery()`/`fetchAllAssociative()`
- Hooks actualizados: eliminados hooks legacy pre-1.7.8, registrados hooks Symfony

### Seguridad
- Corrección de SQL injection en múltiples archivos: `(int)` cast y `pSQL()` en todas las queries
- XSS: escapado de `$_SERVER['REQUEST_URI']` en formularios, `addslashes()` en variables JS
- Hash anti-CSRF: `random_int()` en vez de `rand()`, validación como par hash+order_id, token de un solo uso
- Reemplazo de `$_POST`/`$_GET` directos por `Tools::getValue()`
- Activación de verificación SSL en llamadas cURL y adición de timeouts
- Sanitización de campos Address (city, address1) antes de creación

### Rendimiento
- Cache de carrier lookups por request (`isNacexCarrier`/`isNacexShopCarrier`/`isNacexIntCarrier`)
- Lectura de CSV centralizada con cache por request (`getCsvContents()`)
- Listado de pedidos: una sola query para todas las expediciones en vez de N+1
- Filtro de estados finales en SQL para evitar consultas WS innecesarias
- Consolidación de 3 queries de carriers genéricos en una sola con `IN`

### Funcionalidad
- Selección de punto NacexShop persistida en localStorage entre pasos del checkout
- CSV de puntos NacexShop convertido a UTF-8 al descargar, con retrocompatibilidad para archivos legacy
- Descarga automática del CSV si no existe al confirmar pedido
- Resolución de datos del punto NacexShop desde CSV actualizado (no datos estáticos)

### Calidad de código
- Eliminación de código muerto: hooks legacy, métodos vacíos, bloques comentados
- Tests unitarios con PHPUnit, análisis estático con PHPStan, estilo con PHP CS Fixer
- Workflows de GitHub Actions: CI (PHP 7.4-8.4) + release automático con zip

## Licencia

Academic Free License (AFL 3.0)
