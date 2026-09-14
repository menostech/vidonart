# Product Color Swatch — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4

## 1. Goal

Add color swatches to option values: admin assigns a hex color per option value (image kept as fallback); storefront renders color swatches on the product detail page (select/radio options) and on product cards (hover switches the card image, click navigates to the detail page with the color preselected).

## 2. Data Model

- `oc_option_value` gains `color` column: `VARCHAR(7) NOT NULL DEFAULT ''` (hex `#RRGGBB`).
- Live DB: one-time `ALTER TABLE` (SQL provided to user). `install/opencart-en-gb.sql`: add the column to the `oc_option_value` CREATE TABLE for fresh installs.

## 3. Admin — Option Value Editor

- `admin/view/template/catalog/option_form.twig`: each option value row gains a color input (`type=color`, `name="option_value[<row>][color]"`, value hex), next to the existing image field; the JS add-value template includes the same input.
- `admin/controller/catalog/option.php` `save()`: read `color` per value (default '' if missing/empty); `getValue`/`getValues` return it.
- `admin/model/catalog/option.php`: `addValue()`/`editValue`-equivalent path persists `color` (INSERT/UPDATE `oc_option_value` includes `color`); `getValue(int)`/`getValues(int)` select `color`.
- Language: `entry_color` key in `admin/language/en-gb/catalog/option.php`.

## 4. Storefront — Product Detail Page

- `catalog/view/template/product/product.twig`:
  - For `select` options: if any option value has a non-empty `color`, render a swatch button group instead of the `<select>` (hidden input per value OR a select with visually-hidden styling replaced by swatch buttons that set the select value). Implementation: keep a hidden `<select>` for the form contract (`option[<product_option_id>]`), and render clickable swatch buttons that set its value + trigger change (so validation/JS behavior is unchanged).
  - For `radio` options: if any value has a color, render swatch circles instead of the radio list (same form field names; a hidden radio or the swatch sets the checked radio). Values with image but no color keep the current image-thumbnail radios; values with neither keep text radios.
  - Selected swatch: ink border + check; required validation still applies.
- Data: `catalog/model/product/product.php`'s option value data already includes `image`; add `color` to the option value arrays (getOptions/getOptionValues select).
- URL preselect: the page JS/controller reads `option[<id>]=<value_id>` from the query string and pre-selects that value (twig inline JS sets the select/radio). Card links pass this param.

## 5. Storefront — Product Cards

- `catalog/controller/product/thumb.php`: accept a `swatches` entry in the passed data; build per product from the caller — instead, add a shared helper in the thumb controller? Simplest: callers (category/search/special/home) already have product rows; add to each caller's product_data build: load `getOptions(product_id)` via the catalog product model, collect the FIRST option whose values include at least one non-empty `color`, emit `swatches` = [{value_id, color, image}] (image raw or ''). If none, `swatches = []`.
- `catalog/view/template/product/thumb.twig`: if `swatches` non-empty, render a swatch row (18px circles, gold/ink border on selected/hover):
  - `mouseenter` on a swatch → if that value has an image, swap the card main image `src` to `image/<path>` (pre-resized URL passed in swatch data to avoid client resizing).
  - `click` → navigate to `href` + `&option[<option_id>]=<value_id>` (product detail URL built by the caller already in `href`; append the option param; the caller passes `swatch_option_id`).
- No swatches → no rendering, zero markup change.

## 6. Out of Scope

- Swatches in cart/checkout rows.
- Multi-color gradient/texture swatches beyond hex.
- Admin swatch preview on the option list page.
- Wishlist/compare behaviors with swatches.

## 7. Verification

- Admin: set colors on option values, save, reopen (persists), values without color untouched (image fallback works).
- Detail page: color options render swatches; selection works with required validation; non-color values fall back to image/text; URL preselect works.
- Cards: swatches render only for products with colored values; hover swaps image (when image present); click navigates with preselected color; pagination/related/home all consistent.
- `git diff --check` clean.