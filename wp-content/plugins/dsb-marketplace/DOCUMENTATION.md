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
