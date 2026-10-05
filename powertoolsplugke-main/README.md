# PowerPlug — Premium WooCommerce Theme

Enterprise-grade WooCommerce parent theme for industrial / power-tool retailers.

## Requirements
- PHP 8.0+, WordPress 6.4+, WooCommerce 8.0+

## Install
1. Upload `powerplug.zip` via Appearance → Themes → Add New → Upload.
2. Also upload and activate `powerplug-child.zip` (recommended — customizations live here).
3. On activation you are redirected to **Appearance → PowerPlug Setup** (the wizard).
4. Wizard steps: Welcome → Install Plugins → Activate → Import Demo → Finish.

## Architecture
- `functions.php` — PSR-4 autoloader + boots `PowerPlug\Core\Theme`.
- `inc/Core/` — container (`Theme`), `Bootable` contract, `Assets`.
- `inc/Setup/` — `Supports`, `Menus`, `Setup_Wizard`, `Plugin_Installer`, `Demo_Importer`.
- `inc/Woo/` — WooCommerce presentation (badges, trust, sticky ATC, specs tab).
- `inc/Seo/` — `Schema` (JSON-LD) + `Meta` (OG/Twitter), yields to Yoast/Rank Math.
- `inc/Performance/` — Core Web Vitals `Optimizer`.
- `inc/Security/` — OWASP `Headers`.
- `inc/Admin/` — `Dashboard` control-center with health scores.
- `theme.json` — global design system (colors, fluid type, spacing, dark mode).
- `assets/` — compiled `main.css`, `app.js`, wizard CSS/JS (framework-free).
- `demo/` — importable JSON (categories, products, pages).

## Landing page funnels
- `inc/Front/LandingPage.php` + `template-lp-category.php` power the ad funnels at `/lp-{category}/`.
- Since 2.23.0 every `/lp-{slug}/` URL renders the funnel automatically (no page needed); published `lp-` pages are forced onto the Ads template and their settings box still applies. See the repository root README for the full funnel list and troubleshooting.
- Map a URL to a differently named category with the `powerplug_funnel_aliases` filter.

## Extending
Never edit the parent. Use the child theme and these filters:
- `powerplug_business_details` — override store phone/email/address.
- `powerplug_health_scores` — supply dashboard metrics.
