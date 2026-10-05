## 2.20.1
- Footer now links Warranty Policy, FAQ and Track Order (in addition to About, Returns, Shipping, Terms, Privacy, Contact).
- New shortcode [pp_faq] renders the store FAQ (with FAQ schema) on any page, so you can publish a dedicated FAQ page.
- New shortcode [pp_business_info] renders a Business Information block (business name, registered in Kenya, physical shop, phone, email, hours, nationwide delivery, M-Pesa & Pay on Delivery) for the About page, pulling details from the Customizer so they stay consistent.
## 2.20.0
- Trust & compliance pack for Google Merchant reinstatement. New shortcodes you can place on any page: [pp_trust_badges] (assurance badges), [pp_why_buy] (why buy from us), [pp_store_gallery] (real photos of the physical shop), [pp_store_map] (Google map + directions to the shop).
- Site-wide trust bar added above the footer (Genuine Products, Warranty-Backed, Secure SSL Checkout, Fast Countrywide Delivery, Pay on Delivery, M-Pesa, Physical Shop in Nairobi).
- Bundled two real shop photos into the theme (used by [pp_store_gallery]).
- Accuracy fix: the footer text and homepage "Shop by Brand" chips are now driven by a new Customizer field "Brands you stock" (default Total, Ingco, Maxmech, DCA) instead of hardcoded Makita/Bosch/DeWalt/Honda, so the site only claims brands you genuinely stock.
## 2.19.3
- Landing page funnels on mobile: product grid now shows 2 products per row (was 1), so customers see more models and reach an Order button with far less scrolling. On these narrower cards the Order now / WhatsApp buttons stack vertically and padding/fonts are tightened for a clean fit. Tablets/desktop unchanged (2 and 3 per row).
## 2.19.2
- Landing page funnels: new "Hero From price" field on each landing page. Type your own number (e.g. 3999 shows as KSh 3,999.00) to control the "From" price in the hero, or leave it blank.
- When blank, the hero now automatically shows the lowest price among the funnel's products (previously it used the first product's price, which after setting custom product IDs was not always the cheapest).
## 2.19.1
- Added built-in hero images for the three new funnels (Demolition Breakers, Pressure Washers, Vacuum Cleaners) in the same studio style as the other nine, with full jpg + webp responsive variants. These funnels now look complete out of the box; you can still override with your own product photo via the Hero image URL field.
## 2.19.0
- Landing page funnels: new "Advertised product IDs" box on each landing page. Enter comma-separated WooCommerce product IDs and exactly those products show, in that order — leave blank to auto-show the category. Change it anytime to rotate which items you advertise.
- Added tailored copy for three new funnels: Demolition Breakers, Pressure Washers, Vacuum Cleaners. Funnels without a hero photo now show a clean branded panel instead of a wrong stock image; set a real photo per page via the Hero image URL field.
- Homepage: new Customizer field "Shop by Category cards (slugs)" to control exactly which category cards appear at the top of the homepage, in your order. Leave blank to auto-pick the most popular.
## 2.18.4
- Homepage category rows show a fresh random mix on every reload again (default order set back to Random). Because the homepage is full-page cached by LiteSpeed, the rows now render a small pool and shuffle it in the browser on each load — so it stays lively without bypassing cache or adding server CPU.
- Still switchable in Customizer > Homepage > Product order in homepage rows (Random / Latest first / Best selling / Price).
## 2.18.3
- Product page rebuilt to match the tabarak layout. On mobile it now collapses to one clean column, fixing the clipped price and the scattered title/badges (a v2.17.0 rule had kept it two-column on phones).
- Desktop: the product image now fills its column instead of sitting small in a half-empty space.
- Added an inline "Order on WhatsApp" button beside Add to cart on the single product page (prefilled with the product name + link).
- Delivery note moved into the summary column (was floating full-width, disconnected); trust badges shown as a compact 2-up grid.

## 2.18.2
- Admin Products list cleaned up: restricted to a compact column set (image, name, SKU, stock, price, categories, featured, date) with constrained 44px thumbnails. This removes the crushed one-character-wide third-party columns (Rank Math "SEO Details", Brands, catalog-sync, GTIN, tags) that were inflating rows into huge empty gaps.
- Homepage category rows now default to "Latest first" instead of "Random", so products no longer reshuffle on every page reload. (Still switchable in Customizer > Homepage > Product order in homepage rows.)

## 2.18.1
- Floating WhatsApp button is now a compact circular icon (was a wide pill that overlapped content on mobile).
- Restored horizontal page gutters on mobile (a v2.18.0 padding rule had pushed content to the screen edge).
- Floating WhatsApp + back-to-top buttons now sit clear of the mobile bottom nav.
- Checkout: fixed mobile horizontal overflow and cleaned the "returning customer / coupon" panels; tidier checkboxes and spacing for an international look.

## 2.18.0
- WhatsApp "Order" button label reduced to 12px (tidier on product cards).
- Content pages (About, policies, Contact) now render in a centered, readable column with upgraded typography, tables, and callout styling.
- Checkout refined: contained/centered width, boxed order summary, card-style payment methods.
- New Customizer control (Homepage): "Homepage category rows (slugs)" — choose exactly which categories appear as product rows below Featured Categories, in your chosen order.
- Security hardening: blocked username enumeration (?author= and REST /users for guests), removed pingback/XML-RPC amplification vectors, stripped RSD/WLW/shortlink head meta.
- Normalized store-location microcopy to Tom Mboya St, Nairobi.

## 2.17.0
- Product page: premium buy-box card, sticky gallery, check-card trust badges, stronger price/CTA styling.
- Editable "Order on WhatsApp" button on product cards (replaces Quick view). Number + message set in Customizer > PowerPlug Pro > Header & Contact; nothing hardcoded.
- AJAX add-to-cart on single product pages (SIMPLE products only) with slide-in mini-cart; variable/grouped products keep the standard flow to avoid plugin conflicts (variation swatches, etc.).
- Cart: international-standard table, sticky totals card, fixed underlined checkout button (classic + block).
- Checkout: proper aligned two-column layout (details left, sticky order summary right), full-width fields, prominent Place order button.
- Removed hardcoded WhatsApp numbers from homepage hero + newsletter fallback (now read the Customizer value).

## 2.16.0 - 2026-07-16
- Premium visual layer for landing pages: button micro-interactions, hero polish, product-card image zoom, benefit-card accents, animated announcement sheen, reveal stagger.
- Added scroll progress bar and an HONEST exit-intent nudge (order form / WhatsApp, no fake discount), once per session.
- Added inert scaffolding classes (rating, save badge, low-stock, reviews, FABs, minimal header) for the upcoming Phase 1b sections. No PHP changed in this release.

## 2.15.12 - 2026-07-16
- Landing template now auto-detects its product category from the page slug (e.g. lp-water-pumps loads Water Pumps) when the meta-box dropdown is left blank, so ad pages work without the extra selection step.
- Meta-box blank option relabelled to 'Auto-detect from the page slug'.
- Manual dropdown selection and per-page overrides still take priority.

## 2.15.11 - 2026-07-16
- Landing template now supports 8 more ad categories out of the box (Water Pumps, Hardware Tools, Weighing Scales, Batteries, Welding Machines, Solar Panels, Solar Inverters, Grinders).
- Added bundled responsive per-category hero images (WebP + JPG fallback at 768/1024/1376) resolved automatically by the selected product category.
- Added tailored per-category hero heading and sub-heading defaults; still overridable per page in the meta box.
- Benefits section now shows an honest store-wide default set on every landing category, not just incubators.

# Changelog - PowerPlug Pro

All notable changes to this theme are documented here.
This project follows semantic versioning (MAJOR.MINOR.PATCH).

## 2.15.10

- Landing pages (mobile): fixed the order form running off the right edge. The Incubator-size dropdown has very long option labels, which forced the CSS grid column wider than the phone screen; added min-width:0 and full-width sizing so the fields stay within the screen.
- Landing pages: corrected the template body-class selector (WordPress outputs page-template-template-lp-category-php), so the mobile fixes now actually apply - no horizontal scroll, more bottom clearance so the sticky bar no longer overlaps the footer, the redundant floating WhatsApp pill is hidden on landing pages, and the back-to-top button sits above the bar.

## 2.15.9

- Landing pages (mobile): the sticky bottom buy bar buttons now show their colours again (green WhatsApp, orange "Order Now - Pay on Delivery") - they had been rendering as plain text because the button styles were scoped to the page wrapper the bar sits outside of. Added box-sizing so the bar cannot overflow.
- Landing pages (mobile): removed horizontal overflow (the extra space on the right) with overflow-x clipping on the template, hid the theme's redundant floating WhatsApp pill on landing pages (the page already has its own WhatsApp CTAs and sticky bar, and the pill was being cut off), and lifted the back-to-top button above the sticky bar so nothing overlaps.

## 2.15.8

- Landing pages: the "Everything you need to start" section now shows a real what-is-included image on the right instead of an empty placeholder. Ships a bundled, optimized incubator accessories photo (WebP + responsive) used by default on the incubators category, with a new optional "What-is-included image URL" field in the Landing Page (Ads) settings box to override it per page. Non-incubator categories fall back gracefully.

- Landing pages: product-card "Order on WhatsApp" buttons now pre-fill the message with the product URL first (so WhatsApp shows a link preview of the exact product), then a space, then the order request text.

## 2.15.7

- Landing pages: added a "Landing Page - Category (Ads)" page template for Meta (Facebook/Instagram) ad traffic. It pulls live WooCommerce products (price, stock, image) from a chosen category and drives the real cart/checkout (Cash on Delivery) plus a pre-filled WhatsApp order. Configure the category, hero heading/subheading, hero image, announcement text, benefits and what's-included from the new "Landing Page (Ads) settings" box on any Page. One template powers every category page; defaults are tuned for the Incubators category. Ships a bundled, optimized incubator hero image (WebP + responsive sizes). Honest by design: no fabricated reviews, ratings, countdowns or fake scarcity, so it stays Merchant Center and Meta ad-policy safe.

## 2.15.6

- Homepage: added a Priority Category setting (Customizer > PowerPlug Pro > Homepage Sections) that pins a chosen category first across the Shop by Category scroller, hero category menu, and category product sections. Defaults to the 'incubators' slug so the new Incubators category shows first (before Water Pumps). If the category is not in the top set by product count it is fetched and placed first automatically. Leave the field blank to order by popularity.

## 2.15.5

- Delivery: updated the same-day order cut-off from 2:00 PM to 5:00 PM (Nairobi deliveries run until 6:00 PM). Applied to the product-page delivery estimate, the homepage 'Why choose us' point, and the Shipping & Delivery Policy page.

## 2.15.4

- Compliance/trust: removed the Visa and Mastercard labels from the footer so the site only advertises the payment methods you actually accept (M-Pesa and Pay on Delivery). Removes a payment-misrepresentation signal.
- Compliance/links: the footer Shop column now lists your real, non-empty product categories (pulled live and cached) plus All Products, instead of hard-coded links that could point to empty or non-existent category pages.
- Reviews: added an optional Google Business Profile URL (Customizer -> PowerPlug Pro -> Header & Contact). When set, a 'Read our reviews on Google' link appears in the reviews section - a safe way to surface genuine reputation.

## 2.15.3

- Performance: added a version-keyed transient cache for the homepage category menus, hero/section category lists, storefront filter terms and customer reviews. Caches auto-flush when products, categories or reviews change, so content stays fresh while cutting repeated database queries on every page load (helps most on hosts without a persistent object cache).
- Performance: replaced the homepage per-category random product queries (six full ORDER-BY-random sorts per load) with a cached product-ID pool shuffled in PHP. Same rotating variety, far cheaper, and no longer defeats full-page caching.

## 2.15.2

- Checkout & cart: corrected the block layout so the order summary sits to the right of the form again. v2.15.1 widened the block to full width (correct) but an extra flex rule pushed the summary below it; now only the width is expanded and WooCommerce's native two-column layout is preserved.

## 2.15.1

- Checkout & cart: added styling for the WooCommerce checkout/cart blocks so they use the full centered page width with a proper two-column layout (form on the left, order summary on the right). Previously the block content was constrained to the 820px block content width and sat left, leaving a large empty area on the right.

## 2.15.0

- Domain migration: all theme text, schema, policy pages and data now reference powertoolsplugke.co.ke, with contact email info@powertoolsplugke.co.ke. Phone and shop location unchanged.
- Delivery fees are now stated on product pages and the FAQ: KSh 300 within Nairobi, KSh 1,000 outside Nairobi. In-store pickup remains free.
- Shipping & Delivery policy: replaced the previous free-delivery-threshold wording with the flat KES 300 / KES 1,000 fee structure for accuracy.

## 2.14.3

- Setup wizard: removed the internal 'Merchant Compliance Inspector' from the auto-install list (it is not a wordpress.org plugin and was causing the installer to error and skip the plugins listed after it).
- Setup wizard: no longer auto-installs Yoast SEO (Rank Math is the single recommended SEO plugin) and replaced LiteSpeed Cache with Seraphinite Accelerator for Apache hosts.
- Installer now skips any non-wordpress.org (bundled/custom) plugin gracefully instead of failing.
- Demo import: raised time and memory limits and set ignore_user_abort so sample-data import does not stall or trigger a critical error on modest hosting.

## 2.14.2

- Added a Customer Reviews section on the homepage that automatically shows approved WooCommerce product reviews (4 stars and up), with optional Customizer seed slots for genuine quotes; the section stays hidden until real content exists.
- Added a homepage trust strip: genuine and warranty-backed stock, physical Nairobi shop, secure M-Pesa or pay-on-delivery, and nationwide delivery.

## 2.14.1 - Mobile performance + accessibility
### Performance
- Hero: first slide now renders a single responsive image (srcset 768/1024/1376 with sizes) instead of a duplicate CSS background + full-size image, so mobile downloads a right-sized hero and the LCP element loads sooner.
- Hero images recompressed (progressive, optimized) and mobile/tablet variants generated.
- Header logo no longer requested at full resolution (small sizes hint) and no longer competes with the hero for fetch priority.
- Unused WordPress core block-library CSS is dequeued on classic (non-block) pages, cutting render-blocking CSS.
### Accessibility
- Footer legal links (Returns, Shipping, Privacy, Terms) are underlined so they are distinguishable without relying on colour.
### SEO
- Built-in meta tags now auto-defer to Rank Math, Yoast, SEOPress or All in One SEO when active (filter powerplug_force_meta to override). Structured data can be disabled via the powerplug_disable_schema filter for sites that prefer their plugin's schema.

## 2.14.0 - Domain migration + Merchant-safe messaging
### Changed
- Site domain updated to the new powertoolsplugke.co.ke domain across theme defaults, contact email default (info@powertoolsplugke.co.ke), Theme/Author URI, and WooCommerce from-address default.
- Top bar promo replaced the blanket Free delivery claim with a Merchant-safe message: Genuine, warranty-backed power tools & equipment | Pay by M-Pesa or on delivery.
### Fixed
- Product Offer schema no longer asserts free shipping (shippingRate value 0) by default. OfferShippingDetails is emitted only when a flat rate is explicitly set via the powerplug_flat_shipping option, so structured data cannot conflict with Merchant Center shipping settings.

## 2.13.2 - Mini-cart drawer thumbnail fix
### Fixed
- Product images in the "Your Cart" slide-in drawer rendered full-size on mobile and desktop. Root cause: WooCommerce's thumbnail-shrink rule is scoped to .woocommerce ul.cart_list, but the drawer (.pp-minicart__body) renders woocommerce_mini_cart() outside any .woocommerce wrapper, so nothing constrained the images. Added mini-cart line-item styling: 56px thumbnail, product name + quantity layout, and a styled remove button.

## 2.13.1 - Homepage 6-per-row restored
### Fixed
- The v2.13.0 unified product-card block used !important on the column count, which also overrode the homepage rows (they are wrapped in .woocommerce too), dropping them from 6 to 4 per row. Re-asserted the homepage grid (6 desktop / 4 / 3 / 2) scoped to .pp-shop-full .pp-shop-main with higher priority. Category/shop listings are unaffected.

## 2.13.0 - Unified product cards on all listings
### Fixed
- Category, shop, tag and search product grids now use the SAME card design as the homepage. Root cause: those listings sit in a container that only received weak global rules, so WooCommerce's default float layout won (no card border, overlapping title/price, plain button). Fix: one authoritative product-card block scoped to .woocommerce/.pp-wc__main that overrides WooCommerce defaults - bordered card, contained image, 2-line clamped title, bold price, full-width green add-to-cart with cart glyph, outlined quick view, responsive 4/3/2 columns.

## 2.12.0 - Google alignment + mobile drawer tap fix
### Fixed
- Mobile drawer: tapping a menu item or category did nothing (drawer just closed). Root cause: the dim overlay (z-index 999, body-level) painted above the drawer, which is trapped in the sticky header's stacking context (z-index 100). The overlay swallowed every tap. Fix: raise the header above the overlay while open and set the overlay to pointer-events:none.
### SEO / Google Merchant Center
- Product JSON-LD now includes shippingDetails (OfferShippingDetails) and hasMerchantReturnPolicy (MerchantReturnPolicy) - the fields Search Console flags as missing on merchant listings.
- Added mpn (falls back to SKU), a full image array (main + gallery), and a brand fallback (meta -> product attribute) so the brand-or-identifier requirement is met.
- Added a "Google Merchant" product panel (Brand / GTIN / MPN) under Product data -> Inventory; values flow into the schema.
- Added a Store (LocalBusiness) entity with address, phone, opening hours, price range, and social profiles; Organization gained contactPoint + sameAs.
- Added canonical tags on shop and category archives (WordPress core already handles singular content).
- Filters for merchants: powerplug_return_policy, powerplug_shipping_details, powerplug_opening_hours, powerplug_social_profiles, powerplug_flat_shipping.

## 2.11.0 - Lighthouse accessibility & LCP fixes
### Accessibility
- Added aria-labels to the header Account/Wishlist icon links (their text label is hidden on mobile).
- Enlarged hero slider dot touch targets to 24px (kept the small visual dot).
- Fixed footer legal text contrast (mode-independent light color) and darkened the WhatsApp button so its white label meets WCAG AA.
### Performance / SEO
- Added a real <img fetchpriority="high"> for the first hero slide (fixes desktop NO_LCP and speeds LCP detection).
- Added a meta description (fixes the Lighthouse SEO audit).
- Removed the unused Google Fonts preconnects (the theme uses a system font stack).

## 2.10.0 - Phase 6: Performance, Security, SEO & Accessibility
### Performance
- Removed legacy head bloat (RSD, WLW manifest, shortlink, adjacent-post links).
- Added preconnect + dns-prefetch resource hints for Google Fonts.
- Retained: emoji script/style removal, native lazy-loading, main-CSS preload, deferred theme JS.
### Security
- Baseline OWASP response headers: X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, Strict-Transport-Security.
- Removed WordPress generator version string; disabled XML-RPC.
### SEO / Structured data
- Product JSON-LD now includes priceValidUntil and itemCondition (clears Search Console warnings).
- Variable products emit AggregateOffer (lowPrice/highPrice/offerCount) instead of an empty price.
- BreadcrumbList is now a full dynamic trail (Home > Shop > category ancestors > product) and also renders on category and shop pages.
- Organization + WebSite graphs retained; stands down when Yoast or Rank Math is active.
### Accessibility
- Visible :focus-visible keyboard focus ring on all interactive elements (WCAG 2.4.7).
- Styled, visible WooCommerce breadcrumb; aria-label on the product filter panel.
- Reduced-motion support for drawers and modals.

## 2.9.0 - Phase 5: Setup safety & branding controls
- Demo importer now auto-detects a populated store and skips demo categories/products/menus, so live inventory is never overwritten.
- Customizer "Branding & Colors" section: primary brand color + heading/text color with live retinting via CSS variables.
- Authoritative mobile navigation drawer fix (resolved cascading @media conflicts from earlier versions).

## 2.8.0 - Phase 4: Product discovery
- Filter sidebar: categories, price range, rating, and WooCommerce product attributes (incl. Brand); shareable URL-based filtering.
- Styled sort dropdown, numbered pagination, and an AJAX "Load more products" button.
- Mobile refinements: cart image sizing, stacked/centered checkout, centered category and section layouts.

## 2.7.0 - Mobile navigation & commerce polish
- Mobile slide-in drawer with header + live "Shop by Category" list and quick links.
- Equal-height related/up-sell product grid.
- Full cart and checkout styling (coupon field, boxed totals, sticky order review).

## 2.6.0 - Templates & WooCommerce foundation
- Added page.php (renders full page content) and woocommerce.php full-width wrapper.
- Quick View modal, AJAX add-to-cart + mini-cart, single-product and archive polish.
- Seeder also runs on admin_init so in-place updates repopulate starter pages.

## 2.0.0 - 2.5.0 - Phase 2: Brand & design system
- Full rebrand to PowerPlug Pro (green #268655 + charcoal #111418).
- OOP theme architecture (PSR-4, PowerPlug namespace), theme.json design system.
- Header (top contact bar, search, cart), hero, featured categories, footer, and page content.

## 1.0.0 - Initial scaffold
- Base theme structure, autoloader, setup wizard, plugin installer, demo importer.
