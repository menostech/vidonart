# Categories Parent Quick-Select Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Parent column to the admin Categories list with inline quick-select of the parent category (reusing the quick-edit system, with a new "select" cell type).

**Architecture:** Extends the existing quick-edit pattern: category `saveField()` gains `parent_id` (whitelist + self/cycle validation mirroring the stock form), the list controller provides per-row parent names and a one-time options source, the list template adds the column + hidden options `<select>`, and quickedit.js gains a `select` cell type that clones the options and saves on change.

**Tech Stack:** PHP 8 / OpenCart 4.1.0.4 admin, Twig, jQuery (bundled).

**Reference:** `admin/model/catalog/category.php` — `getCategories()` returns `name` as the full path (GROUP_CONCAT ' > '), `getPaths(int)` returns path rows; stock cycle check at `admin/controller/catalog/category.php:477-482`.

---

## File Structure

| File | Responsibility | Status |
|---|---|---|
| `admin/controller/catalog/category.php` | saveField: add parent_id (validation + cycle check); getList: category_options + parent_name | Modify (Task 1) |
| `admin/model/catalog/category.php` | updateField whitelist + parent_id | Modify (Task 1) |
| `admin/language/en-gb/catalog/category.php` | column_parent key (error_parent already exists — reuse) | Modify (Task 1) |
| `admin/view/template/catalog/category_list.twig` | Parent column + hidden options select + colspan 7 | Modify (Task 2) |
| `admin/view/javascript/quickedit.js` | new "select" cell type handler | Modify (Task 3) |

---

## Task 1: Backend — saveField parent_id, list data, language

**Files:**
- Modify: `admin/controller/catalog/category.php`
- Modify: `admin/model/catalog/category.php`
- Modify: `admin/language/en-gb/catalog/category.php`

- [ ] **Step 1: Add parent_id to saveField whitelist + validation**

In `admin/controller/catalog/category.php`, in `saveField()`, change the fields map from:

```php
			$fields = [
				'name'       => 'error_name',
				'sort_order' => 'error_sort_order',
				'status'     => 'error_status',
				'image'      => 'error_image'
			];
```

to:

```php
			$fields = [
				'name'       => 'error_name',
				'sort_order' => 'error_sort_order',
				'status'     => 'error_status',
				'image'      => 'error_image',
				'parent_id'  => 'error_parent'
			];
```

After the existing `if ($field == 'image' && ...)` block (inside the same `else`), add:

```php
				if ($field == 'parent_id') {
					if (!ctype_digit((string)$value)) {
						$json['error'][$field] = $this->language->get($fields[$field]);
					} elseif ((int)$value == (int)$post_info['category_id']) {
						$json['error'][$field] = $this->language->get($fields[$field]);
					} elseif ((int)$value) {
						// Cycle check (mirror stock save(): getPaths of the new parent must not contain this category)
						$this->load->model('catalog/category');

						$results = $this->model_catalog_category->getPaths((int)$value);

						foreach ($results as $result) {
							if ($result['path_id'] == (int)$post_info['category_id']) {
								$json['error'][$field] = $this->language->get($fields[$field]);
								break;
							}
						}
					}
				}
```

In the normalization block, add:

```php
			if ($field == 'parent_id') {
				$value = (int)$value;
			}
```

- [ ] **Step 2: Add parent_id to the model whitelist**

In `admin/model/catalog/category.php`, `updateField()`, change:

```php
		$fields = ['name', 'sort_order', 'status', 'image'];
```

to:

```php
		$fields = ['name', 'sort_order', 'status', 'image', 'parent_id'];
```

(`parent_id` uses the generic single-column branch — already handled.)

- [ ] **Step 3: List data — options + parent_name**

In `admin/controller/catalog/category.php`, in `getList()`, before building the rows (right after `$this->load->model('catalog/category');`), add:

```php
		$category_names = [];

		$all_categories = $this->model_catalog_category->getCategories();

		foreach ($all_categories as $all_category) {
			$category_names[$all_category['category_id']] = $all_category['name'];
		}

		$data['category_options'] = $all_categories;
```

In the row loop (where `$data['categories'][] = ['edit' => ...] + $result;`), add before the array:

```php
			$parent_name = ($result['parent_id'] > 0 && isset($category_names[$result['parent_id']])) ? $category_names[$result['parent_id']] : '';
```

and add `'parent_name' => $parent_name,` into the row array (before the union).

Before the view return, add `$data['column_parent']` is not needed (language auto-dumps) — but ensure `quick_edit_url`/`placeholder` (already added in the previous feature) remain.

- [ ] **Step 4: Language key**

In `admin/language/en-gb/catalog/category.php`, in the Column block, add:

```php
$_['column_parent'] = 'Parent';
```

(`error_parent` already exists at line 47 — do NOT duplicate it.)

- [ ] **Step 5: Verify & commit**

```powershell
git diff --check
git add admin/controller/catalog/category.php admin/model/catalog/category.php admin/language/en-gb/catalog/category.php
git commit -m "feat: category parent_id quick-edit endpoint and list data"
```

---

## Task 2: List template — Parent column + options source

**Files:**
- Modify: `admin/view/template/catalog/category_list.twig`

- [ ] **Step 1: Add the column**

Read the file. Current thead order: checkbox, image, name, sort_order, status, action. Insert the Parent `<th>` between the name and sort_order headers:

```twig
          <th class="text-start">{{ column_parent }}</th>
```

In the row, insert the Parent cell after the name cell and before the sort-order cell:

```twig
              <td data-quick-edit="parent_id" data-quick-edit-type="select" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ category.category_id }}" data-quick-edit-id-field="category_id" data-quick-edit-value="{{ category.parent_id }}" data-quick-edit-text="{{ category.parent_name ? category.parent_name : '—' }}">{{ category.parent_name ? category.parent_name : '—' }}</td>
```

- [ ] **Step 2: Hidden options select + colspan**

Before the quickedit.js script tag at the end of the file, add:

```twig
<select id="quick-parent-options" class="d-none">
  <option value="0">None</option>
  {% for option in category_options %}
    <option value="{{ option.category_id }}">{{ option.name }}</option>
  {% endfor %}
</select>
```

Change the empty-state `colspan` from 6 to **7**.

- [ ] **Step 3: Verify & commit**

```powershell
git diff --check
git add admin/view/template/catalog/category_list.twig
git commit -m "feat: parent column and quick-select source on category list"
```

---

## Task 3: quickedit.js — "select" cell type

**Files:**
- Modify: `admin/view/javascript/quickedit.js`

- [ ] **Step 1: Add the select handler**

Append inside the IIFE (after the image handler block):

```js
    // ---- select cells (dropdown, save on change) ----
    $(document).on('click', '[data-quick-edit][data-quick-edit-type="select"]', function() {
        var cell = this;

        if ($(cell).find('select').length || $(cell).hasClass('quick-editing')) {
            return;
        }

        $(cell).addClass('quick-editing');

        var url = $(cell).data('quick-edit-url');
        var id = $(cell).data('quick-edit-id');
        var current = $(cell).data('quick-edit-value');

        var select = $('<select>', {
            class: 'form-select form-select-sm',
            style: 'min-width: 180px;'
        });

        $('#quick-parent-options').find('option').each(function() {
            select.append($('<option>', { value: $(this).val(), text: $(this).text() }));
        });

        select.val(String(current));

        $(cell).html(select);

        select.on('change', function() {
            var value = select.val();

            if (value === String(current)) {
                $(cell).html($('<span>').text($(cell).data('quick-edit-text')));
                $(cell).removeClass('quick-editing');
                return;
            }

            var data = {};
            data[$(cell).data('quick-edit-id-field')] = id;
            data['field'] = 'parent_id';
            data['value'] = value;

            post(url, data, function(json) {
                var label = select.find('option:selected').text();

                $(cell).data('quick-edit-value', value);
                $(cell).data('quick-edit-text', label);
                $(cell).html($('<span>').text(label));
                flash(cell);
            }, function(errors) {
                $(cell).html($('<span>').text($(cell).data('quick-edit-text')));
                showAlert(errors);
            }).always(function() {
                $(cell).removeClass('quick-editing');
            });
        });
    });
```

- [ ] **Step 2: Verify & commit**

```powershell
node --check admin/view/javascript/quickedit.js
git diff --check
git add admin/view/javascript/quickedit.js
git commit -m "feat: quick-edit select cell type for parent selection"
```

---

## Self-Review Notes

- **Spec coverage:** Parent column + path display → Tasks 1-2; select interaction ("None" + all paths, save on change) → Tasks 2-3; backend whitelist + self/cycle validation → Task 1 (cycle check mirrors stock `save()` at category.php:477-482); model whitelist → Task 1; language `column_parent` added, `error_parent` reused → Task 1.
- **Placeholder scan:** no TBD; all code complete.
- **Type consistency:** cell attribute names (`data-quick-edit-value` = raw parent_id, `data-quick-edit-text` = display label) match the new JS; POST payload uses the same id-field builder; `category_options`/`category.parent_name` naming consistent between controller and template.
- **Note:** the JS `select.val(String(current))` — `current` may be `0` (top level); the hidden options select includes `value="0"` so it resolves. The cell's `data-quick-edit-text` fallback for top level is `'—'`.