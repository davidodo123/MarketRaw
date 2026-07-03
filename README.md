# MarketRaw

Marketplace multi-vendor para negocios locales de Granada, construido como plugin WordPress custom (`dsb-marketplace`) + tema custom (`marketraw`), sobre WooCommerce como motor de pagos y carrito.

No usa Dokan, WC Vendors ni ningún plugin de marketplace de terceros — toda la lógica de tiendas, comisiones, split de pedidos, búsqueda y chatbot IA está escrita desde cero.

---

## Índice

- [Stack](#stack)
- [Funcionalidades](#funcionalidades)
- [Arquitectura](#arquitectura)
- [Instalación local](#instalación-local)
- [Configuración](#configuración)
- [Seguridad](#seguridad)
- [Roadmap](#roadmap)
- [Licencia](#licencia)

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.x, WordPress 6.x, WooCommerce 7+ |
| Base de datos | MySQL 8 (4 tablas custom vía `$wpdb`/`dbDelta`) |
| Frontend | Vanilla JS + jQuery (admin-ajax), sin build step / sin npm |
| IA | Google Gemini (`gemini-2.5-flash-lite`) para el chatbot del marketplace |
| Plugin | `dsb-marketplace` — namespace `DSB\Marketplace`, autoloader por mapa explícito |
| Tema | `marketraw` — diseño editorial propio (papel/tinta/granate, serif Fraunces), animaciones JS sutiles con `prefers-reduced-motion` |

Sin dependencias de build (Webpack/Vite/npm): todo el JS/CSS del plugin y del tema es código plano servido directamente por WordPress.

---

## Funcionalidades

- [x] **Fase 1** — Skeleton del plugin, 4 tablas BD, CPT `dsb_product` + taxonomías `dsb_category`/`dsb_zone`, rol `dsb_vendor`, registro de tiendas (AJAX + nonce)
- [x] **Fase 2** — Dashboard de vendedor (tabs: productos/pedidos/configuración), búsqueda AJAX con filtros (`$wpdb` raw), página pública `/tienda/{slug}/`, sistema de reseñas
- [x] **Fase 3** — Split automático de pedidos WooCommerce por vendedor, cálculo de comisiones (con primer mes gratis), notificaciones por email, crons (resumen diario, stock bajo semanal)
- [x] **Fase 4** — API REST (`/wp-json/dsb/v1/*`): vendors, productos, reseñas, búsqueda full-text, auth vía WP Application Passwords
- [x] **Fase 5** — Chatbot IA flotante: lenguaje natural → búsqueda estructurada (Gemini decide intención, parámetros y si busca productos o una tienda concreta; el servidor ejecuta la búsqueda real y nunca permite que el modelo invente productos, tiendas o precios)
- [x] Página de login/registro custom (`/cuenta/`) — reemplaza wp-login.php, AJAX propio con `wp_signon`/`wp_insert_user`
- [ ] Admin panel completo (`WP_List_Table`, vista de payouts)
- [ ] Sistema de pagos a vendedores (payouts)
- [ ] Testing automatizado (PHPUnit)
- [ ] Deploy en VPS propio

Detalle técnico completo de cada fase, schema de BD, endpoints y decisiones de diseño: [`wp-content/plugins/dsb-marketplace/DOCUMENTATION.md`](wp-content/plugins/dsb-marketplace/DOCUMENTATION.md).

---

## Arquitectura

```
wp-content/
├── plugins/dsb-marketplace/
│   ├── includes/      → Core (singleton), Install, Vendor, Auth, Product, Order,
│   │                     Commission, Notification, REST_API, Chatbot, Ajax
│   ├── admin/          → Menú admin, vistas (dashboard/vendors/orders/settings)
│   └── public/         → Shortcodes, rewrite rules, vistas, assets (js/css)
└── themes/marketraw/
    ├── front-page.php  → Landing del marketplace
    ├── page.php         → Páginas con shortcodes del plugin
    └── assets/          → main.css (design system), main.js (animaciones)
```

**Principios del plugin:** una clase por fichero, autoloader por mapa explícito (sin magic), `$wpdb->prepare()` sin excepciones, nonces en todo AJAX, ownership check (`post_author`) antes de editar/eliminar contenido de vendedor.

Páginas auto-creadas en la activación del plugin: `/marketplace/`, `/mi-tienda/`, `/crear-mi-tienda/`, `/cuenta/`, y `/tienda/{slug}/` (rewrite rule, no es página de WP).

---

## Instalación local

Pensado para [Local](https://localwp.com/) (WP Engine) u otro entorno WordPress local equivalente.

1. Clona el repo dentro de la carpeta `app/public` de tu sitio local:
   ```bash
   git clone https://github.com/davidodo123/MarketRaw.git
   ```
2. Crea `wp-config.php` a partir de `wp-config-sample.php` con las credenciales de tu BD local (este fichero **no** está versionado — ver [Configuración](#configuración)).
3. Activa WordPress, luego **WooCommerce** (requerido — el plugin no arranca sin él).
4. Activa el plugin **DSB Marketplace** y el tema **MarketRaw**.
5. En la activación del plugin se crean automáticamente las tablas, roles, páginas y rewrite rules.

---

## Configuración

`wp-config.php` no se versiona (está en `.gitignore`) porque lleva credenciales de entorno. Constantes propias del plugin que puedes definir ahí:

```php
// Activa el widget del chatbot IA. Sin esta constante, el widget no se encola ni se renderiza.
define( 'DSB_GEMINI_API_KEY', 'tu-api-key-de-Google-AI-Studio' );
```

---

## Seguridad

- `check_ajax_referer()` en todos los handlers AJAX, `$wpdb->prepare()` en toda query con input dinámico.
- `sanitize_*()` en input, `esc_*()` en output, `wp_kses_post()` para contenido HTML de vendedor.
- Verificación de `post_author` antes de editar/eliminar productos de otro vendedor.
- Claves de APIs externas (Gemini) solo como constante de servidor — nunca en JS ni en BD.
- Rate limiting (ventana deslizante 20 msg/15 min) en el chatbot, por ser la única llamada a una API de pago externa del proyecto.

Checklist completo en `DOCUMENTATION.md` §10.

---

## Roadmap

Lo más relevante pendiente, por orden de prioridad:

1. Sistema de payouts (tabla `dsb_payouts`, botón "solicitar pago")
2. Admin panel con `WP_List_Table` y vista de pagos pendientes
3. Testing con PHPUnit (`Commission::calculate`, `Order::split_order` idempotencia)
4. Deploy en VPS (Nginx + PHP-FPM + MySQL + Redis + Let's Encrypt)
5. Mejoras de UX: paginación por URL, autocompletado, mapa de tiendas, modo oscuro

Lista extendida (26 mejoras en 5 categorías) en `DOCUMENTATION.md` §13.

---

## Licencia

GPL-2.0+

---

Desarrollado por **David Santiago Ybermúdez**.
