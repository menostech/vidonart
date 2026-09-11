# Admin List Inline Quick Edit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add click-to-edit inline editing to the admin Products and Categories list pages (image, name, model, price, quantity, sort order, status), saving each cell via a per-field ajax endpoint.

**Architecture:** Shared JS component `admin/view/javascript/quickedit.js` driven by data attributes (delegated so pagination reloads keep working), plus one `saveField()` action on each existing controller with per-field validation and a lightweight `updateField()` model method (single-column UPDATE; product/category `name` special-cased into the description table). No changes to the existing list checkbox/delete/sort flows.

**Tech Stack:** PHP 8 / OpenCart 4.1.0.4 admin MVC, Twig, jQuery (bundled).

---

## File Structure

| File | Responsibility | Status |
|---|---|---|
| `admin/model/catalog/product.php` | `updateField()` single-column update (+ name 鈫?description table) | Modify (Task 1) |
| `admin/controller/catalog/product.php` | `saveField()` action + `image_raw`/`price_raw` in list data | Modify (Task 1) |
| `admin/language/en-gb/catalog/product.php` | new error/success keys | Modify (Task 1) |
| `admin/model/catalog/category.php` | `updateField()` (+ name 鈫?description table) | Modify (Task 2) |
| `admin/controller/catalog/category.php` | `saveField()` action + `image_raw` in list data | Modify (Task 2) |
| `admin/language/en-gb/catalog/category.php` | new error/success keys | Modify (Task 2) |
| `admin/view/javascript/quickedit.js` | shared inline-edit component | Create (Task 3) |
| `admin/view/template/catalog/product_list.twig` | cell attributes + image/status cell markup + script tag | Modify (Task 4) |
| `admin/view/template/catalog/category_list.twig` | same for categories | Modify (Task 5) |

---

## Task 1: Products 鈥?model `updateField`, controller `saveField`, language

**Files:**
- Modify: `admin/model/catalog/product.php`
- Modify: `admin/controller/catalog/product.php`
- Modify: `admin/language/en-gb/catalog/product.php`

- [ ] **Step 1: Add `updateField` to the product model**

In `admin/model/catalog/product.php` (namespace `Opencart\Admin\Model\Catalog`), append before the closing brace of the class:

```php
	/**
	 * Update Field
	 *
	 * @param int    $product_id product id
	 * @param string $field      field name
	 * @param mixed  $value      field value
	 *
	 * @return void
	 */
	public function updateField(int $product_id, string $field, $value): void {
		if ($field == 'name') {
			$this->db->query("UPDATE `" . DB_PREFIX . "product_description` SET `name` = '" . $this->db->escape((string)$value) . "' WHERE `product_id` = '" . (int)$product_id . "'");
		} else {
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `" . $field . "` = '" . $this->db->escape((string)$value) . "' WHERE `product_id` = '" . (int)$product_id . "'");
		}
	}
```

- [ ] **Step 2: Add `saveField` to the product controller**

In `admin/controller/catalog/product.php`, append a new public method before the closing brace of the class (after the existing methods):

```php
	/**
	 * Save Field
	 *
	 * @return void
	 */
	public function saveField(): void {
		$this->load->language('catalog/product');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'product_id' => 0,
			'field'      => '',
			'value'      => ''
		];

		$post_info = $this->request->post + $required;

		if (!$json) {
			$fields = [
				'name'       => 'error_name',
				'model'      => 'error_model',
				'price'      => 'error_price',
				'quantity'   => 'error_quantity',
				'sort_order' => 'error_sort_order',
				'status'     => 'error_status',
				'image'      => 'error_image'
			];

			if (!isset($fields[$post_info['field']])) {
				$json['error']['warning'] = $this->language->get('error_field');
			} else {
				$field = $post_info['field'];
				$value = $post_info['value'];

				if ($field == 'name' && !oc_validate_length($value, 1, 255)) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'model' && !oc_validate_length($value, 0, 64)) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'price' && (!is_numeric($value) || (float)$value < 0)) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'quantity' && (!ctype_digit((string)$value))) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'sort_order' && !ctype_digit((string)$value)) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'status' && !in_array((string)$value, ['0', '1'])) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'image' && (!oc_validate_length($value, 1, 255) || !is_file(DIR_IMAGE . html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8')))) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}
			}
		}

		if (!$json) {
			$this->load->model('catalog/product');

			$value = ($field == 'status') ? (int)$value : $value;

			if ($field == 'price') {
				$value = (float)$value;
			}

			if ($field == 'quantity' || $field == 'sort_order') {
				$value = (int)$value;
			}

			$this->model_catalog_product->updateField((int)$post_info['product_id'], $field, $value);

			$json['success'] = $this->language->get('text_success_field');
			$json['value'] = (string)$value;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
```

- [ ] **Step 3: Add raw values to the product list data**

In `admin/controller/catalog/product.php`, inside `getList()`, in the `$data['products'][] = [...] + $result;` array (around line 370), add two keys before the union:

```php
		$data['products'][] = [
			'image'      => $this->model_tool_image->resize($image, 40, 40),
			'image_raw'  => $result['image'],
			'price_raw'  => $result['price'],
			'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
			'edit'       => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . ($result['master_id'] ? '&master_id=' . $result['master_id'] : '') . $url),
			'variant'    => (!$result['master_id'] ? $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&master_id=' . $result['product_id'] . $url) : '')
		] + $result;
```

(Apply only the additions: `image_raw` and `price_raw`; keep the existing keys verbatim.)

- [ ] **Step 4: Add product language keys**

Append to `admin/language/en-gb/catalog/product.php`:

```php
// Quick edit
$_['text_success_field']  = 'Success: You have modified this field!';
$_['error_field']         = 'Warning: Invalid field!';
$_['error_model']         = 'Model must be 64 characters or less!';
$_['error_price']         = 'Price must be a number greater than or equal to zero!';
$_['error_quantity']      = 'Quantity must be a whole number!';
$_['error_sort_order']    = 'Sort Order must be a whole number!';
$_['error_status']        = 'Invalid status value!';
$_['error_image']         = 'Invalid image!';
```

- [ ] **Step 5: Verify & commit**

```powershell
git diff --check
git add admin/model/catalog/product.php admin/controller/catalog/product.php admin/language/en-gb/catalog/product.php
git commit -m "feat: product saveField quick-edit endpoint"
```

---

## Task 2: Categories 鈥?model `updateField`, controller `saveField`, language

**Files:**
- Modify: `admin/model/catalog/category.php`
- Modify: `admin/controller/catalog/category.php`
- Modify: `admin/language/en-gb/catalog/category.php`

- [ ] **Step 1: Add `updateField` to the category model**

In `admin/model/catalog/category.php` (namespace `Opencart\Admin\Model\Catalog`), append before the closing brace of the class:

```php
	/**
	 * Update Field
	 *
	 * @param int    $category_id category id
	 * @param string $field       field name
	 * @param mixed  $value       field value
	 *
	 * @return void
	 */
	public function updateField(int $category_id, string $field, $value): void {
		if ($field == 'name') {
			$this->db->query("UPDATE `" . DB_PREFIX . "category_description` SET `name` = '" . $this->db->escape((string)$value) . "' WHERE `category_id` = '" . (int)$category_id . "'");
		} else {
			$this->db->query("UPDATE `" . DB_PREFIX . "category` SET `" . $field . "` = '" . $this->db->escape((string)$value) . "' WHERE `category_id` = '" . (int)$category_id . "'");
		}
	}
```

- [ ] **Step 2: Add `saveField` to the category controller**

In `admin/controller/catalog/category.php`, append before the closing brace of the class:

```php
	/**
	 * Save Field
	 *
	 * @return void
	 */
	public function saveField(): void {
		$this->load->language('catalog/category');

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/category')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'category_id' => 0,
			'field'       => '',
			'value'       => ''
		];

		$post_info = $this->request->post + $required;

		if (!$json) {
			$fields = [
				'name'       => 'error_name',
				'sort_order' => 'error_sort_order',
				'status'     => 'error_status',
				'image'      => 'error_image'
			];

			if (!isset($fields[$post_info['field']])) {
				$json['error']['warning'] = $this->language->get('error_field');
			} else {
				$field = $post_info['field'];
				$value = $post_info['value'];

				if ($field == 'name' && !oc_validate_length($value, 1, 255)) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'sort_order' && !ctype_digit((string)$value)) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'status' && !in_array((string)$value, ['0', '1'])) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}

				if ($field == 'image' && (!oc_validate_length($value, 1, 255) || !is_file(DIR_IMAGE . html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8')))) {
					$json['error'][$field] = $this->language->get($fields[$field]);
				}
			}
		}

		if (!$json) {
			$this->load->model('catalog/category');

			$value = ($field == 'status') ? (int)$value : $value;

			if ($field == 'sort_order') {
				$value = (int)$value;
			}

			$this->model_catalog_category->updateField((int)$post_info['category_id'], $field, $value);

			$json['success'] = $this->language->get('text_success_field');
			$json['value'] = (string)$value;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
```

- [ ] **Step 3: Add raw image to the category list data**

In `admin/controller/catalog/category.php`, in `getList()`, in the `$data['categories'][] = [...] + $result;` array, add `'image_raw' => $result['image'],` alongside the existing `'image' => $this->model_tool_image->resize($image, 40, 40),` key.

- [ ] **Step 4: Add category language keys**

Append to `admin/language/en-gb/catalog/category.php`:

```php
// Quick edit
$_['text_success_field']  = 'Success: You have modified this field!';
$_['error_field']         = 'Warning: Invalid field!';
$_['error_sort_order']    = 'Sort Order must be a whole number!';
$_['error_status']        = 'Invalid status value!';
$_['error_image']         = 'Invalid image!';
```

- [ ] **Step 5: Verify & commit**

```powershell
git diff --check
git add admin/model/catalog/category.php admin/controller/catalog/category.php admin/language/en-gb/catalog/category.php
git commit -m "feat: category saveField quick-edit endpoint"
```

---

## Task 3: Shared JS component 鈥?`admin/view/javascript/quickedit.js`

**Files:**
- Create: `admin/view/javascript/quickedit.js`

- [ ] **Step 1: Create the component**

Create `admin/view/javascript/quickedit.js` with this exact content:

```js
(function($) {
    'use strict';

    function post(url, data, done, fail) {
        $.ajax({
            url: url,
            type: 'post',
            data: data,
            dataType: 'json',
            cache: false,
            success: function(json) {
                if (json['error']) {
                    fail(json['error']);
                } else {
                    done(json);
                }
            },
            error: function() {
                fail({ 'warning': 'Request failed' });
            }
        });
    }

    function showAlert(errors) {
        var text = '';

        for (var key in errors) {
            text += errors[key] + ' ';
        }

        $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + text + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
    }

    function flash(cell) {
        $(cell).addClass('table-primary');

        setTimeout(function() {
            $(cell).removeClass('table-primary');
        }, 600);
    }

    // ---- text / number cells ----
    $(document).on('click', '[data-quick-edit][data-quick-edit-type="text"], [data-quick-edit][data-quick-edit-type="number"]', function(e) {
        if ($(this).find('input').length) {
            return;
        }

        var cell = this;
        var field = $(cell).data('quick-edit');
        var type = $(cell).data('quick-edit-type');
        var url = $(cell).data('quick-edit-url');
        var id = $(cell).data('quick-edit-id');
        var original = $(cell).data('quick-edit-value');

        var input = $('<input>', {
            type: type == 'number' ? 'number' : 'text',
            value: original,
            class: 'form-control form-control-sm',
            style: 'min-width: 120px;'
        });

        $(cell).html(input);
        input.trigger('focus').select();

        var saving = false;

        function save() {
            if (saving) {
                return;
            }

            saving = true;

            var value = input.val();

            if (value === original) {
                $(cell).html($('<span>').text(original));
                return;
            }

            var data = {};
            data[$(cell).data('quick-edit-id-field')] = id;
            data['field'] = field;
            data['value'] = value;

            post(url, data, function(json) {
                $(cell).html($('<span>').text(json['value'] !== undefined ? json['value'] : value));
                $(cell).data('quick-edit-value', json['value'] !== undefined ? json['value'] : value);
                flash(cell);
            }, function(errors) {
                $(cell).html($('<span>').text(original));
                showAlert(errors);
            });
        }

        function cancel() {
            $(cell).html($('<span>').text(original));
        }

        input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                save();
            }

            if (e.key === 'Escape') {
                cancel();
            }
        });

        input.on('blur', function() {
            save();
        });
    });

    // ---- status cells ----
    $(document).on('click', '[data-quick-edit][data-quick-edit-type="status"]', function(e) {
        var cell = this;
        var url = $(cell).data('quick-edit-url');
        var id = $(cell).data('quick-edit-id');
        var current = $(cell).data('quick-edit-value');

        if ($(cell).hasClass('quick-editing')) {
            return;
        }

        $(cell).addClass('quick-editing');

        var next = current == '1' ? '0' : '1';

        var data = {};
        data[$(cell).data('quick-edit-id-field')] = id;
        data['field'] = 'status';
        data['value'] = next;

        post(url, data, function(json) {
            $(cell).data('quick-edit-value', next);
            $(cell).find('.fa-square-check, .fa-square').removeClass('fa-square-check fa-square').addClass(next == '1' ? 'fa-square-check' : 'fa-square');
            $(cell).closest('tr').toggleClass('table-active opacity-50', next != '1');
            flash(cell);
        }, function(errors) {
            showAlert(errors);
        }).always(function() {
            $(cell).removeClass('quick-editing');
        });
    });

    // ---- image cells (file manager pick -> auto save) ----
    $(document).on('change', '[data-quick-edit][data-quick-edit-type="image"] input[type="hidden"]', function() {
        var cell = $(this).closest('[data-quick-edit][data-quick-edit-type="image"]');
        var url = cell.data('quick-edit-url');
        var id = cell.data('quick-edit-id');
        var value = $(this).val();

        var data = {};
        data[cell.data('quick-edit-id-field')] = id;
        data['field'] = 'image';
        data['value'] = value;

        post(url, data, function(json) {
            flash(cell.get(0));
        }, function(errors) {
            showAlert(errors);
        });
    });
})(jQuery);
```

- [ ] **Step 2: Verify & commit**

```powershell
git diff --check
git add admin/view/javascript/quickedit.js
git commit -m "feat: shared quick-edit js component"
```

Note: the file is plain jQuery 鈥?loaded after `common.js` (which provides `$`). The `data-quick-edit-id`/`data-quick-edit-url` values come from the Twig rendering in Tasks 4鈥?.

---

## Task 4: Product list template

**Files:**
- Modify: `admin/view/template/catalog/product_list.twig`

- [ ] **Step 1: Add quick-edit attributes to the cells**

Read the file first. Apply these edits to the product row (inside `{% for product in products %}`):

Edit 1 鈥?image cell (currently `<td class="text-center"><img src="{{ product.image }}" alt="{{ product.name }}" class="img-thumbnail"/></td>`):

```twig
              <td class="text-center" data-quick-edit="image" data-quick-edit-type="image" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id">
                <div class="border rounded d-inline-block">
                  <img src="{{ product.image }}" alt="" title="" id="thumb-quick-{{ product.product_id }}" data-oc-placeholder="{{ placeholder }}" class="img-fluid" style="width: 40px; height: 40px;"/>
                  <input type="hidden" name="quick-image-{{ product.product_id }}" value="{{ product.image_raw }}" id="input-quick-{{ product.product_id }}-image"/>
                  <div class="d-grid">
                    <button type="button" data-oc-toggle="image" data-oc-target="#input-quick-{{ product.product_id }}-image" data-oc-thumb="#thumb-quick-{{ product.product_id }}" class="btn btn-primary rounded-0 btn-sm"><i class="fa-solid fa-pencil"></i></button>
                  </div>
                </div>
              </td>
```

Edit 2 鈥?name cell:

```twig
              <td data-quick-edit="name" data-quick-edit-type="text" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id" data-quick-edit-value="{{ product.name }}">{{ product.name }}</td>
```

Edit 3 鈥?model cell:

```twig
              <td class="d-none d-lg-table-cell" data-quick-edit="model" data-quick-edit-type="text" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id" data-quick-edit-value="{{ product.model }}">{{ product.model }}</td>
```

Edit 4 鈥?price cell (keep the special/sale display; wrap with the quick-edit attributes on the td; `data-quick-edit-value` uses the raw price):

```twig
              <td class="text-end" data-quick-edit="price" data-quick-edit-type="number" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id" data-quick-edit-value="{{ product.price_raw }}">
                {% if product.special %}<span style="text-decoration: line-through;">{{ product.price }}</span>
                  <br/>
                  <div class="text-danger">{{ product.special }}</div>
                {% else %}
                  {{ product.price }}
                {% endif %}</td>
```

Edit 5 鈥?quantity cell:

```twig
              <td class="text-end" data-quick-edit="quantity" data-quick-edit-type="number" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id" data-quick-edit-value="{{ product.quantity }}">
                {% if product.quantity <= 0 %}
                  <span class="badge bg-warning">{{ product.quantity }}</span>
                {% elseif product.quantity <= 5 %}
                  <span class="badge bg-danger">{{ product.quantity }}</span>
                {% else %}
                  <span class="badge bg-success">{{ product.quantity }}</span>
                {% endif %}</td>
```

Edit 6 鈥?add a status cell. Insert a new `<td>` right after the sort-order cell (the current last data column) and before the action cell, and add the sort-order attributes to the sort-order cell. Locate the sort-order cell (contains `{{ product.sort_order }}`); change it to:

```twig
              <td class="text-end" data-quick-edit="sort_order" data-quick-edit-type="number" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id" data-quick-edit-value="{{ product.sort_order }}">{{ product.sort_order }}</td>
```

Then insert after it:

```twig
              <td class="text-center" data-quick-edit="status" data-quick-edit-type="status" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ product.product_id }}" data-quick-edit-id-field="product_id" data-quick-edit-value="{{ product.status }}"><i class="fa-solid {{ product.status ? 'fa-square-check' : 'fa-square' }}"></i></td>
```

Also update the empty-state row `colspan` from 7 to 8 (one more column).

Edit 7 鈥?load the script at the end of the file (before the closing tags; the list template is a partial so the script tag must be inside it):

```twig
<script src="admin/view/javascript/quickedit.js" type="text/javascript"></script>
```

- [ ] **Step 2: Pass `quick_edit_url` and `placeholder` from the product controller**

In `admin/controller/catalog/product.php`, in `getList()` before `return $this->load->view('catalog/product_list', $data);`, add:

```php
		$data['quick_edit_url'] = $this->url->link('catalog/product.saveField', 'user_token=' . $this->session->data['user_token']);

		// Placeholder
		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 40, 40);
```

- [ ] **Step 3: Verify & commit**

```powershell
git diff --check
git add admin/view/template/catalog/product_list.twig admin/controller/catalog/product.php
git commit -m "feat: wire quick-edit cells on product list"
```

---

## Task 5: Category list template

**Files:**
- Modify: `admin/view/template/catalog/category_list.twig`
- Modify: `admin/controller/catalog/category.php`

- [ ] **Step 1: Add quick-edit attributes to the cells**

Read the file first. The category row has: checkbox, image cell, name cell, sort-order cell, action cell. Apply:

Edit 1 鈥?image cell 鈫?`data-quick-edit="image" data-quick-edit-type="image" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ category.category_id }}" data-quick-edit-id-field="category_id"` with the file-manager markup (mirror Task 4 Edit 1, using `category.image_raw` and `{{ category.category_id }}` in the ids/name).

Edit 2 鈥?name cell:

```twig
              <td data-quick-edit="name" data-quick-edit-type="text" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ category.category_id }}" data-quick-edit-id-field="category_id" data-quick-edit-value="{{ category.name }}">{{ category.name }}</td>
```

Edit 3 鈥?sort-order cell 鈫?`data-quick-edit="sort_order" data-quick-edit-type="number"` with `category.sort_order` (mirror Task 4 pattern).

Edit 4 鈥?insert status cell after the sort-order cell, before the action cell:

```twig
              <td class="text-center" data-quick-edit="status" data-quick-edit-type="status" data-quick-edit-url="{{ quick_edit_url }}" data-quick-edit-id="{{ category.category_id }}" data-quick-edit-id-field="category_id" data-quick-edit-value="{{ category.status }}"><i class="fa-solid {{ category.status ? 'fa-square-check' : 'fa-square' }}"></i></td>
```

Adjust the empty-state `colspan` if the table has one (add one for the new column).

Edit 5 鈥?script tag at the end:

```twig
<script src="admin/view/javascript/quickedit.js" type="text/javascript"></script>
```

- [ ] **Step 2: Pass `quick_edit_url` and `placeholder` from the category controller**

In `admin/controller/catalog/category.php`, in `getList()` before the view return:

```php
		$data['quick_edit_url'] = $this->url->link('catalog/category.saveField', 'user_token=' . $this->session->data['user_token']);

		// Placeholder
		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 40, 40);
```

- [ ] **Step 3: Verify & commit**

```powershell
git diff --check
git add admin/view/template/catalog/category_list.twig admin/controller/catalog/category.php
git commit -m "feat: wire quick-edit cells on category list"
```

---

## Self-Review Notes

- **Spec coverage:** saveField endpoints (products Task 1, categories Task 2) with whitelist + per-field validation + permission checks + JSON contract; shared JS component Task 3 (text/number Enter/blur/Esc, status click-toggle, image file-manager auto-save, delegated events); list templates Task 4/5 (all 7 product cells + 4 category cells, script includes). Existing checkbox/delete/sort/pagination flows untouched 鈥?quickedit binds only to marked cells.
- **Placeholder scan:** no TBD; all code complete.
- **Type consistency:** attribute names (`data-quick-edit`, `-type`, `-url`, `-id`, `-value`), POST keys (`id`, `field`, `value`), controller `saveField` names, and model `updateField` signatures consistent across all tasks. `image_raw`/`price_raw` added in the list controllers and consumed in the templates.
- **Notes:** the JS sends `{id, field, value}` while the spec's controller reads `product_id`/`category_id` 鈥?the `saveField` controllers use `$required` with `product_id`/`category_id` keys, so the JS must send those exact keys. Update the JS POST data in Task 3 accordingly: for products `{product_id: id, ...}` / categories `{category_id: id, ...}` cannot both be fixed in one shared file 鈥?use `data-quick-edit-id-field` attribute on cells (`product_id` for products, `category_id` for categories) and read it in the JS: `var data = {}; data[$(cell).data('quick-edit-id-field')] = id; data.field = field; data.value = value;`. Apply this in Tasks 3鈥?: add `data-quick-edit-id-field="product_id"` (products) / `"category_id"` (categories) to every cell, and build the POST payload from it in quickedit.js.
