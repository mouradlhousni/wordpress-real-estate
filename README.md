# Keystone Realty — custom WordPress real estate site

A complete, hand-built WordPress site for a fictitious real estate agency, made as a portfolio piece.
No page builder, no premium plugin, no ACF: a **custom theme** for presentation and a **custom plugin**
for the business logic, written to WordPress Coding Standards.

[![Try the live demo in WordPress Playground](https://img.shields.io/badge/Try%20the%20live%20demo-WordPress%20Playground-1f4e8c?logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mouradlhousni/wordpress-real-estate/master/blueprint.json)

**[▶ Try the live demo in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/mouradlhousni/wordpress-real-estate/master/blueprint.json)**
— a full WordPress with the theme, the plugin and the demo content, running in your browser (admin included, nothing hosted).

![Home page](docs/screenshots/home.jpg)

## Contents

- [Features](#features)
- [Two ways to see the demo](#two-ways-to-see-the-demo)
- [Technical choices](#technical-choices)
- [Screenshots](#screenshots)
- [Getting started](#getting-started)
- [REST API](#rest-api)
- [WP-CLI](#wp-cli)
- [Quality](#quality)
- [Project structure](#project-structure)

## Features

### Plugin — `keystone-properties` (business logic)

- **Custom post types**: `property`, `agent`, and a private, admin-only `lead`.
- **Taxonomies**: property type (Apartment, House, Villa, Office), city, status (For Sale, For Rent, Sold).
- **Typed custom fields** registered with `register_post_meta()` and exposed to the REST API: price, bedrooms,
  bathrooms, surface, year built, address, latitude/longitude, features, gallery, listing agent, featured flag.
- **Hand-built admin UI**: meta boxes with nonces, capability checks, validation messages and sanitization;
  a media-library gallery picker with drag-and-drop ordering; sortable price, city and status columns; taxonomy filters.
- **Search API**: `GET /wp-json/keystone/v1/properties` with city, type, status, price range, minimum bedrooms,
  sort and pagination. Parameters are schema-validated and results are cached.
- **"Contact the agent" form**: nonce, honeypot, per-IP rate limiting (the IP is hashed, never stored), server-side
  validation, email to the listing agent with `wp_mail()`, and a lead saved for the admin. Works with JavaScript
  (REST, no reload) and without it (classic POST).
- **Structured data**: schema.org `RealEstateListing` with `Offer`, `Accommodation`, address, geo coordinates and agent.
- **`wp keystone seed`**: builds a complete demo (4 agents, 24 listings in 5 cities, pages, menu) with illustrations
  **generated locally with GD**: no downloads, no hotlinking.

### Theme — `keystone` (presentation)

- Classic PHP theme: front page, property search (archive and city/type/status pages), single property, agents, pages, 404.
- **Home page**: framed photo hero with a Buy/Rent search bar, key figures in a bento grid, featured homes with
  type filter chips, a city mosaic, an agents carousel (scroll-snap, no library), testimonials and a call to action.
- **Live search** in plain JavaScript: filters, sort and pagination without reloading the page, shareable URLs,
  back/forward support, loading state, polite screen-reader announcements and an empty state.
  The page is rendered on the server first, so it also works without JavaScript and is fully crawlable.
- **Leaflet + OpenStreetMap map** with price markers that follow the results. Leaflet is loaded only when a map scrolls into view.
- **Property cards** show the status, price, specs, photo count and the listing agent; the server (PHP) and the live
  search (JavaScript) render exactly the same markup.
- **Property page**: photo mosaic with an accessible lightbox (native `<dialog>`, keyboard, swipe), key figures,
  key facts, features, map, sticky agent card with contact form, similar properties.
- **Two Gutenberg blocks** built with `@wordpress/scripts`: *Featured Properties* (count, city, status, featured only)
  and *Property Search Bar*, both rendered on the server.
- **Design system** in CSS custom properties, mobile-first, no CSS framework. Warm editorial direction: ink and amber
  on a warm off-white, generous radii, pill controls, and headings in Manrope with one word set in Instrument Serif
  italic. Both fonts are self-hosted (47 kB in total) and every text colour pair meets WCAG AA.
- **SEO**: meta description, Open Graph and Twitter cards (switched off automatically when an SEO plugin is active).

## Two ways to see the demo

A portfolio demo should never be a live WordPress with working forms on a public server: it would collect real
personal data, attract spam and need constant security updates. So this project is shown in two safe ways.

| | Static showcase | WordPress Playground |
| --- | --- | --- |
| What it is | Plain HTML/CSS/JS generated from the site | Real WordPress compiled to WebAssembly, running **in the visitor's browser** |
| Hosting | Any static host (Cloudflare Pages, Netlify, GitHub Pages) | Nothing hosted: `playground.wordpress.net` + `blueprint.json` |
| Search, filters, map, gallery | ✓ (same UI, filters a JSON file) | ✓ (REST API) |
| Contact form | Replaced by a contact card and a link to Playground | ✓ Validated, but stores and sends nothing (demo mode) |
| Admin, blocks, WP-CLI | — | ✓ Logged in as admin |
| Attack surface | None (no PHP, no `/wp-json/`, no login) | None on any server |

### Demo mode — `KEYSTONE_DEMO_MODE`

```php
define( 'KEYSTONE_DEMO_MODE', true ); // wp-config.php (on by default in .wp-env.json and blueprint.json)
```

All checks run for real (nonce, honeypot, field validation on the client and the server), then the form answers
*"Demo mode: the form works, but no data is stored or sent."* Nothing is written to the database (not even the
rate-limit counter), no email is sent, and the form accepts no files. A discreet "Demo site" banner is shown on the
front end and a notice in the admin. The real code path stays complete: set the constant to `false` and leads are
saved and emailed. This was verified by logging every SQL write and every `wp_mail()` call during a submission:
10 writes and 1 email with demo mode off, **none** with demo mode on.

The constant is read in a single place, `keystone_is_demo_mode()` (filterable).

### WordPress Playground — `blueprint.json`

The blueprint defines `KEYSTONE_DEMO_MODE`, installs the plugin and the theme straight from this repository, activates
them, sets pretty permalinks, runs `wp keystone seed --images=8` (about 25 seconds in Playground) and opens the home page
logged in as admin.

It uses Playground's `git:directory` resource rather than the repository ZIP, because GitHub's ZIP wraps the whole
repository and Playground needs the plugin and theme folders on their own. To use a fork or another branch, edit
`url` and `ref` in the two install steps and the `blueprint-url` of the link above.

### Static export — `bin/export-static.sh`

```bash
bin/export-static.sh                                   # local WP-CLI ("wp")
WP_CLI="npx wp-env run cli wp" bin/export-static.sh    # with wp-env
bin/export-static.sh --from path/to/simply-static-export
npx serve dist                                         # preview
```

1. Sets `KEYSTONE_STATIC_BUILD` to `true` (restored when the script ends, even on error). The theme then adds
   `data-static-build="1"` to `<body>`, the search reads `data/properties.json` instead of the REST API, the contact
   form becomes a contact card with a link to Playground, and REST/oEmbed/feed links are removed from `<head>`.
2. Copies the site as HTML with the Simply Static plugin (`--from`), `wget --mirror` when available, or a
   dependency-free Node crawler (`bin/static-export.js`).
3. Writes `dist/data/properties.json` with `wp keystone export-json` (every listing, root-relative URLs).
4. Rewrites every URL as relative (the export works from a domain root or a sub-folder such as GitHub Pages),
   removes `wp-login.php`, `xmlrpc.php`, `/wp-json/` and admin links, adds `_headers` (security headers and caching
   for Cloudflare Pages / Netlify), `.nojekyll` and a 404 page, then **fails if anything dynamic is left**.

The result was tested in a browser served from `/` and from `/showcase/`: search, filters, pagination, sorting,
shareable URLs, map, gallery and 404 work, with zero requests to PHP or `/wp-json/`.

## Technical choices

**Why no page builder?** Page builders add hundreds of kilobytes of CSS/JS, store layouts as opaque data that ties
the client to one vendor, and make performance and accessibility hard to control. Hand-written templates and two
focused blocks keep every page fast, the markup semantic, and the editing experience simple for the client.

**Why a theme *and* a plugin?** Content must outlive the design. Listings, agents, leads, the REST API and the
contact form live in the plugin, so a redesign (or a switch to another theme) never loses data or features.
The theme only reads data through a small public API (`keystone_get_property()`, `keystone_search_properties()`,
`keystone_lead_form()`…) and shows a clear message if the plugin is disabled.

**Why no ACF?** Fields declared with `register_post_meta()` are typed, sanitized on every write path (admin, REST,
WP-CLI), visible in the REST schema, and carry no third-party dependency or licence.

**Performance**

- One normalized read model (`PropertyRepository`) feeds the REST API, templates, blocks, JSON-LD and the static
  export, so every surface shows the same data.
- Heavy queries are cached in transients whose key contains a version number. Saving, deleting or re-tagging a
  property bumps the version: invalidation is instant and nothing has to be purged.
- No `posts_per_page => -1`; search pages skip the default main query work; block CSS is dropped where unused.
- Responsive images (`srcset`/`sizes`), lazy loading below the fold, `fetchpriority="high"` on the main image,
  WebP sub-sizes, preloaded fonts, minified and deferred scripts loaded only on the pages that need them.

Lighthouse (mobile, local server):

| Page | Performance | Accessibility | Best Practices | SEO |
| --- | --- | --- | --- | --- |
| Home | 92 | 100 | 100 | 100 |
| Search | 95 | 100 | 100 | 100 |
| Property | 97 | 100 | 100 | 100 |
| Agents | 99 | 100 | 100 | 100 |

> Measured without gzip/brotli on the local Apache server. Text compression on the production server adds a few more points.

**Security**: every output is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`), every input is sanitized, every
write checks a nonce and `current_user_can()`. Leads are private, never exposed to the REST API and readable only by
administrators. The no-JavaScript fallback passes error *codes* in the URL, never free text.

**Accessibility**: skip link, visible focus styles, labelled form fields with inline error messages
(`aria-invalid`/`aria-describedby`), `aria-live` result count, keyboard-operable map markers and lightbox,
AA colour contrast, `prefers-reduced-motion` respected.

## Screenshots

| Search with map | Property page |
| --- | --- |
| ![Search page with map](docs/screenshots/search-map.jpg) | ![Property page](docs/screenshots/property.jpg) |

| Mobile | Mobile search |
| --- | --- |
| ![Mobile home](docs/screenshots/mobile-home.jpg) | ![Mobile search](docs/screenshots/mobile-search.jpg) |

| Admin: listings | Admin: editing a property |
| --- | --- |
| ![Admin list](docs/screenshots/admin-list.jpg) | ![Admin edit screen](docs/screenshots/admin-edit.jpg) |

## Getting started

Requirements: PHP 8.1+, WordPress 6.5+ (tested on 7.1), Node 20+, Composer, and Docker for `wp-env`.

```bash
npm install
composer install
npm run env:start            # wp-env: WordPress on http://localhost:8888 (admin / password)
npm run seed                 # wp keystone seed: full demo content in about 30 seconds
```

The theme is activated automatically by `wp-env`. The built block files and minified assets are committed,
so the site works without running a build.

**Without Docker** (LocalWP, Laragon, MAMP…): link or copy `plugins/keystone-properties` and `themes/keystone` into
`wp-content/plugins` and `wp-content/themes`, activate both, set permalinks to "Post name", then run
`wp keystone seed`. The WP-CLI command also works in the WordPress Playground.

**Development**

```bash
npm run build          # blocks (@wordpress/scripts) + minified CSS/JS
npm run start          # watch the blocks
npm run lint           # PHPCS + ESLint + Stylelint
```

Set `SCRIPT_DEBUG` to `true` to load the unminified CSS and JavaScript.

## REST API

`GET /wp-json/keystone/v1/properties`

| Parameter | Type | Description |
| --- | --- | --- |
| `city`, `type`, `status` | slug | Taxonomy filters |
| `min_price`, `max_price` | number | Price range (reversed bounds are swapped) |
| `min_beds` | integer | Minimum number of bedrooms |
| `sort` | `newest` \| `price-asc` \| `price-desc` \| `surface` | Sort order |
| `featured` | boolean | Featured listings only |
| `page`, `per_page` | integer | Pagination (`per_page` ≤ 24) |

```bash
curl "http://localhost:8888/wp-json/keystone/v1/properties?city=nice&min_beds=2&sort=price-asc"
```

The response contains `items`, `total`, `total_pages`, `page` and `per_page`, plus `X-WP-Total` headers.

`POST /wp-json/keystone/v1/leads` receives the contact form (nonce required, rate limited).

## WP-CLI

```bash
wp keystone seed [--properties=<1-24>] [--images=<8-40>]   # replace previous demo content
wp keystone reset                                          # delete everything the seed created
wp keystone export-json [--output=<file>]                  # listings as JSON (static search)
```

Seeding is idempotent and only ever touches content it created itself. By default it draws illustrations locally
with GD (no hotlinking). To use real photos instead, drop them in `plugins/keystone-properties/assets/demo/`
(`exterior/<type>/`, `interior/`, `agents/`): they are resized and compressed at seed time.

## Quality

- `vendor/bin/phpcs`: WordPress Coding Standards and PHPCompatibilityWP (PHP 8.1+), no errors.
- No PHP notices with `WP_DEBUG` on; no JavaScript console errors.
- Everything is prefixed `keystone_`; the plugin uses the `Keystone\Properties` namespace.

## Project structure

```
.
├── .wp-env.json                 # local environment (Docker), demo mode on
├── blueprint.json               # WordPress Playground demo
├── composer.json, phpcs.xml.dist
├── package.json                 # @wordpress/scripts, linters, build
├── bin/                         # export-static.sh, static-export.js, build helpers
├── docs/screenshots/
├── plugins/keystone-properties/
│   ├── keystone-properties.php  # bootstrap + autoloader
│   ├── includes/functions.php   # public template API used by the theme
│   └── src/
│       ├── PostTypes.php, Meta.php, Cache.php, PropertyRepository.php
│       ├── Admin/               # meta boxes, list columns
│       ├── Rest/                # properties + leads endpoints
│       ├── Leads/               # form rendering and lead service
│       ├── Frontend/            # JSON-LD, demo banner, static-build cleanup
│       └── Cli/                 # seed command + GD image generator
└── themes/keystone/
    ├── *.php                    # templates
    ├── inc/                     # setup, assets, template tags, SEO, blocks
    ├── template-parts/
    ├── src/blocks/              # block sources → build/blocks
    └── assets/                  # css, js, fonts, Leaflet
```

## License

GPL-2.0-or-later. Agency, people, phone numbers and addresses are fictitious.
