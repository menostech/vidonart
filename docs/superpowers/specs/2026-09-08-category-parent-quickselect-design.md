# Categories List Parent Quick-Select — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4

## 1. Goal

Add a Parent column to the admin Categories list with inline quick selection of the parent category (reusing the existing quick-edit system).

## 2. List Changes

### 2.1 New Parent column (`admin/view/template/catalog/category_list.twig`)

- New `<th>{{ column_parent }}</th>` + cell placed **before the Sort Order column**.
- Cell shows the parent path (e.g. "Home Living > Ceramics"); top-level categories show "—".
- Cell markup: `data-quick-edit="parent_id" data-quick-edit-type="select" data-quick-edit-url/id/id-field="category_id" data-quick-edit-value="<parent_id>"; data-quick-edit-text="<parent path or '—'>"` (text shown after save).
- Empty-state colspan 6 → 7.
- Hidden options source rendered once in the partial:

```twig
<select id="quick-parent-options" class="d-none">
  <option value="0">None</option>
  {% for option in category_options %}
    <option value="{{ option.category_id }}">{{ option.path }}</option>
  {% endfor %}
</select>
```

### 2.2 Controller data (`admin/controller/catalog/category.php` `getList()`)

- Build `$data['category_options']`: all categories via `getCategories()` (no filters), each with `path` from `getPath($category_id)` (skip none — every category is a valid parent for another row; cycle protection happens server-side per row).
- Per row: `parent_name` = `getPath($result['parent_id'])` if `parent_id > 0` else `''`; `parent_id` comes from the `+ $result` union.
- Add language key `column_parent` = 'Parent' to `admin/language/en-gb/catalog/category.php`.

## 3. Backend

### 3.1 `saveField()` (category) — add `parent_id` to whitelist

- Validation:
  - `ctype_digit((string)$value)` (0 allowed = top level);
  - `(int)$value != category_id` → `error_parent` (self-reference);
  - cycle check mirroring the stock form (`admin/controller/catalog/category.php:477-482`): if `parent_id > 0`, `getPaths((int)$parent_id)` — if the result set contains this category's `category_id`, → `error_parent` ("Parent cannot be a child of this category!").
- Normalization: `(int)$value`.
- New language key `error_parent` in `admin/language/en-gb/catalog/category.php` (stock form has `error_parent`? — check; if the key already exists in the stock category language file, reuse it and do not duplicate).

### 3.2 Model `updateField()` (category)

- Whitelist becomes `['name', 'sort_order', 'status', 'image', 'parent_id']`; `parent_id` uses the generic single-column UPDATE branch (oc_category table).

## 4. quickedit.js — new "select" type

- New delegated handler for `data-quick-edit-type="select"`:
  - Click → build `<select class="form-select form-select-sm">` cloning the options from `#quick-parent-options`; pre-select the option matching the cell's `data-quick-edit-value`; show it.
  - `change` → POST (same payload builder: id-field/id/field/value) → success: update cell text to the selected option's label (also store as `data-quick-edit-text`), flash; failure: showAlert, cell text unchanged.
  - While open, clicking the same cell again does nothing (open guard).
  - No Enter/blur/Esc handling (selection is immediate).

## 5. Out of Scope

- Parent quick-select on other lists.
- Multi-level drag-drop reordering.

## 6. Verification

- List shows Parent column with paths; click → dropdown with "None" + all paths; selecting saves; self-reference and cycle attempts rejected with error; top-level ("None") sets parent_id 0; pagination reload keeps it working.