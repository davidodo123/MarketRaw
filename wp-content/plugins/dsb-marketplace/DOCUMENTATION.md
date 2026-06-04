# DSB Marketplace — Documentación Técnica Completa

**Versión:** 1.0.0  
**Stack:** PHP 8.x · WordPress 6.x · WooCommerce 7+ · MySQL 8  
**Desarrollado por:** David Santiago Ybermúdez

---

## Índice

1. [Visión general del proyecto](#1-visión-general)
2. [Arquitectura del plugin](#2-arquitectura)
3. [Base de datos](#3-base-de-datos)
4. [Fase 1 — Skeleton, BD, CPT, registro de vendedores](#4-fase-1)
5. [Fase 2 — Dashboard, AJAX search, página de tienda](#5-fase-2)
6. [Fase 3 — WooCommerce, split de pedidos, comisiones](#6-fase-3)
7. [Fase 4 — REST API](#7-fase-4)
8. [Tema MarketRaw](#8-tema-marketraw)
9. [Seguridad](#9-seguridad)
10. [Crons y notificaciones](#10-crons-y-notificaciones)
11. [Deploy en VPS](#11-deploy-en-vps)
12. [Futuras mejoras](#12-futuras-mejoras)
13. [Referencia rápida de endpoints REST](#13-referencia-rest)
14. [Referencia rápida de AJAX actions](#14-referencia-ajax)
15. [Glosario de hooks propios](#15-hooks)

---

## 1. Visión general

**DSB Marketplace** es un marketplace multi-vendor para negocios locales de Granada, construido como plugin WordPress custom. No depende de Dokan, WC Vendors ni ningún plugin de terceros equivalente. WooCommerce se usa exclusivamente como motor de pagos y carrito.

### Flujo general de usuario

```
Comprador navega → filtra productos (AJAX) → añade al carrito WC
→ checkout WC → pago → pedido "completed"
→ plugin split automático por vendedor
→ email al vendedor + crédito en balance
→ vendedor gestiona desde dashboard
```

### Flujo de vendedor

```
Usuario registrado → /crear-mi-tienda/ → formulario AJAX
→ tienda en estado "pending" → admin aprueba → "active"
→ vendedor accede a /mi-tienda/ → añade productos → vende
```

---

## 2. Arquitectura

### Estructura de ficheros

```
dsb-marketplace/
├── dsb-marketplace.php          ← Entry point, constants, autoloader, hooks de activación
├── includes/
│   ├── class-dsb-marketplace.php   ← Core singleton, bootstrap
│   ├── class-dsb-install.php       ← Tablas BD, roles, páginas, crons (activación)
│   ├── class-dsb-vendor.php        ← CRUD vendedores, shortcode registro, AJAX
│   ├── class-dsb-product.php       ← CPT dsb_product, taxonomías, meta boxes
│   ├── class-dsb-order.php         ← Split de pedidos WC, reversión de reembolsos
│   ├── class-dsb-commission.php    ← Cálculo de comisiones, estadísticas
│   ├── class-dsb-notification.php  ← Emails inmediatos, crons diario/semanal
│   ├── class-dsb-rest-api.php      ← Endpoints REST /dsb/v1/*
│   ├── class-dsb-ajax.php          ← Handlers AJAX públicos y de vendedor
│   └── class-dsb-notification.php
├── admin/
│   ├── class-dsb-admin.php         ← Admin menu, enqueue, AJAX admin
│   ├── views/
│   │   ├── dashboard.php           ← Panel principal con stats globales
│   │   ├── vendors.php             ← Listado y aprobación de tiendas
│   │   ├── orders.php              ← Pedidos multi-vendor con paginación
│   │   ├── settings.php            ← Configuración global (% comisión)
│   │   ├── product-pricing-meta.php
│   │   └── product-gallery-meta.php
│   └── assets/
│       ├── css/admin.css
│       └── js/admin.js
├── public/
│   ├── class-dsb-public.php        ← Shortcodes, rewrite rules, enqueue condicional
│   ├── views/
│   │   ├── marketplace.php         ← UI de búsqueda con filtros y grid AJAX
│   │   ├── vendor-register.php     ← Formulario de registro de tienda
│   │   ├── vendor-dashboard.php    ← Dashboard con tabs (productos/pedidos/config)
│   │   └── vendor-store.php        ← Página pública /tienda/{slug}/
│   └── assets/
│       ├── css/public.css
│       ├── js/marketplace.js       ← Search AJAX, filtros, load more
│       └── js/vendor-dashboard.js  ← CRUD productos, tabs, settings
└── languages/
```

### Principios de diseño

- **Una clase por fichero.** Namespace `DSB\Marketplace`.
- **Autoloader por mapa explícito** en `dsb-marketplace.php`. Predecible, sin magic.
- **Hooks en `__construct`, lógica en métodos separados.** Nunca lógica en el constructor.
- **Singleton** solo en `Core`. El resto de clases son instancias normales.
- **Zero var_dump/echo en código final.** Usar `error_log()` si es necesario.

---

## 3. Base de datos

### `dsb_vendors`

Almacena la información de cada tienda.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | BIGINT UNSIGNED PK | ID interno |
| `user_id` | BIGINT UNSIGNED | FK `wp_users` |
| `store_name` | VARCHAR(200) | Nombre público |
| `store_slug` | VARCHAR(200) UNIQUE | URL slug: `/tienda/{slug}/` |
| `description` | TEXT | Descripción de la tienda |
| `logo_id` | BIGINT | FK `wp_posts` (attachment) |
| `banner_id` | BIGINT | FK `wp_posts` (attachment) |
| `address` | VARCHAR(300) | Dirección física |
| `city` | VARCHAR(100) | Default: Granada |
| `phone` | VARCHAR(20) | |
| `status` | ENUM | `pending` / `active` / `suspended` |
| `commission_rate` | DECIMAL(5,2) | % comisión aplicada a esta tienda |
| `balance` | DECIMAL(10,2) | Saldo pendiente de cobro |
| `created_at` | DATETIME | |

**Índices:** `idx_user_id`, `idx_status`, UNIQUE `store_slug`

---

### `dsb_vendor_orders`

Split de cada pedido WooCommerce por vendedor.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | BIGINT UNSIGNED PK | |
| `wc_order_id` | BIGINT | FK `wp_woocommerce_orders` |
| `vendor_id` | BIGINT | FK `dsb_vendors.id` |
| `subtotal` | DECIMAL(10,2) | Total de items del vendedor |
| `commission` | DECIMAL(10,2) | Comisión del marketplace |
| `vendor_earnings` | DECIMAL(10,2) | `subtotal - commission` |
| `status` | ENUM | `pending` / `processing` / `completed` / `refunded` |
| `paid_at` | DATETIME | Fecha de cobro |

**Índices:** `idx_vendor_id`, `idx_wc_order_id`

---

### `dsb_vendor_reviews`

Valoraciones de compradores verificados.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `vendor_id` | BIGINT | |
| `user_id` | BIGINT | |
| `wc_order_id` | BIGINT | El comprador debe tener pedido con ese vendedor |
| `rating` | TINYINT | 1–5 |
| `comment` | TEXT | |

**Constraint:** UNIQUE `(vendor_id, user_id, wc_order_id)` — una reseña por pedido.

---

### `dsb_transactions`

Log inmutable de todos los movimientos de saldo.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `vendor_id` | BIGINT | |
| `type` | ENUM | `earning` / `withdrawal` / `refund` |
| `amount` | DECIMAL(10,2) | Puede ser negativo (refund) |
| `reference` | VARCHAR(100) | Ej: `WC-1234`, `REFUND-WC-1234` |

---

## 4. Fase 1

### Clase `Install`

Ejecutada en `register_activation_hook`. Hace:

1. `create_tables()` — crea las 4 tablas con `dbDelta()` (idempotente, safe para updates)
2. `add_roles_and_caps()` — crea rol `dsb_vendor`, añade caps a `administrator`
3. `create_pages()` — inserta páginas `marketplace`, `mi-tienda`, `crear-mi-tienda` con shortcodes
4. Registra rewrite rule `/tienda/{slug}/`
5. Programa crons
6. `flush_rewrite_rules()`

En `deactivation_hook`: limpia crons, flush rewrite rules. **No elimina tablas** (datos del usuario).

### Clase `Vendor`

Métodos públicos clave:

```php
create_vendor(array $data): int|false
get_vendor_by_user(int $user_id): ?object
get_vendor_by_id(int $vendor_id): ?object
get_vendor_by_slug(string $slug): ?object
update_vendor_status(int $vendor_id, string $status): bool
get_vendors(array $args): array          // listado paginado
is_active_vendor(int $user_id): bool
```

El shortcode `[dsb_vendor_register]` renderiza `public/views/vendor-register.php` y carga `vendor-register.js` condicionalmente (solo cuando el shortcode está en la página).

El AJAX `dsb_register_vendor`:
- Verifica nonce `dsb_vendor_register_nonce`
- Sanitiza todos los inputs
- Genera slug único con `sanitize_title()` + loop de deduplicación
- Inserta con `$wpdb->insert()` — nunca interpolación SQL
- Envía email al admin

### Clase `Product`

Registra el **Custom Post Type `dsb_product`** con:
- Slug de archivo: `/producto/{post-name}/`
- Taxonomías: `dsb_category` (jerárquica) y `dsb_zone` (plana, barrios de Granada)
- Meta boxes: precio, stock, destacado, galería
- `show_in_rest: true` — consumible por REST API y editor Gutenberg

El hook `pre_get_posts` restringe a los vendedores para que solo vean sus propios productos en wp-admin.


## 5. Fase 2

### AJAX Search

El endpoint `dsb_search` (en `Ajax::search()`) construye SQL crudo con `$wpdb` para máximo rendimiento:

```sql
SELECT DISTINCT p.ID, p.post_title, p.post_author
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_dsb_price')
[INNER JOIN taxonomy tables if filtered]
WHERE p.post_type = 'dsb_product'
  AND p.post_status = 'publish'
  [AND p.post_title LIKE %s]
  [AND CAST(pm.meta_value AS DECIMAL) BETWEEN %f AND %f]
  [AND t_cat.slug = %s]
  [AND t_zone.slug = %s]
ORDER BY p.post_date DESC
LIMIT %d OFFSET %d
```

Todos los params pasan por `$wpdb->prepare()`. La query COUNT se ejecuta separada (sin LIMIT) para paginación.

### Marketplace JS (`marketplace.js`)

- Debounce 380ms en búsqueda de texto
- Debounce 600ms en rango de precios
- Chips de categoría con aria-checked para accesibilidad
- Render HTML en cliente (no templates PHP)
- Escaping manual (`escHtml`, `escAttr`) para prevenir XSS en datos dinámicos
- `window.dsbSearchVendor(uid)` — API pública para filtrar por vendedor desde la store page

### Dashboard (`vendor-dashboard.js`)

Arquitectura tab-based:
- Tab activo persiste en memoria de sesión (no en URL, por simplicidad)
- Al entrar en tab Productos: carga lista AJAX automáticamente
- Formulario inline (no modal) con `slideDown/Up` jQuery
- Edición: extrae datos mínimos de la fila de tabla; los campos avanzados (descripción, categoría) se rellenan vacíos — intencional para Fase 2, datos completos en Fase 5

### Rewrite rules (`/tienda/{slug}/`)

```php
add_rewrite_rule('^tienda/([^/]+)/?$', 'index.php?dsb_vendor_slug=$matches[1]', 'top');
```

Registrada en `init` (siempre) y en `activate()` (antes del flush). El filtro `template_include` devuelve `public/views/vendor-store.php` si el query var existe. El template llama a `get_header()` y `get_footer()` del tema activo.

---

## 6. Fase 3

### Clase `Order` — split automático

El hook principal:

```php
add_action('woocommerce_order_status_changed', [$this, 'on_status_changed'], 10, 4);
```

Se activa cuando un pedido pasa a `completed`. El método `split_order()`:

1. **Idempotencia:** verifica que `wc_order_id` no exista ya en `dsb_vendor_orders`. Si existe, sale sin hacer nada.
2. Itera `$order->get_items()` agrupando subtotales por `post_author` del producto.
3. Para cada vendedor: calcula comisión, inserta `dsb_vendor_orders`, actualiza `balance`, inserta `dsb_transactions`, envía email.

Si el pedido pasa a `refunded` o `cancelled`: `reverse_order()` hace el proceso inverso — descuenta balance, inserta transacción de tipo `refund` con monto negativo.

### Clase `Commission` — cálculo

```php
calculate(float $subtotal, float $rate, ?object $vendor = null): [commission, vendor_earnings]
```

**Primer mes gratis:** si `$vendor->created_at` es < 30 días, `rate = 0.0`. Implementado como feature de onboarding sin intervención del admin.

```php
$commission      = round($subtotal * $rate / 100, 2);
$vendor_earnings = round($subtotal - $commission, 2);
```

Se usa `round(..., 2)` — nunca `floor` o `ceil` para no perjudicar sistemáticamente a un lado.

### Clase `Notification`

#### Emails inmediatos

- `vendor_new_order()` — al completar pedido. Enviado dentro del mismo request HTTP (síncrono, via `wp_mail`).
- `vendor_approved()` — cuando admin aprueba la tienda. Llamado desde `Vendor::update_vendor_status()`.

#### Crons registrados en activación

| Cron hook | Frecuencia | Hora | Función |
|-----------|-----------|------|---------|
| `dsb_daily_summary` | `daily` | 08:00 del día siguiente | Resumen de ventas del día a cada vendedor con pedidos |
| `dsb_weekly_low_stock` | `weekly` | Lunes 09:00 | Aviso a vendedores con productos con stock < 5 |

Los crons solo envían email si hay datos relevantes (no molestan con emails vacíos).

---

## 7. Fase 4

### REST API — Namespace `dsb/v1`

Implementada en `REST_API::register_routes()` via `rest_api_init`. Todos los endpoints públicos usan `'permission_callback' => '__return_true'`. El único endpoint autenticado es `POST /vendors/{id}/reviews`.

**Autenticación:** WP Application Passwords (nativo desde WP 5.6). Header: `Authorization: Basic base64(user:app_password)`.

### Formato de respuesta consistente

**Colecciones:**
```json
{
  "data": [...],
  "meta": {
    "total": 47,
    "pages": 4,
    "page": 1,
    "per_page": 12
  }
}
```

**Recursos individuales:**
```json
{
  "data": { ... }
}
```

**Errores:** `WP_Error` con código HTTP correcto (400, 401, 403, 404, 409, 500).

### Consideraciones de los endpoints

**`GET /search?q=`:** busca simultáneamente en productos (WP_Query con `s`) y en tiendas (`$wpdb` LIKE). El param `type` permite filtrar solo uno. Pensado para el chatbot IA de Fase 5.

**`POST /vendors/{id}/reviews`:** valida que el `wc_order_id` pertenezca al usuario autenticado (si WooCommerce está disponible). La constraint UNIQUE en BD previene duplicados aunque el check falle.

**`GET /products`:** usa `WP_Query` en lugar de `$wpdb` raw porque necesita compatibilidad con plugins de cache, filtros de terceros, y el sistema de paginación de WP. La búsqueda AJAX del frontend usa `$wpdb` raw por performance; la API REST prioriza compatibilidad.

---

## 8. Tema MarketRaw

El tema `marketraw` es un tema custom minimalista que sirve como frontend del marketplace. **No es un tema de uso general** — está diseñado específicamente para el plugin.

### Design System

Variables CSS en `:root`:
- Colores base: `--bg`, `--surface`, `--surface-2`, `--surface-3`
- Accentos: `--purple` (#7C3AED), `--orange` (#F97316), `--cyan`, `--green`, `--pink`
- Gradiente principal: `--grad-text` (purple → pink → orange)
- Sombras: `--shadow-sm`, `--shadow`, `--shadow-md`, `--shadow-lg`

### Animaciones JS (`main.js`)

| Feature | Descripción |
|---------|-------------|
| Scroll progress bar | Barra de progreso en el top, actualizada en scroll |
| Nav glassmorphism | Blur + border aparece al pasar 50px de scroll |
| Mobile nav | Hamburger con animación de líneas, overlay con `body.overflow:hidden` |
| Magnetic buttons | `[data-magnetic]` — el botón se mueve hacia el cursor (strength 0.38) |
| 3D tilt cards | `[data-tilt]` — rotateX/Y según posición del cursor, max 9° |
| Counter animation | `[data-count]` — easeOut cubic desde 0 al valor target, 1600ms |
| Split text reveal | `[data-split]` — divide por palabras en `<span>`, stagger reveal |
| Intersection Observer | `.reveal` — fadeInUp al entrar en viewport |
| Hero word cycle | Ciclo de palabras con transición enter/leave en el h1 del hero |
| Blob parallax | Los blobs del hero se mueven suavemente siguiendo el ratón |
| Hero cards parallax | Las tarjetas flotantes del hero responden al movimiento del ratón |

### Overrides del plugin DSB

El tema incluye en `main.css` una sección `/* DSB PLUGIN OVERRIDES */` que adapta los estilos del plugin al design system del tema (colores, border-radius, sombras). Esto permite que el plugin funcione con cualquier tema sin estilos rotos, y que el tema propio se vea perfecto.

### Templates

| Fichero | URL | Descripción |
|---------|-----|-------------|
| `front-page.php` | `/` | Landing page del marketplace |
| `page.php` | `/marketplace/`, `/mi-tienda/`, etc. | Páginas con shortcodes del plugin |
| `index.php` | Blog fallback | Solo posts del blog |
| `header.php` | — | Nav fixed con scroll effect |
| `footer.php` | — | Footer dark con links |

---

## 9. Seguridad

### Checklist implementado

| Medida | Dónde |
|--------|-------|
| `check_ajax_referer()` en todos los handlers AJAX | `Ajax`, `Vendor`, `Admin` |
| `$wpdb->prepare()` en todas las queries | Sin excepción en todo el plugin |
| `sanitize_text_field()`, `sanitize_textarea_field()`, `sanitize_key()`, `absint()` | Todos los inputs |
| `esc_html()`, `esc_attr()`, `esc_url()` en todos los outputs | Todos los templates |
| `wp_kses_post()` para contenido HTML del vendedor | `Ajax::vendor_save_product()` |
| `current_user_can()` antes de operaciones sensibles | Todos los endpoints de vendedor y admin |
| Verificación de `post_author` antes de editar/eliminar productos | `Ajax` |
| Constraint UNIQUE en BD para reviews duplicadas | Schema |
| `'permission_callback' => '__return_true'` solo en endpoints de lectura pública | `REST_API` |
| Roles y capabilities custom | `Install::add_roles_and_caps()` |

### Roles y capabilities

| Role | Caps |
|------|------|
| `dsb_vendor` | `read`, `upload_files`, `dsb_manage_own_store`, `dsb_manage_own_products` |
| `administrator` | Todo lo anterior + `dsb_manage_marketplace`, `dsb_manage_vendors` |

---

## 10. Crons y notificaciones

### Registro

```php
// En Install::activate()
wp_schedule_event(strtotime('tomorrow 08:00:00'), 'daily', 'dsb_daily_summary');
wp_schedule_event(strtotime('next monday 09:00:00'), 'weekly', 'dsb_weekly_low_stock');

// En Install::deactivate()
wp_clear_scheduled_hook('dsb_daily_summary');
wp_clear_scheduled_hook('dsb_weekly_low_stock');
```

### wp_cron vs cron real en VPS

`wp_cron` se ejecuta en peticiones HTTP. En producción con poco tráfico, puede retrasarse. Solución: deshabilitar wp_cron y usar cron real del sistema.

En `/etc/crontab` o `crontab -e`:
```bash
# Deshabilitar wp_cron en wp-config.php
define('DISABLE_WP_CRON', true);

# Cron del sistema (cada minuto)
* * * * * www-data /usr/bin/php /var/www/html/wp-cron.php > /dev/null 2>&1
```

O con WP-CLI:
```bash
* * * * * www-data wp --path=/var/www/html cron event run --due-now > /dev/null 2>&1
```

---

## 11. Deploy en VPS

### Stack recomendado

```
Ubuntu 22.04 LTS
Nginx 1.24
PHP 8.2-FPM
MySQL 8.0
Redis 7 (object cache con wp-redis)
Let's Encrypt (Certbot)
WP-CLI
```

### Script de deploy

```bash
#!/bin/bash
set -e

WEBROOT="/var/www/marketraw"
PLUGIN_PATH="$WEBROOT/wp-content/plugins/dsb-marketplace"

# Pull latest
cd $WEBROOT
git pull origin main

# Flush cache
wp --path=$WEBROOT cache flush

# Reactivar plugin para re-run de migraciones BD si la versión cambió
CURRENT_VERSION=$(wp --path=$WEBROOT option get dsb_marketplace_version 2>/dev/null || echo "0")
PLUGIN_VERSION=$(grep "Version:" $PLUGIN_PATH/dsb-marketplace.php | awk '{print $3}')

if [ "$CURRENT_VERSION" != "$PLUGIN_VERSION" ]; then
  wp --path=$WEBROOT plugin deactivate dsb-marketplace
  wp --path=$WEBROOT plugin activate dsb-marketplace
  echo "Plugin actualizado: $CURRENT_VERSION → $PLUGIN_VERSION"
fi

# Flush rewrite rules
wp --path=$WEBROOT rewrite flush

echo "Deploy completado."
```


### Dashboard (`vendor-dashboard.js`)

Arquitectura tab-based:
- Tab activo persiste en memoria de sesión (no en URL, por simplicidad)
- Al entrar en tab Productos: carga lista AJAX automáticamente
- Formulario inline (no modal) con `slideDown/Up` jQuery
- Edición: extrae datos mínimos de la fila de tabla; los campos avanzados (descripción, categoría) se rellenan vacíos — intencional para Fase 2, datos completos en Fase 5

### Rewrite rules (`/tienda/{slug}/`)

```php
add_rewrite_rule('^tienda/([^/]+)/?$', 'index.php?dsb_vendor_slug=$matches[1]', 'top');
```

Registrada en `init` (siempre) y en `activate()` (antes del flush). El filtro `template_include` devuelve `public/views/vendor-store.php` si el query var existe. El template llama a `get_header()` y `get_footer()` del tema activo.

---

## 6. Fase 3

### Clase `Order` — split automático

El hook principal:

```php
add_action('woocommerce_order_status_changed', [$this, 'on_status_changed'], 10, 4);
```

Se activa cuando un pedido pasa a `completed`. El método `split_order()`:

1. **Idempotencia:** verifica que `wc_order_id` no exista ya en `dsb_vendor_orders`. Si existe, sale sin hacer nada.
2. Itera `$order->get_items()` agrupando subtotales por `post_author` del producto.
3. Para cada vendedor: calcula comisión, inserta `dsb_vendor_orders`, actualiza `balance`, inserta `dsb_transactions`, envía email.

Si el pedido pasa a `refunded` o `cancelled`: `reverse_order()` hace el proceso inverso — descuenta balance, inserta transacción de tipo `refund` con monto negativo.

### Clase `Commission` — cálculo

```php
calculate(float $subtotal, float $rate, ?object $vendor = null): [commission, vendor_earnings]
```

**Primer mes gratis:** si `$vendor->created_at` es < 30 días, `rate = 0.0`. Implementado como feature de onboarding sin intervención del admin.

```php
$commission      = round($subtotal * $rate / 100, 2);
$vendor_earnings = round($subtotal - $commission, 2);
```

Se usa `round(..., 2)` — nunca `floor` o `ceil` para no perjudicar sistemáticamente a un lado.

### Clase `Notification`

#### Emails inmediatos

- `vendor_new_order()` — al completar pedido. Enviado dentro del mismo request HTTP (síncrono, via `wp_mail`).
- `vendor_approved()` — cuando admin aprueba la tienda. Llamado desde `Vendor::update_vendor_status()`.

#### Crons registrados en activación

| Cron hook | Frecuencia | Hora | Función |
|-----------|-----------|------|---------|
| `dsb_daily_summary` | `daily` | 08:00 del día siguiente | Resumen de ventas del día a cada vendedor con pedidos |
| `dsb_weekly_low_stock` | `weekly` | Lunes 09:00 | Aviso a vendedores con productos con stock < 5 |

Los crons solo envían email si hay datos relevantes (no molestan con emails vacíos).

---

### Dashboard (`vendor-dashboard.js`)

Arquitectura tab-based:
- Tab activo persiste en memoria de sesión (no en URL, por simplicidad)
- Al entrar en tab Productos: carga lista AJAX automáticamente
- Formulario inline (no modal) con `slideDown/Up` jQuery
- Edición: extrae datos mínimos de la fila de tabla; los campos avanzados (descripción, categoría) se rellenan vacíos — intencional para Fase 2, datos completos en Fase 5

### Rewrite rules (`/tienda/{slug}/`)

```php
add_rewrite_rule('^tienda/([^/]+)/?$', 'index.php?dsb_vendor_slug=$matches[1]', 'top');
```

Registrada en `init` (siempre) y en `activate()` (antes del flush). El filtro `template_include` devuelve `public/views/vendor-store.php` si el query var existe. El template llama a `get_header()` y `get_footer()` del tema activo.

---

## 6. Fase 3

### Clase `Order` — split automático

El hook principal:

```php
add_action('woocommerce_order_status_changed', [$this, 'on_status_changed'], 10, 4);
```

Se activa cuando un pedido pasa a `completed`. El método `split_order()`:

1. **Idempotencia:** verifica que `wc_order_id` no exista ya en `dsb_vendor_orders`. Si existe, sale sin hacer nada.
2. Itera `$order->get_items()` agrupando subtotales por `post_author` del producto.
3. Para cada vendedor: calcula comisión, inserta `dsb_vendor_orders`, actualiza `balance`, inserta `dsb_transactions`, envía email.

Si el pedido pasa a `refunded` o `cancelled`: `reverse_order()` hace el proceso inverso — descuenta balance, inserta transacción de tipo `refund` con monto negativo.

### Clase `Commission` — cálculo

```php
calculate(float $subtotal, float $rate, ?object $vendor = null): [commission, vendor_earnings]
```

**Primer mes gratis:** si `$vendor->created_at` es < 30 días, `rate = 0.0`. Implementado como feature de onboarding sin intervención del admin.

```php
$commission      = round($subtotal * $rate / 100, 2);
$vendor_earnings = round($subtotal - $commission, 2);
```

Se usa `round(..., 2)` — nunca `floor` o `ceil` para no perjudicar sistemáticamente a un lado.

### Clase `Notification`

#### Emails inmediatos

- `vendor_new_order()` — al completar pedido. Enviado dentro del mismo request HTTP (síncrono, via `wp_mail`).
- `vendor_approved()` — cuando admin aprueba la tienda. Llamado desde `Vendor::update_vendor_status()`.

#### Crons registrados en activación

| Cron hook | Frecuencia | Hora | Función |
|-----------|-----------|------|---------|
| `dsb_daily_summary` | `daily` | 08:00 del día siguiente | Resumen de ventas del día a cada vendedor con pedidos |
| `dsb_weekly_low_stock` | `weekly` | Lunes 09:00 | Aviso a vendedores con productos con stock < 5 |

Los crons solo envían email si hay datos relevantes (no molestan con emails vacíos).

---

## 7. Fase 4

### REST API — Namespace `dsb/v1`

Implementada en `REST_API::register_routes()` via `rest_api_init`. Todos los endpoints públicos usan `'permission_callback' => '__return_true'`. El único endpoint autenticado es `POST /vendors/{id}/reviews`.

**Autenticación:** WP Application Passwords (nativo desde WP 5.6). Header: `Authorization: Basic base64(user:app_password)`.

### Formato de respuesta consistente

**Colecciones:**
```json
{
  "data": [...],
  "meta": {
    "total": 47,
    "pages": 4,
    "page": 1,
    "per_page": 12
  }
}
```

**Recursos individuales:**
```json
{
  "data": { ... }
}
```

**Errores:** `WP_Error` con código HTTP correcto (400, 401, 403, 404, 409, 500).

### Consideraciones de los endpoints

**`GET /search?q=`:** busca simultáneamente en productos (WP_Query con `s`) y en tiendas (`$wpdb` LIKE). El param `type` permite filtrar solo uno. Pensado para el chatbot IA de Fase 5.

**`POST /vendors/{id}/reviews`:** valida que el `wc_order_id` pertenezca al usuario autenticado (si WooCommerce está disponible). La constraint UNIQUE en BD previene duplicados aunque el check falle.

**`GET /products`:** usa `WP_Query` en lugar de `$wpdb` raw porque necesita compatibilidad con plugins de cache, filtros de terceros, y el sistema de paginación de WP. La búsqueda AJAX del frontend usa `$wpdb` raw por performance; la API REST prioriza compatibilidad.

---

## 8. Tema MarketRaw

El tema `marketraw` es un tema custom minimalista que sirve como frontend del marketplace. **No es un tema de uso general** — está diseñado específicamente para el plugin.

### Design System

Variables CSS en `:root`:
- Colores base: `--bg`, `--surface`, `--surface-2`, `--surface-3`
- Accentos: `--purple` (#7C3AED), `--orange` (#F97316), `--cyan`, `--green`, `--pink`
- Gradiente principal: `--grad-text` (purple → pink → orange)
- Sombras: `--shadow-sm`, `--shadow`, `--shadow-md`, `--shadow-lg`


### Dashboard (`vendor-dashboard.js`)

Arquitectura tab-based:
- Tab activo persiste en memoria de sesión (no en URL, por simplicidad)
- Al entrar en tab Productos: carga lista AJAX automáticamente
- Formulario inline (no modal) con `slideDown/Up` jQuery
- Edición: extrae datos mínimos de la fila de tabla; los campos avanzados (descripción, categoría) se rellenan vacíos — intencional para Fase 2, datos completos en Fase 5

### Rewrite rules (`/tienda/{slug}/`)

```php
add_rewrite_rule('^tienda/([^/]+)/?$', 'index.php?dsb_vendor_slug=$matches[1]', 'top');
```

Registrada en `init` (siempre) y en `activate()` (antes del flush). El filtro `template_include` devuelve `public/views/vendor-store.php` si el query var existe. El template llama a `get_header()` y `get_footer()` del tema activo.

---

## 6. Fase 3

### Clase `Order` — split automático

El hook principal:

```php
add_action('woocommerce_order_status_changed', [$this, 'on_status_changed'], 10, 4);
```

Se activa cuando un pedido pasa a `completed`. El método `split_order()`:

1. **Idempotencia:** verifica que `wc_order_id` no exista ya en `dsb_vendor_orders`. Si existe, sale sin hacer nada.
2. Itera `$order->get_items()` agrupando subtotales por `post_author` del producto.
3. Para cada vendedor: calcula comisión, inserta `dsb_vendor_orders`, actualiza `balance`, inserta `dsb_transactions`, envía email.

Si el pedido pasa a `refunded` o `cancelled`: `reverse_order()` hace el proceso inverso — descuenta balance, inserta transacción de tipo `refund` con monto negativo.

### Clase `Commission` — cálculo

```php
calculate(float $subtotal, float $rate, ?object $vendor = null): [commission, vendor_earnings]
```

**Primer mes gratis:** si `$vendor->created_at` es < 30 días, `rate = 0.0`. Implementado como feature de onboarding sin intervención del admin.

```php
$commission      = round($subtotal * $rate / 100, 2);
$vendor_earnings = round($subtotal - $commission, 2);
```

Se usa `round(..., 2)` — nunca `floor` o `ceil` para no perjudicar sistemáticamente a un lado.

### Clase `Notification`

#### Emails inmediatos

- `vendor_new_order()` — al completar pedido. Enviado dentro del mismo request HTTP (síncrono, via `wp_mail`).
- `vendor_approved()` — cuando admin aprueba la tienda. Llamado desde `Vendor::update_vendor_status()`.

#### Crons registrados en activación

| Cron hook | Frecuencia | Hora | Función |
|-----------|-----------|------|---------|
| `dsb_daily_summary` | `daily` | 08:00 del día siguiente | Resumen de ventas del día a cada vendedor con pedidos |
| `dsb_weekly_low_stock` | `weekly` | Lunes 09:00 | Aviso a vendedores con productos con stock < 5 |

Los crons solo envían email si hay datos relevantes (no molestan con emails vacíos).

---

## 7. Fase 4

### REST API — Namespace `dsb/v1`

Implementada en `REST_API::register_routes()` via `rest_api_init`. Todos los endpoints públicos usan `'permission_callback' => '__return_true'`. El único endpoint autenticado es `POST /vendors/{id}/reviews`.

**Autenticación:** WP Application Passwords (nativo desde WP 5.6). Header: `Authorization: Basic base64(user:app_password)`.

### Formato de respuesta consistente

**Colecciones:**
```json
{
  "data": [...],
  "meta": {
    "total": 47,
    "pages": 4,
    "page": 1,
    "per_page": 12
  }
}
```

**Recursos individuales:**
```json
{
  "data": { ... }
}
```

**Errores:** `WP_Error` con código HTTP correcto (400, 401, 403, 404, 409, 500).

### Consideraciones de los endpoints

**`GET /search?q=`:** busca simultáneamente en productos (WP_Query con `s`) y en tiendas (`$wpdb` LIKE). El param `type` permite filtrar solo uno. Pensado para el chatbot IA de Fase 5.

**`POST /vendors/{id}/reviews`:** valida que el `wc_order_id` pertenezca al usuario autenticado (si WooCommerce está disponible). La constraint UNIQUE en BD previene duplicados aunque el check falle.

**`GET /products`:** usa `WP_Query` en lugar de `$wpdb` raw porque necesita compatibilidad con plugins de cache, filtros de terceros, y el sistema de paginación de WP. La búsqueda AJAX del frontend usa `$wpdb` raw por performance; la API REST prioriza compatibilidad.

---

## 8. Tema MarketRaw

El tema `marketraw` es un tema custom minimalista que sirve como frontend del marketplace. **No es un tema de uso general** — está diseñado específicamente para el plugin.

### Design System

Variables CSS en `:root`:
- Colores base: `--bg`, `--surface`, `--surface-2`, `--surface-3`
- Accentos: `--purple` (#7C3AED), `--orange` (#F97316), `--cyan`, `--green`, `--pink`
- Gradiente principal: `--grad-text` (purple → pink → orange)
- Sombras: `--shadow-sm`, `--shadow`, `--shadow-md`, `--shadow-lg`

### Animaciones JS (`main.js`)

| Feature | Descripción |
|---------|-------------|
| Scroll progress bar | Barra de progreso en el top, actualizada en scroll |
| Nav glassmorphism | Blur + border aparece al pasar 50px de scroll |
| Mobile nav | Hamburger con animación de líneas, overlay con `body.overflow:hidden` |
| Magnetic buttons | `[data-magnetic]` — el botón se mueve hacia el cursor (strength 0.38) |
| 3D tilt cards | `[data-tilt]` — rotateX/Y según posición del cursor, max 9° |
| Counter animation | `[data-count]` — easeOut cubic desde 0 al valor target, 1600ms |
| Split text reveal | `[data-split]` — divide por palabras en `<span>`, stagger reveal |
| Intersection Observer | `.reveal` — fadeInUp al entrar en viewport |
| Hero word cycle | Ciclo de palabras con transición enter/leave en el h1 del hero |
| Blob parallax | Los blobs del hero se mueven suavemente siguiendo el ratón |
| Hero cards parallax | Las tarjetas flotantes del hero responden al movimiento del ratón |

### Overrides del plugin DSB

El tema incluye en `main.css` una sección `/* DSB PLUGIN OVERRIDES */` que adapta los estilos del plugin al design system del tema (colores, border-radius, sombras). Esto permite que el plugin funcione con cualquier tema sin estilos rotos, y que el tema propio se vea perfecto.

### Templates

| Fichero | URL | Descripción |
|---------|-----|-------------|
| `front-page.php` | `/` | Landing page del marketplace |
| `page.php` | `/marketplace/`, `/mi-tienda/`, etc. | Páginas con shortcodes del plugin |
| `index.php` | Blog fallback | Solo posts del blog |
| `header.php` | — | Nav fixed con scroll effect |
| `footer.php` | — | Footer dark con links |

---

## 9. Seguridad

### Checklist implementado

| Medida | Dónde |
|--------|-------|
| `check_ajax_referer()` en todos los handlers AJAX | `Ajax`, `Vendor`, `Admin` |
| `$wpdb->prepare()` en todas las queries | Sin excepción en todo el plugin |
| `sanitize_text_field()`, `sanitize_textarea_field()`, `sanitize_key()`, `absint()` | Todos los inputs |
| `esc_html()`, `esc_attr()`, `esc_url()` en todos los outputs | Todos los templates |
| `wp_kses_post()` para contenido HTML del vendedor | `Ajax::vendor_save_product()` |
| `current_user_can()` antes de operaciones sensibles | Todos los endpoints de vendedor y admin |
| Verificación de `post_author` antes de editar/eliminar productos | `Ajax` |
| Constraint UNIQUE en BD para reviews duplicadas | Schema |
| `'permission_callback' => '__return_true'` solo en endpoints de lectura pública | `REST_API` |
| Roles y capabilities custom | `Install::add_roles_and_caps()` |

### Roles y capabilities

| Role | Caps |
|------|------|
| `dsb_vendor` | `read`, `upload_files`, `dsb_manage_own_store`, `dsb_manage_own_products` |
| `administrator` | Todo lo anterior + `dsb_manage_marketplace`, `dsb_manage_vendors` |

---

## 10. Crons y notificaciones

### Registro

```php
// En Install::activate()
wp_schedule_event(strtotime('tomorrow 08:00:00'), 'daily', 'dsb_daily_summary');
wp_schedule_event(strtotime('next monday 09:00:00'), 'weekly', 'dsb_weekly_low_stock');

// En Install::deactivate()
wp_clear_scheduled_hook('dsb_daily_summary');
wp_clear_scheduled_hook('dsb_weekly_low_stock');
```

### wp_cron vs cron real en VPS

`wp_cron` se ejecuta en peticiones HTTP. En producción con poco tráfico, puede retrasarse. Solución: deshabilitar wp_cron y usar cron real del sistema.

En `/etc/crontab` o `crontab -e`:
```bash
# Deshabilitar wp_cron en wp-config.php
define('DISABLE_WP_CRON', true);

# Cron del sistema (cada minuto)
* * * * * www-data /usr/bin/php /var/www/html/wp-cron.php > /dev/null 2>&1
```

O con WP-CLI:
```bash
* * * * * www-data wp --path=/var/www/html cron event run --due-now > /dev/null 2>&1
```

---

## 11. Deploy en VPS

### Stack recomendado

```
Ubuntu 22.04 LTS
Nginx 1.24
PHP 8.2-FPM
MySQL 8.0
Redis 7 (object cache con wp-redis)
Let's Encrypt (Certbot)
WP-CLI
```

### Script de deploy

```bash
#!/bin/bash
set -e

WEBROOT="/var/www/marketraw"
PLUGIN_PATH="$WEBROOT/wp-content/plugins/dsb-marketplace"

# Pull latest
cd $WEBROOT
git pull origin main

# Flush cache
wp --path=$WEBROOT cache flush

# Reactivar plugin para re-run de migraciones BD si la versión cambió
CURRENT_VERSION=$(wp --path=$WEBROOT option get dsb_marketplace_version 2>/dev/null || echo "0")
PLUGIN_VERSION=$(grep "Version:" $PLUGIN_PATH/dsb-marketplace.php | awk '{print $3}')

if [ "$CURRENT_VERSION" != "$PLUGIN_VERSION" ]; then
  wp --path=$WEBROOT plugin deactivate dsb-marketplace
  wp --path=$WEBROOT plugin activate dsb-marketplace
  echo "Plugin actualizado: $CURRENT_VERSION → $PLUGIN_VERSION"
fi

# Flush rewrite rules
wp --path=$WEBROOT rewrite flush

echo "Deploy completado."
```

### Configuración Nginx para WooCommerce

```nginx
server {
    listen 443 ssl http2;
    server_name marketraw.com;

    root /var/www/marketraw;
    index index.php;

    # No cache para WooCommerce
    set $no_cache 0;
    if ($request_uri ~* "(cart|checkout|my-account|tienda)") {
        set $no_cache 1;
    }

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_cache_bypass $no_cache;
        fastcgi_no_cache $no_cache;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Assets con cache largo
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 12. Futuras mejoras

### Fase 5 (planificada) — Chatbot IA

Integración de OpenAI `gpt-4o-mini` como widget flotante en el frontend.

**Arquitectura:**
- Widget JS con textarea + botón enviar
- Petición AJAX al servidor (nunca exponer API key en frontend)
- El servidor llama a OpenAI con contexto inyectado:
  ```
  Eres asistente del marketplace MarketRaw de Granada.
  Categorías disponibles: [lista dinámica]
  Zonas: [lista dinámica]
  Rango de precios: 0 - 500€
  ```
- El modelo devuelve JSON con params de búsqueda
- El servidor llama internamente a `GET /dsb/v1/search` con esos params
- La respuesta se reformatea en lenguaje natural

**Seguridad:** la API key de OpenAI siempre en el servidor, nunca en JS ni en BD sin cifrar. Guardar en `wp-config.php` como constante.

---

### Mejoras de backend

**Sistema de pagos a vendedores (payouts)**
- Actualmente el saldo se acumula en `dsb_vendors.balance`
- Implementar botón "Solicitar pago" en dashboard → crea registro en tabla `dsb_payouts`
- Admin revisa y marca como pagado → actualiza balance y crea transacción `withdrawal`
- Integrar con Stripe Connect o transferencia bancaria manual

**Comisiones dinámicas**
- Comisión por categoría (ej: electrónica 15%, artesanía 8%)
- Comisión progresiva por volumen (más ventas → menos comisión)
- Cupones de comisión para atraer nuevos vendedores

**Sistema de valoraciones mejorado**
- Valoración media visible en tarjetas de tienda y en el listado admin
- Filtro en marketplace por valoración mínima
- Respuesta del vendedor a reseñas

**Analytics para vendedores**
- Gráfico de ventas por mes (Chart.js)
- Productos más vendidos
- Tasa de conversión (visitas / pedidos) — requiere tracking de visitas

**Carga de imágenes en dashboard**
- Actualmente el logo/banner solo se puede subir desde wp-admin (Media Library)
- Implementar uploader con WordPress Media Library en el dashboard frontend
- Usar `wp.media` JavaScript API

---


### Dashboard (`vendor-dashboard.js`)

Arquitectura tab-based:
- Tab activo persiste en memoria de sesión (no en URL, por simplicidad)
- Al entrar en tab Productos: carga lista AJAX automáticamente
- Formulario inline (no modal) con `slideDown/Up` jQuery
- Edición: extrae datos mínimos de la fila de tabla; los campos avanzados (descripción, categoría) se rellenan vacíos — intencional para Fase 2, datos completos en Fase 5

### Rewrite rules (`/tienda/{slug}/`)

```php
add_rewrite_rule('^tienda/([^/]+)/?$', 'index.php?dsb_vendor_slug=$matches[1]', 'top');
```

Registrada en `init` (siempre) y en `activate()` (antes del flush). El filtro `template_include` devuelve `public/views/vendor-store.php` si el query var existe. El template llama a `get_header()` y `get_footer()` del tema activo.

---

## 6. Fase 3

### Clase `Order` — split automático

El hook principal:

```php
add_action('woocommerce_order_status_changed', [$this, 'on_status_changed'], 10, 4);
```

Se activa cuando un pedido pasa a `completed`. El método `split_order()`:

1. **Idempotencia:** verifica que `wc_order_id` no exista ya en `dsb_vendor_orders`. Si existe, sale sin hacer nada.
2. Itera `$order->get_items()` agrupando subtotales por `post_author` del producto.
3. Para cada vendedor: calcula comisión, inserta `dsb_vendor_orders`, actualiza `balance`, inserta `dsb_transactions`, envía email.

Si el pedido pasa a `refunded` o `cancelled`: `reverse_order()` hace el proceso inverso — descuenta balance, inserta transacción de tipo `refund` con monto negativo.

### Clase `Commission` — cálculo

```php
calculate(float $subtotal, float $rate, ?object $vendor = null): [commission, vendor_earnings]
```

**Primer mes gratis:** si `$vendor->created_at` es < 30 días, `rate = 0.0`. Implementado como feature de onboarding sin intervención del admin.

```php
$commission      = round($subtotal * $rate / 100, 2);
$vendor_earnings = round($subtotal - $commission, 2);
```

Se usa `round(..., 2)` — nunca `floor` o `ceil` para no perjudicar sistemáticamente a un lado.

### Clase `Notification`

#### Emails inmediatos

- `vendor_new_order()` — al completar pedido. Enviado dentro del mismo request HTTP (síncrono, via `wp_mail`).
- `vendor_approved()` — cuando admin aprueba la tienda. Llamado desde `Vendor::update_vendor_status()`.

#### Crons registrados en activación

| Cron hook | Frecuencia | Hora | Función |
|-----------|-----------|------|---------|
| `dsb_daily_summary` | `daily` | 08:00 del día siguiente | Resumen de ventas del día a cada vendedor con pedidos |
| `dsb_weekly_low_stock` | `weekly` | Lunes 09:00 | Aviso a vendedores con productos con stock < 5 |

Los crons solo envían email si hay datos relevantes (no molestan con emails vacíos).

---

## 7. Fase 4

### REST API — Namespace `dsb/v1`

Implementada en `REST_API::register_routes()` via `rest_api_init`. Todos los endpoints públicos usan `'permission_callback' => '__return_true'`. El único endpoint autenticado es `POST /vendors/{id}/reviews`.

**Autenticación:** WP Application Passwords (nativo desde WP 5.6). Header: `Authorization: Basic base64(user:app_password)`.

### Formato de respuesta consistente

**Colecciones:**
```json
{
  "data": [...],
  "meta": {
    "total": 47,
    "pages": 4,
    "page": 1,
    "per_page": 12
  }
}
```

**Recursos individuales:**
```json
{
  "data": { ... }
}
```

**Errores:** `WP_Error` con código HTTP correcto (400, 401, 403, 404, 409, 500).

### Consideraciones de los endpoints

**`GET /search?q=`:** busca simultáneamente en productos (WP_Query con `s`) y en tiendas (`$wpdb` LIKE). El param `type` permite filtrar solo uno. Pensado para el chatbot IA de Fase 5.

**`POST /vendors/{id}/reviews`:** valida que el `wc_order_id` pertenezca al usuario autenticado (si WooCommerce está disponible). La constraint UNIQUE en BD previene duplicados aunque el check falle.

**`GET /products`:** usa `WP_Query` en lugar de `$wpdb` raw porque necesita compatibilidad con plugins de cache, filtros de terceros, y el sistema de paginación de WP. La búsqueda AJAX del frontend usa `$wpdb` raw por performance; la API REST prioriza compatibilidad.

---

## 8. Tema MarketRaw

El tema `marketraw` es un tema custom minimalista que sirve como frontend del marketplace. **No es un tema de uso general** — está diseñado específicamente para el plugin.

### Design System

Variables CSS en `:root`:
- Colores base: `--bg`, `--surface`, `--surface-2`, `--surface-3`
- Accentos: `--purple` (#7C3AED), `--orange` (#F97316), `--cyan`, `--green`, `--pink`
- Gradiente principal: `--grad-text` (purple → pink → orange)
- Sombras: `--shadow-sm`, `--shadow`, `--shadow-md`, `--shadow-lg`

### Animaciones JS (`main.js`)

| Feature | Descripción |
|---------|-------------|
| Scroll progress bar | Barra de progreso en el top, actualizada en scroll |
| Nav glassmorphism | Blur + border aparece al pasar 50px de scroll |
| Mobile nav | Hamburger con animación de líneas, overlay con `body.overflow:hidden` |
| Magnetic buttons | `[data-magnetic]` — el botón se mueve hacia el cursor (strength 0.38) |
| 3D tilt cards | `[data-tilt]` — rotateX/Y según posición del cursor, max 9° |
| Counter animation | `[data-count]` — easeOut cubic desde 0 al valor target, 1600ms |
| Split text reveal | `[data-split]` — divide por palabras en `<span>`, stagger reveal |
| Intersection Observer | `.reveal` — fadeInUp al entrar en viewport |
| Hero word cycle | Ciclo de palabras con transición enter/leave en el h1 del hero |
| Blob parallax | Los blobs del hero se mueven suavemente siguiendo el ratón |
| Hero cards parallax | Las tarjetas flotantes del hero responden al movimiento del ratón |

### Overrides del plugin DSB

El tema incluye en `main.css` una sección `/* DSB PLUGIN OVERRIDES */` que adapta los estilos del plugin al design system del tema (colores, border-radius, sombras). Esto permite que el plugin funcione con cualquier tema sin estilos rotos, y que el tema propio se vea perfecto.

### Templates

| Fichero | URL | Descripción |
|---------|-----|-------------|
| `front-page.php` | `/` | Landing page del marketplace |
| `page.php` | `/marketplace/`, `/mi-tienda/`, etc. | Páginas con shortcodes del plugin |
| `index.php` | Blog fallback | Solo posts del blog |
| `header.php` | — | Nav fixed con scroll effect |
| `footer.php` | — | Footer dark con links |

---

## 9. Seguridad

### Checklist implementado

| Medida | Dónde |
|--------|-------|
| `check_ajax_referer()` en todos los handlers AJAX | `Ajax`, `Vendor`, `Admin` |
| `$wpdb->prepare()` en todas las queries | Sin excepción en todo el plugin |
| `sanitize_text_field()`, `sanitize_textarea_field()`, `sanitize_key()`, `absint()` | Todos los inputs |
| `esc_html()`, `esc_attr()`, `esc_url()` en todos los outputs | Todos los templates |
| `wp_kses_post()` para contenido HTML del vendedor | `Ajax::vendor_save_product()` |
| `current_user_can()` antes de operaciones sensibles | Todos los endpoints de vendedor y admin |
| Verificación de `post_author` antes de editar/eliminar productos | `Ajax` |
| Constraint UNIQUE en BD para reviews duplicadas | Schema |
| `'permission_callback' => '__return_true'` solo en endpoints de lectura pública | `REST_API` |
| Roles y capabilities custom | `Install::add_roles_and_caps()` |

### Roles y capabilities

| Role | Caps |
|------|------|
| `dsb_vendor` | `read`, `upload_files`, `dsb_manage_own_store`, `dsb_manage_own_products` |
| `administrator` | Todo lo anterior + `dsb_manage_marketplace`, `dsb_manage_vendors` |

---

## 10. Crons y notificaciones

### Registro

```php
// En Install::activate()
wp_schedule_event(strtotime('tomorrow 08:00:00'), 'daily', 'dsb_daily_summary');
wp_schedule_event(strtotime('next monday 09:00:00'), 'weekly', 'dsb_weekly_low_stock');

// En Install::deactivate()
wp_clear_scheduled_hook('dsb_daily_summary');
wp_clear_scheduled_hook('dsb_weekly_low_stock');
```

### wp_cron vs cron real en VPS

`wp_cron` se ejecuta en peticiones HTTP. En producción con poco tráfico, puede retrasarse. Solución: deshabilitar wp_cron y usar cron real del sistema.

En `/etc/crontab` o `crontab -e`:
```bash
# Deshabilitar wp_cron en wp-config.php
define('DISABLE_WP_CRON', true);

# Cron del sistema (cada minuto)
* * * * * www-data /usr/bin/php /var/www/html/wp-cron.php > /dev/null 2>&1
```

O con WP-CLI:
```bash
* * * * * www-data wp --path=/var/www/html cron event run --due-now > /dev/null 2>&1
```

---

## 11. Deploy en VPS

### Stack recomendado

```
Ubuntu 22.04 LTS
Nginx 1.24
PHP 8.2-FPM
MySQL 8.0
Redis 7 (object cache con wp-redis)
Let's Encrypt (Certbot)
WP-CLI
```

### Script de deploy

```bash
#!/bin/bash
set -e

WEBROOT="/var/www/marketraw"
PLUGIN_PATH="$WEBROOT/wp-content/plugins/dsb-marketplace"

# Pull latest
cd $WEBROOT
git pull origin main

# Flush cache
wp --path=$WEBROOT cache flush

# Reactivar plugin para re-run de migraciones BD si la versión cambió
CURRENT_VERSION=$(wp --path=$WEBROOT option get dsb_marketplace_version 2>/dev/null || echo "0")
PLUGIN_VERSION=$(grep "Version:" $PLUGIN_PATH/dsb-marketplace.php | awk '{print $3}')

if [ "$CURRENT_VERSION" != "$PLUGIN_VERSION" ]; then
  wp --path=$WEBROOT plugin deactivate dsb-marketplace
  wp --path=$WEBROOT plugin activate dsb-marketplace
  echo "Plugin actualizado: $CURRENT_VERSION → $PLUGIN_VERSION"
fi

# Flush rewrite rules
wp --path=$WEBROOT rewrite flush

echo "Deploy completado."
```

### Configuración Nginx para WooCommerce

```nginx
server {
    listen 443 ssl http2;
    server_name marketraw.com;

    root /var/www/marketraw;
    index index.php;

    # No cache para WooCommerce
    set $no_cache 0;
    if ($request_uri ~* "(cart|checkout|my-account|tienda)") {
        set $no_cache 1;
    }

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_cache_bypass $no_cache;
        fastcgi_no_cache $no_cache;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Assets con cache largo
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 12. Futuras mejoras

### Fase 5 (planificada) — Chatbot IA

Integración de OpenAI `gpt-4o-mini` como widget flotante en el frontend.

**Arquitectura:**
- Widget JS con textarea + botón enviar
- Petición AJAX al servidor (nunca exponer API key en frontend)
- El servidor llama a OpenAI con contexto inyectado:
  ```
  Eres asistente del marketplace MarketRaw de Granada.
  Categorías disponibles: [lista dinámica]
  Zonas: [lista dinámica]
  Rango de precios: 0 - 500€
  ```
- El modelo devuelve JSON con params de búsqueda
- El servidor llama internamente a `GET /dsb/v1/search` con esos params
- La respuesta se reformatea en lenguaje natural

**Seguridad:** la API key de OpenAI siempre en el servidor, nunca en JS ni en BD sin cifrar. Guardar en `wp-config.php` como constante.

---

### Mejoras de backend

**Sistema de pagos a vendedores (payouts)**
- Actualmente el saldo se acumula en `dsb_vendors.balance`
- Implementar botón "Solicitar pago" en dashboard → crea registro en tabla `dsb_payouts`
- Admin revisa y marca como pagado → actualiza balance y crea transacción `withdrawal`
- Integrar con Stripe Connect o transferencia bancaria manual

**Comisiones dinámicas**
- Comisión por categoría (ej: electrónica 15%, artesanía 8%)
- Comisión progresiva por volumen (más ventas → menos comisión)
- Cupones de comisión para atraer nuevos vendedores

**Sistema de valoraciones mejorado**
- Valoración media visible en tarjetas de tienda y en el listado admin
- Filtro en marketplace por valoración mínima
- Respuesta del vendedor a reseñas

**Analytics para vendedores**
- Gráfico de ventas por mes (Chart.js)
- Productos más vendidos
- Tasa de conversión (visitas / pedidos) — requiere tracking de visitas

**Carga de imágenes en dashboard**
- Actualmente el logo/banner solo se puede subir desde wp-admin (Media Library)
- Implementar uploader con WordPress Media Library en el dashboard frontend
- Usar `wp.media` JavaScript API

---

### Mejoras de frontend y UX

**Paginación por URL** en el marketplace
- Actualmente solo "cargar más"
- Añadir parámetros de URL (`?page=2&cat=alimentacion`) para que las URLs sean compartibles y el SEO mejore

**Búsqueda con autocompletado**
- Sugerencias en tiempo real (debounce 200ms) mostrando los primeros 5 resultados
- Historial de búsquedas en localStorage

**Mapa de tiendas**
- Integrar Leaflet.js (libre) con OpenStreetMap
- Geocodificar la dirección del vendedor al registrarse
- Mostrar pines en el mapa filtrados por las búsquedas activas

**Modo oscuro**
- El tema ya tiene el design system en CSS variables
- Añadir clase `.dark` al `<html>` con media query `prefers-color-scheme`
- Toggle manual persistido en localStorage

**PWA (Progressive Web App)**
- Service worker para cache offline de assets
- Manifest.json para instalación en móvil
- Push notifications con Notification API para nuevos pedidos

---

### Mejoras de rendimiento

**Cache de queries frecuentes**
- `get_vendors()`, listado de categorías: cachear con `wp_cache_set()` / `wp_cache_get()`
- Invalidar cache cuando se aprueba/suspende una tienda

**Redis Object Cache**
- Plugin `wp-redis` en VPS para que el object cache de WP persista entre requests
- Reducción de queries a BD en ~60%

**Imágenes**
- Generar thumbnails WebP automáticamente en subida
- Lazy loading nativo (`loading="lazy"`) ya implementado en tarjetas de producto

**Índices adicionales**
- Si el marketplace crece mucho: añadir índice compuesto `(post_type, post_status, post_author)` en `wp_posts`
- Índice en `dsb_vendor_orders.created_at` para queries del cron diario

---

### Mejoras de seguridad y compliance

**Rate limiting en AJAX y REST API**
- Limitar peticiones a `dsb_search` a 60/min por IP con `wp_check_request_limit()` (custom)
- Evitar scraping masivo

**GDPR / LOPD**
- Anonimizar datos de usuarios eliminados en `dsb_vendor_reviews`
- Endpoint de exportación de datos del vendedor (WP ya tiene API para esto)
- Política de retención de datos en `dsb_transactions`

**2FA para vendedores**
- Integrar con plugin Two-Factor (jetpack/two-factor)
- Obligatorio para acceder al dashboard si balance > 500€

**Auditoría de cambios**
- Log de cambios de estado de tiendas (quién aprobó, cuándo)
- Log de cambios de commission_rate por admin

---

### Testing

**Unit tests (PHPUnit)**
- `Commission::calculate()` — casos límite (rate=0, rate=100, primer mes)
- `Vendor::generate_unique_slug()` — colisiones
- `Order::split_order()` — idempotencia, pedidos multi-vendor

**Integration tests**
- Flujo completo de registro → aprobación → producto → pedido → split
- Reembolso y reversión de balance

**E2E (Playwright / Cypress)**
- Flujo de compra completo en staging
- Dashboard vendedor: CRUD productos

---

## 13. Referencia REST

Base URL: `https://[dominio]/wp-json/dsb/v1/`

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/vendors` | No | Lista tiendas activas paginadas |
| GET | `/vendors/{id}` | No | Detalle de tienda con rating |
| GET | `/vendors/{id}/products` | No | Productos de una tienda |
| GET | `/vendors/{id}/reviews` | No | Reseñas de una tienda |
| POST | `/vendors/{id}/reviews` | Sí | Crear reseña (comprador verificado) |
| GET | `/products` | No | Lista productos con filtros |
| GET | `/search?q=texto` | No | Búsqueda full-text en productos y tiendas |

**Parámetros comunes de paginación:** `page` (default: 1), `per_page` (default: 20, max: 100)

**Parámetros de `/products`:** `q`, `cat` (slug), `zone` (slug), `min_price`, `max_price`

**Parámetros de `/search`:** `q` (requerido, min 2 chars), `type` (all/products/vendors), `per_page`

---

## 14. Referencia AJAX

Base: `POST /wp-admin/admin-ajax.php`

### Público (sin autenticación)

| Action | Nonce | Params | Descripción |
|--------|-------|--------|-------------|
| `dsb_search` | `dsb_public_nonce` | `q, category, zone, min_price, max_price, page` | Búsqueda de productos |

### Vendedor (requiere login + tienda activa)

| Action | Nonce | Params | Descripción |
|--------|-------|--------|-------------|
| `dsb_register_vendor` | `dsb_vendor_register_nonce` | `store_name, description, address, phone` | Registro de tienda |
| `dsb_vendor_get_products` | `dsb_vendor_nonce` | — | Lista productos propios |
| `dsb_vendor_save_product` | `dsb_vendor_nonce` | `product_id, title, content, price, stock, category, zone` | Crear/editar producto |
| `dsb_vendor_delete_product` | `dsb_vendor_nonce` | `product_id` | Eliminar producto |
| `dsb_vendor_update_settings` | `dsb_vendor_nonce` | `store_name, description, address, phone` | Actualizar tienda |

### Admin (requiere `dsb_manage_vendors`)

| Action | Nonce | Params | Descripción |
|--------|-------|--------|-------------|
| `dsb_update_vendor_status` | `dsb_admin_nonce` | `vendor_id, status` | Aprobar/suspender tienda |

---

## 15. Hooks

### Actions propios del plugin

| Hook | Cuándo | Parámetros |
|------|--------|-----------|
| `dsb_vendor_registered` | Al crear una tienda | `$vendor_id, $user_id` |
| `dsb_vendor_approved` | Al aprobar una tienda | `$vendor_id` |
| `dsb_order_split` | Al dividir un pedido | `$wc_order_id, $vendor_orders` |
| `dsb_daily_summary` | Cron diario 08:00 | — |
| `dsb_weekly_low_stock` | Cron semanal lunes 09:00 | — |

> **Nota:** Los hooks listados se disparan internamente pero aún no están implementados con `do_action()` en v1.0.0. Están planificados para v1.1.0 para permitir extensibilidad sin tocar el plugin core.

### Filtros útiles para extensión

Planificados para v1.1.0:

```php
// Modificar comisión antes de calcular
apply_filters('dsb_commission_rate', $rate, $vendor, $order);

// Modificar datos del producto en la REST API
apply_filters('dsb_rest_product_data', $data, $post_id);

// Añadir campos al formulario de registro de tienda
do_action('dsb_vendor_register_form_fields');

// Ejecutar código después del split de pedido
do_action('dsb_after_order_split', $wc_order_id, $vendor_orders);
```

---

*Documentación generada para v1.0.0. Última actualización: Fase 4 completada.*
