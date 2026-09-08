# Vidon Art Storefront Redesign — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user (section-by-section, 7 sections)
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4 (fresh install, no `config.php` yet)

## 1. Context & Goals

The store sells custom-made, "New Chinese Style" (新国风) and Oriental-culture products: jewelry, handbags, and home & living objects. The storefront must:

- Present a premium, modern-oriental brand identity for an **English-speaking international audience**.
- Sell **standard products** through the normal cart/checkout flow, AND capture **custom orders** through a dedicated consultation form.
- Be implemented as a full storefront visual redesign of the stock OpenCart 4.1 default theme (Bootstrap 5 SCSS + Twig templates).

Brand: **Vidon Art** — tagline **"Oriental Heritage · Modern Craft"**.

## 2. Visual Direction: "Modern Oriental"

Selected from 4 candidate directions. Design language: paper-white canvas, ink-black text/primary actions, restrained tea-green and gold accents, generous whitespace, thin 1px borders, minimal shadows, elegant serif display type.

### 2.1 Color tokens

| Token | Value | Usage |
|---|---|---|
| Paper (bg) | `#FAFAF8` | Page background |
| Surface | `#FFFFFF` | Cards, forms |
| Ink | `#222222` | Headings, body, primary buttons |
| Text secondary | `#5A5A55` | Helper text, meta |
| Tea | `#8A9B8E` | Links, hover, secondary buttons |
| Gold | `#C9A227` | Badges, sale price, custom tags, hairline accents |
| Border | `#E4E4DF` | 1px hairlines |

### 2.2 Typography

- Display/headings: **Cormorant Garamond** (serif), fallback Georgia.
- Body/UI: **Inter**, fallback system stack.
- Loaded via Google Fonts in `header.twig`.
- Buttons/labels: uppercase + letter-spacing for luxury feel.

### 2.3 Rhythm & feel

- Section gaps 48–72px; max content width 1280px.
- Border radius 2–6px; shadows effectively unused; hierarchy via hairlines + whitespace.
- Image hover: subtle scale (1.03), transition 0.25s ease.
- Breakpoints: Bootstrap grid (mobile-first); product grid 4/3/2 columns (desktop/tablet/mobile).

## 3. Implementation Approach (selected: A)

Chosen: **A — in-place customization of core templates + SCSS** (`catalog/view/template/**` + `catalog/view/stylesheet/`).

Rejected alternatives:
- **B — OC4 Theme Editor (DB `design_theme`)**: upgrade-safe but code lives in DB, untracked by git, poor dev experience; unsuitable for a full redesign.
- **C — OCMOD modification**: runtime XML rewrites; unmaintainable for a whole-theme redesign.

Rationale: the theme source files are already tracked in git (4053 files); upgrades can be diff-merged. No theme-extension mechanism exists in OC4 storefront.

## 4. Architecture & Files

### 4.1 Template changes (edit in place)

| Area | Files |
|---|---|
| Header/footer/menu | `catalog/view/template/common/header.twig`, `footer.twig`, `menu.twig`, `search.twig`, `currency.twig`, `language.twig`, `cart.twig` |
| Home | `catalog/view/template/common/home.twig` |
| Category/list | `catalog/view/template/product/category.twig`, `search.twig`, `special.twig`, `thumb.twig` (shared card), `compare.twig` |
| Product detail | `catalog/view/template/product/product.twig`, `review.twig`, `related.twig` |
| Misc | `pagination.twig`, `cookie.twig`, `success.twig`, `maintenance.twig`, account/checkout/information templates restyled via global CSS only |

### 4.2 New files (custom order page)

- `catalog/controller/information/custom.php` — handles GET (form) and POST (validate + mail).
- `catalog/view/template/information/custom.twig` — form + success state + info column.
- `catalog/language/en-gb/information/custom.php` — language strings.

### 4.3 Styles

- `catalog/view/stylesheet/stylesheet.scss` — full rewrite: override Bootstrap variables (`$primary`, `$border-radius`, fonts, etc.) + component styles (buttons, cards, pagination, forms, tabs, modals).
- Bootstrap SCSS sources already present at `catalog/view/stylesheet/scss/`; compiled CSS committed to git (OpenCart `startup/sass` pipeline compiles SCSS at runtime when needed).
- `catalog/view/stylesheet/bootstrap.css` regenerated/overridden for palette alignment.

### 4.4 Data flow

1. Customer browses standard catalog → cart → checkout (stock OpenCart flow, restyled).
2. Customer requests custom piece via:
   - nav link / homepage CTA → `/index.php?route=information/custom` (SEF: `custom`),
   - or product page "Customize This Piece" → route with `product_id` → prefills product reference + category.
3. POST → controller validates (email required + format) → sends email via OC mail to store owner → renders success state.

### 4.5 Error handling

- Custom form: field-level validation errors near fields; required: email; format-checked.
- Empty search/category: empty-state message + "Explore Other Collections" link.
- Maintenance/cookie/authorize: stock logic, restyled.

## 5. Page Designs

### 5.1 Global frame

**Header** (sticky, paper with translucency): serif logo "Vidon Art" left; centered nav — Home · Jewelry · Handbags · Home Living · Custom Order (serif, gold hairline underline on hover); right — search, account, wishlist, cart (gold count badge). Mobile: hamburger → full-screen drawer with large serif links. Dropdown submenus restyled as hairline card panels.

**Footer** (ink `#222`): brand column (logo + tagline + one-liner) + 3 link columns (Shop / Custom / Help); newsletter input (hairline ivory); bottom bar with gold hairline separator, copyright, gray payment icons, language/currency switchers.

**Shared components**: breadcrumbs (hairline, gold "/"), pagination (hairline page numbers, active = ink solid), modals (white, 1px border, serif titles), cookie notice and back-to-top restyled in ink/paper.

### 5.2 Homepage (layout 1 — "Classic category navigation")

1. **Hero**: ~70vh full-width brand visual, paper bg, ink serif headline "Oriental Heritage, Modern Craft", sub-line, two buttons: primary ink "Shop the Collection" + outline "Start a Custom Order".
2. **Collection tiles**: 3 equal large cards — Jewelry / Handbags / Home Living; image + serif name + "Explore →" (image scale + gold arrow on hover), 24px gutters, hairline below.
3. **Featured Pieces**: serif section title + gold hairline accent + "View All"; 4-column grid using shared `thumb.twig`.
4. **Custom CTA band**: ink band — serif title "One of a Kind, Made for You" + copy, gold button "Start a Custom Order".
5. Footer (global).

### 5.3 Category / listing pages

- Page head: large serif category name + description, hairline under.
- Filter bar: sort/limit hairline selects + grid/list toggle icons.
- Grid: shared `thumb.twig`, 4/3/2 cols.
- Empty state: message + explore link. Pagination as per global.

### 5.4 Product card (`thumb.twig`)

White card, 1px `#E4E4DF` border, no shadow; 1:1 image (hover scale + ink border); name Inter 2-line clamp; price ink, sale price gold + struck original; corner badge slot (New/Sale/Custom-Made — gold fill or gold outline); hover quick add-to-cart circle button (ink); every card footer shows a subtle gold "Customize →" link → custom form with product prefilled (no per-product flag; the brand is custom-first, so the affordance is always present).

### 5.5 Product detail

- Left gallery: 1:1 main image + thumbnail row (selected = ink outline); lightbox restyled.
- Right info: breadcrumb; serif title; price row (sale = gold + struck); attribute options (dropdown/button group, selected = ink solid); qty stepper; full-width ink **Add to Cart**; badge line; **custom block** — gold dashed hairline box, serif "Want It Custom-Made?", copy, outline button "Customize This Piece →" → custom form prefilled (name + category).
- Trust line: Free Shipping · Handcrafted · Returns (icons + gray).
- Tabs: Description / Specifications / Reviews (hairline tabs, active ink underline; reviews stock logic restyled). Related: 4-col `thumb` grid.
- Out-of-stock: gold "Made to order" or gray sold-out state. Add-to-cart success modal: white, hairline, serif title, View Cart / Checkout.

### 5.6 Custom Order page (`information/custom`)

- Page head: serif "Custom Order" + copy: "Tell us what you have in mind — our artisans will reply within 2 business days."
- Two columns (stack on mobile): form | info.
- Fields:
  1. Category — card-style 3-way select (Jewelry / Handbags / Home Living), selected = ink solid.
  2. Product reference (optional) — text, prefilled from product page.
  3. Describe your idea — textarea (placeholder with style/materials/colors/occasion/budget).
  4. Budget range (optional) — select: Under $200 / $200–500 / $500–1,000 / $1,000+.
  5. Email — required, format-checked.
  6. Submit Request — ink primary.
- Success: serif "Thank You" + reply ETA copy + "Back to Home" link. Mail to store owner via OC mail.
- Info column: "What Happens Next" 3 steps (Reply within 2 days → Design proposal & quote → Craft & deliver, ~4–8 weeks); contact fallback: email + social placeholders.

### 5.7 Other pages

Cart, checkout, account, information, blog: stock logic, restyled via global CSS (form controls, tables, buttons, serif titles).

## 6. Out of Scope

- Admin panel restyle, new payment methods, multi-language (single English storefront this phase).
- No changes to catalog/checkout business logic beyond the custom page.

## 7. Assets Required (from user; placeholders acceptable initially)

- Hero visual, 3 collection tile images, product photos (jewelry/bags/home).
- Logo (text logo used until provided).
- Store owner email for custom-order notifications.

## 8. Verification

- Install store via `install/` wizard (requires DB + config.php) — implementation should not require it, but visual verification happens after install.
- Walk pages: home, category, product, custom form (+ prefill from product), cart, checkout, account.
- Responsive check at 360 / 768 / 1280 px; contrast check for ink-on-paper and gold-on-ink combinations.