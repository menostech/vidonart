# Slideshow Module — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4

## 1. Goal

A configurable slideshow module: admin-managed slideshows (add/delete), each with full-width or box width, height, background image per slide, overlay color + opacity, and slide content (kicker, title, sub, action button). Per-slide content alignment. Autoplay/arrows/dots toggles. Placed via the standard layout system (homepage `content_top`), replacing the hardcoded `.va-hero`.

## 2. Data Model (two new tables, created by module `install()`)

### `oc_slideshow`
| Column | Type | Notes |
|---|---|---|
| slideshow_id | int PK AI | |
| name | varchar(128) | admin label |
| width_mode | char(4) | `full` or `box` |
| height | int | px |
| autoplay | tinyint(1) | 0/1 |
| arrows | tinyint(1) | 0/1 |
| dots | tinyint(1) | 0/1 |
| status | tinyint(1) | 0/1 |

### `oc_slideshow_slide`
| Column | Type | Notes |
|---|---|---|
| slide_id | int PK AI | |
| slideshow_id | int | FK → oc_slideshow |
| image | varchar(255) | original image path (rendered via CSS cover, no resize) |
| overlay_color | varchar(7) | hex `#RRGGBB` |
| overlay_opacity | int | 0–100 |
| align | char(6) | `left` / `center` / `right` |
| kicker | varchar(255) | eyebrow text |
| title | varchar(255) | slide title |
| sub | varchar(1024) | subtitle text |
| button_text | varchar(128) | action button label (optional) |
| button_link | varchar(512) | action button href (optional) |
| sort_order | int | |
| status | tinyint(1) | 0/1 |

## 3. Admin

### 3.1 Design → Slideshow (core admin)
- `admin/controller/design/slideshow.php` — index/list/getList/form/save/delete, following the stock `admin/controller/design/banner.php` pattern (namespace `Opencart\Admin\Controller\Design`).
- `admin/view/template/design/slideshow.twig` — list with add/edit/delete.
- `admin/view/template/design/slideshow_form.twig` — slideshow fields + slide repeater (each row: image upload via standard OC filemanager button, overlay color (type=color), overlay opacity (number 0–100), align select, kicker/title/sub inputs, button text + link, sort order, status; add/remove rows, drag sort).
- `admin/language/en-gb/design/slideshow.php` — all keys.
- Admin menu: add `design/slideshow` entry to the Design group in `admin/controller/common/column_left.php` + language key.
- Permission: `design/slideshow` added to admin user group permissions (standard OC flow — the route appears in the permissions list automatically; no code needed beyond the controller existing).

### 3.2 Module instance (extension)
- `extension/opencart/admin/controller/module/slideshow.php` — `index()` (name, slideshow_id select, status), `save()`, `install()` (CREATE TABLE both tables), `uninstall()` (DROP both tables), per the stock `module/banner.php` pattern (namespace `Opencart\Admin\Controller\Extension\Opencart\Module`).
- `extension/opencart/admin/view/template/module/slideshow.twig` + `extension/opencart/admin/language/en-gb/module/slideshow.php`.
- Layout placement: standard (Extensions → Modules → install → add to layout position `content_top` of the home layout).

## 4. Catalog Rendering

- `extension/opencart/catalog/controller/module/slideshow.php` — loads slideshow + enabled slides (ordered by sort_order), builds per-slide data; converts `overlay_color` hex + `overlay_opacity` into a ready `rgba(r, g, b, a)` string in the controller (Twig does no hex parsing); passes `width_mode`, `height`, `autoplay`, `arrows`, `dots`.
- `extension/opencart/catalog/view/template/module/slideshow.twig`:
  - wrapper: `full` → full-width block (no container); `box` → `.container`;
  - inline `style="height: {height}px"` on the carousel;
  - each slide: `<div class="va-slide" style="background-image:url(...)">` with cover sizing, overlay div `background: rgba(...)`, content block (kicker / title / sub / button) with alignment class (`text-start` / `text-center` / `text-end`);
  - carousel: **Bootstrap 5.3 Carousel** (already bundled — same approach as the stock banner module; no Swiper dependency), `carousel-fade` for fade transitions; autoplay via `data-bs-ride="carousel"` + JS interval, arrows = carousel-control buttons, dots = carousel-indicators, each rendered only when the corresponding setting is on; pagination dots styled gold, arrows styled ink.
- No catalog language file needed (all slide text comes from DB).

## 5. Homepage Integration

- `catalog/view/template/common/home.twig`: remove the `.va-hero` section (keep the `.va-hero*` CSS rules for potential reuse).
- Deployment: admin creates slideshow + slides → installs module → places instance at home layout `content_top`.

## 6. Out of Scope

- Multiple slideshow instances in one layout (not needed; a slideshow is a group selected by an instance).
- Slide transition effects beyond fade (no ken-burns etc.).
- Per-slide width/height overrides (slideshow-level height only).
- Storefront language packs beyond en-gb for admin (admin language file en-gb only, consistent with existing modules).

## 7. Verification

- Module install creates the two tables (check via phpMyAdmin/CLI after install).
- Admin: create slideshow, add 3 slides with different aligns/overlays, save, re-open (values persist), delete slideshow (cascade).
- Storefront: place instance on home layout; check full vs box, height, autoplay on/off, arrows/dots on/off, overlay color/opacity, alignment classes, button links, image cover rendering, responsive heights.
- `git diff --check` clean; commit messages per task.