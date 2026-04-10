<p align="center">
  <img src="images/logos/nacex_logista.png" alt="Nacex" height="80">
</p>

<h1 align="center">Nacex - Módulo PrestaShop</h1>

<p align="center">
  Genera expediciones e imprime etiquetas de <a href="https://www.nacex.es">Nacex</a> con un solo clic desde tu tienda PrestaShop.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PrestaShop-1.7.8%20%7C%208.x%20%7C%209.x-blue" alt="PrestaShop">
  <img src="https://img.shields.io/badge/PHP-7.4%20%7C%208.0--8.4-purple" alt="PHP">
  <img src="https://img.shields.io/badge/Licencia-AFL%203.0-green" alt="License">
</p>

---

## Requisitos

- PrestaShop 1.7.8+ / 8.x / 9.x
- PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 / 8.4
- Extensión PHP cURL

## Funcionalidades

| Funcionalidad | Descripcion |
|---|---|
| Expediciones unitarias y masivas | Genera envios individuales o en lote |
| Impresion de etiquetas | PDF directo desde el pedido |
| NacexShop | Puntos de recogida integrados en el checkout |
| Seguimiento | Actualizacion automatica de estados |
| Multiservicio | Nacional, internacional, premium, e-commerce... |
| Devoluciones | Gestion de expediciones de retorno |
| OPC | Compatible con OnePageCheckout, SuperCheckout |
| Tarifas y zonas | Configuracion flexible de precios por zona |
| Logs | Registro de actividad para depuracion |

## Instalacion

1. Descarga el zip desde la seccion [Releases](../../releases)
2. En el backoffice de PrestaShop: **Modulos > Subir un modulo** y selecciona el zip
3. Configura las credenciales de Nacex desde la configuracion del modulo

## Desarrollo

```bash
composer install          # Instalar dependencias
composer test             # Tests unitarios
composer stan             # Analisis estatico (PHPStan)
composer lint:php         # Lint de sintaxis PHP
composer lint:cs          # Code style (check)
composer fix:cs           # Code style (fix)
composer qa               # QA completo (lint + phpstan + tests)
```

## CI/CD

| Workflow | Trigger | Descripcion |
|---|---|---|
| **CI** | Push / PR a `main` | Lint, PHPStan, tests y CS Fixer en PHP 7.4 a 8.4 |
| **Release** | Nuevo tag | Genera release en GitHub con el modulo empaquetado en zip |

## Cambios respecto al modulo original

<details>
<summary><strong>Compatibilidad</strong></summary>

- Soporte para PrestaShop 1.7.8, 8.x y 9.x
- Compatibilidad con PHP 7.4 a 8.4
- Sustitucion de `utf8_encode` (eliminado en PHP 8.2) por `toUtf8()` con deteccion automatica de encoding
- Cast a `(float)` en llamadas a `number_format()` (estricto en PHP 8+)
- Compatibilidad Doctrine DBAL 3.x (PS9): `executeQuery()`/`fetchAllAssociative()`
- Hooks actualizados: eliminados hooks legacy pre-1.7.8, registrados hooks Symfony
</details>

<details>
<summary><strong>Seguridad</strong></summary>

- Correccion de SQL injection en multiples archivos: `(int)` cast y `pSQL()` en todas las queries
- XSS: escapado de `$_SERVER['REQUEST_URI']` en formularios, `addslashes()` en variables JS
- Hash anti-CSRF: `random_int()` en vez de `rand()`, validacion como par hash+order_id, token de un solo uso
- Reemplazo de `$_POST`/`$_GET` directos por `Tools::getValue()`
- Activacion de verificacion SSL en llamadas cURL y adicion de timeouts
- Sanitizacion de campos Address (city, address1) antes de creacion
</details>

<details>
<summary><strong>Rendimiento</strong></summary>

- Cache de carrier lookups por request (`isNacexCarrier`/`isNacexShopCarrier`/`isNacexIntCarrier`)
- Lectura de CSV centralizada con cache por request (`getCsvContents()`)
- Listado de pedidos: una sola query para todas las expediciones en vez de N+1
- Filtro de estados finales en SQL para evitar consultas WS innecesarias
- Consolidacion de 3 queries de carriers genericos en una sola con `IN`
</details>

<details>
<summary><strong>Funcionalidad</strong></summary>

- Seleccion de punto NacexShop persistida en localStorage entre pasos del checkout
- CSV de puntos NacexShop convertido a UTF-8 al descargar, con retrocompatibilidad para archivos legacy
- Descarga automatica del CSV si no existe al confirmar pedido
- Resolucion de datos del punto NacexShop desde CSV actualizado (no datos estaticos)
- Persistir estado de expedicion en BD al visualizar pedido (sincroniza con WS de Nacex)
- Cancelar expedicion: solo disponible en estado PENDIENTE (notificado), actualiza BD cuando ya fue cancelada externamente (error 5611)
- Actualizacion automatica de estado del pedido para transito, reparto e incidencia
- Inicializacion de zonas segura: no sobreescribe configuracion global de paises
- Soporte multitienda en inicializacion de zonas (zone_shop segun contexto)
- Popup NacexShop cross-origin: fallback con postMessage
- Historico de expediciones desde tabla principal (eliminada tabla _his)
</details>

<details>
<summary><strong>Interfaz</strong></summary>

- Logo Nacex Logista en todas las vistas
- Tabla masiva: badges de estado de expedicion, toolbar con filtro por transportista
- Tabla masiva: extender ModuleAdminController para PS8, fix maquetacion
- Configuracion: multi-select reemplazados por checkboxes
- Configuracion: toggles con estilo nativo PrestaShop 8
- Configuracion: tab Nacex se abre directamente sin redireccion
- Proteccion de endpoints AJAX con verificacion de admin
</details>

<details>
<summary><strong>Calidad de codigo</strong></summary>

- Eliminacion de codigo muerto y dependencias no utilizadas
- Tests unitarios con PHPUnit (96 tests, 141 assertions)
- Analisis estatico con PHPStan, estilo con PHP CS Fixer
- Workflows de GitHub Actions: CI (PHP 7.4-8.4) + release automatico con zip
</details>

## Donaciones

Si te ha gustado este trabajo y te ha sido util, puedes invitarme a un cafe ;)

[![Donar](https://img.shields.io/badge/Stripe-Donar-blue?logo=stripe)](https://buy.stripe.com/28E28rdbX96l7r95Nd1ck00)

## Licencia

Academic Free License (AFL 3.0)
