# Nacex - Módulo PrestaShop

Módulo de transporte para PrestaShop que integra los servicios de mensajería de [Nacex](https://www.nacex.es). Permite generar expediciones e imprimir etiquetas enviando información a Nacex con un solo clic.

## Requisitos

- PrestaShop 1.7.7+
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

- Corrección de vulnerabilidades SQL injection en múltiples archivos (nacexDAO, MOunitaria, CPuntoNacexShop, CambioEstadoPedido, etc.)
- Sustitución de `utf8_encode` (eliminado en PHP 8.2) por función compatible
- Corrección de métodos deprecated de PrestaShop (`Execute` → `execute`, `ExecuteS` → `executeS`)
- Activación de verificación SSL en llamadas cURL y adición de timeouts
- Validación y try-catch en el tratamiento de respuestas XML del webservice
- Corrección de bugs: variables sin inicializar, operadores incorrectos, asignaciones faltantes
- Eliminación de código muerto y métodos sin uso
- Añadidos tests unitarios, PHPStan y PHP CS Fixer
- Añadidos workflows de GitHub Actions (CI + release automático)

## Licencia

Academic Free License (AFL 3.0)
