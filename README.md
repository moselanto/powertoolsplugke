# powertoolsplugke

Source code for **[powertoolsplugke.co.ke](https://www.powertoolsplugke.co.ke/)** (Power Tools Plug Kenya), a WooCommerce store for power tools, solar equipment, incubators and hardware.

| | |
|---|---|
| **Live site** | https://www.powertoolsplugke.co.ke/ |
| **Parent theme** | `powertoolsplugke-main/` (PowerPlug Pro) — v2.23.0 |
| **Child theme** | `powerplug-child/` — put customisations here |
| **Stack** | WordPress 6.4+, WooCommerce 8.0+, PHP 8.0+ |

GitHub commits do **not** auto-deploy. After changing files here, zip the theme folder and upload it under **Appearance → Themes → Add New → Upload Theme** (replace current), then purge the LiteSpeed cache.

## Repository layout

```
powertoolsplugke-main/      PowerPlug Pro parent theme
  inc/Front/LandingPage.php Landing-page funnel module (routing + renderer)
  template-lp-category.php  "Landing Page — Category (Ads)" template
  assets/img/lp-*-hero.*    Built-in hero images for each funnel
  CHANGELOG.md              Version history
powerplug-child/            Child theme
```

## Landing page funnels (`/lp-{category}/`)

Ad-traffic landing pages built by `inc/Front/LandingPage.php` and `template-lp-category.php`: announcement bar, hero with From price, trust strip, live product grid (prices/stock from WooCommerce), benefits, what's included, comparison, shipping/warranty, FAQ, order form and sticky WhatsApp/Order bar.

### Fix in 2.23.0 — "Nothing found."

Before 2.23.0 a funnel only worked if a **published** WordPress page with that exact `lp-` slug existed **and** used the "Landing Page — Category (Ads)" template. Otherwise WordPress returned a 404 and the theme showed "Nothing found."

Now:

1. Any `/lp-{slug}/` URL renders the funnel automatically — no page, rewrite rule or permalink flush required.
2. A published `lp-` page is always rendered with the Ads template, even if it was saved with the default template.
3. The slug is matched to a product category by exact slug → aliases → singular/plural → category name.
4. Settings in the page's **Landing Page (Ads) settings** box (category, advertised product IDs, From price, hero heading/sub/image, announcement, benefits, what's included) still override the defaults when the page exists and is published.

### Funnels

| Funnel | Hero image |
|---|---|
| https://www.powertoolsplugke.co.ke/lp-incubators/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-demolition-breakers/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-vacuum-cleaners/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-pressure-washers/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-water-pumps/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-hardware-tools/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-weighing-scales/ | branded panel (set Hero image URL) |
| https://www.powertoolsplugke.co.ke/lp-batteries/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-welding-machines/ | branded panel (set Hero image URL) |
| https://www.powertoolsplugke.co.ke/lp-solar-panels/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-solar-inverters/ | built-in |
| https://www.powertoolsplugke.co.ke/lp-grinders/ | built-in |

### Adding or fixing a funnel

- New funnel: just use `/lp-{category-slug}/`. To customise it, create a page with that slug, choose the **Landing Page — Category (Ads)** template, fill in the settings box and publish.
- If the URL differs from the category slug, add an alias in the child theme:

```php
add_filter( 'powerplug_funnel_aliases', function ( $map ) {
	$map['grinders'] = array( 'angle-grinders' );
	return $map;
} );
```

### If a funnel still shows "Nothing found."

- Confirm the 2.23.0 theme is uploaded and purge LiteSpeed cache.
- Check the matching category has published products (or set Advertised product IDs).
- Check Rank Math / Redirection for a redirect on that URL.
