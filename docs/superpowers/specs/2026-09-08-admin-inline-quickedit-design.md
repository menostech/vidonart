# Admin List Inline Quick Edit — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4

## 1. Goal

Allow admin users to edit cells directly in the Products and Categories list pages (click cell → inline edit → save), without opening the edit form. Approach A approved: a shared JS component driven by `data-quick-edit` attributes + per-field save endpoints on the existing controllers.

## 2. Editable Cells

| List | Cells |
|---|---|
| Products (`admin/view/template/catalog/product_list.twig`) | Image, Name, Model, Price, Quantity, Sort Order, Status |
| Categories (`admin/view/template/catalog/category_list.twig`) | Image, Name, Sort Order, Status |

## 3. Backend: `saveField()` actions

### 3.1 `admin/controller/catalog/product.php` → `saveField()`

- POST: `product_id`, `field`, `value`.
- Permission: `$this->user->hasPermission('modify', 'catalog/product')` else `error_permission`.
- Field whitelist + validation:

| field | validation |
|---|---|
| name | `oc_validate_length` 1–255 |
| model | `oc_validate_length` ≤64 |
| price | numeric ≥ 0 |
| quantity | int ≥ 0 |
| sort_order | int |
| status | 0/1 |
| image | non-empty + `is_file(DIR_IMAGE . html_entity_decode(value))` |

- Persist via new model method `updateField(int $product_id, string $field, $value)` in `admin/model/catalog/product.php` (single-column UPDATE; keeps the existing `editProduct` untouched).
- Success JSON: `{"success": "...", "value": "<normalized>"}` (e.g., status flipped value). Error JSON: `{"error": {"field": "..."}}` with new language keys.

### 3.2 `admin/controller/catalog/category.php` → `saveField()`

- POST: `category_id`, `field`, `value`; permission `modify` `catalog/category`.
- Whitelist: `name` (1–255), `sort_order` (int), `status` (0/1), `image` (path + is_file).
- Model `updateField(int $category_id, string $field, $value)` in `admin/model/catalog/category.php`.
- Same JSON contract.

### 3.3 Language keys

Add to `admin/language/en-gb/catalog/product.php` and `catalog/category.php`:
- `error_name` (already exists in product.php — reuse), new: `error_model`, `error_price`, `error_quantity`, `error_sort_order`, `error_image`, `text_success_field` (e.g. 'Success: You have modified this field!').

## 4. Frontend: `admin/view/javascript/quickedit.js` (shared component)

- Loaded on the two list pages (after `common.js`).
- Delegated event binding at document level (survives ajax list reloads from pagination/sorting).

**Behavior per type** (driven by data attributes on the cell):
- `data-quick-edit="<field>"` — text/number cell: click → replace cell content with `<input>` (type=number for price/quantity/sort_order) → Enter or blur → POST → on success update cell text + flash highlight; Esc cancels; failure → restore original + `#alert` error.
- `data-quick-edit="status"` — click → immediate POST with flipped value → toggle displayed state + add/remove row class `table-active opacity-50`.
- Image cell: stock file-manager markup per row (hidden input + thumb with `data-oc-toggle="image"` / `data-oc-thumb`); when the hidden input changes (picker chose), auto-POST the `image` field → refresh thumbnail on success.
- Cell markup attributes: `data-quick-edit-url` (saveField URL with `user_token`), `data-quick-edit-value` (initial display value), `data-quick-edit-id` (row entity id).
- Status display: bootstrap-style checkbox/switch read-only element (clickable), reflecting current value.

## 5. List Template Changes

- `product_list.twig`: wrap/add attributes on the 7 cells (image cell gets the file-manager markup + hidden input).
- `category_list.twig`: 4 cells.
- Both pages: `<script src="admin/view/javascript/quickedit.js">` before `</body>`/after common.js (stock list templates load scripts via the page script list — add to controller's `$data['scripts']` like other admin pages do, or inline script tag following existing list page conventions).
- Unchanged: checkbox select/delete flow, header sort links, pagination, existing ajax form (`data-oc-toggle="ajax"` list container) — quickedit only intercepts clicks on marked cells.

## 6. Out of Scope

- Other lists (Slideshows, Banners, etc.) — the mechanism is reusable; wiring them later is just markup + a saveField action.
- Bulk inline edits, drag-and-drop sort, multi-language inline editing.
- Storefront changes.

## 7. Verification

- Products: edit name/model/price/quantity/sort_order inline (Enter + blur), toggle status, change image via file manager — all persist and re-render; Esc cancels; invalid values show error + revert; unauthorized user gets permission error; pagination reload keeps quickedit working.
- Categories: same for its 4 cells.
- `git diff --check` clean.