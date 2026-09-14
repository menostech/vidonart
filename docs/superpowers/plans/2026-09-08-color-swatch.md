# Product Color Swatch Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add color swatches to option values (admin hex color per value) and render them on the product detail page (select/radio options) and product cards (hover swaps image, click navigates with the color preselected).

**Architecture:** Core modification consistent with the project's existing pattern: `oc_option_value.color` column (SELECT *-based models pass it through automatically), admin option form gains a color input, storefront renders swatches driven by the `color` field; swatch data for cards is built centrally in the `product/thumb` controller; detail-page preselect via URL query params handled in inline JS.

**Tech Stack:** PHP 8 / OpenCart 4.1.0.4, Twig, jQuery, SCSS.

**Key facts (verified):** `admin/model/catalog/option.php` `getValue()`/`getValues()` use `SELECT *` → the new column flows through automatically; `addValue()` has explicit INSERT columns (needs `color` added); `catalog/model/catalog/product.php` `getOptions()`/`getOptionValues()` also use `SELECT *` → `color` appears in option value arrays automatically; `product/thumb` receives `product_id` from all callers.

---

## File Structure

| File | Responsibility | Status |
|---|---|---|
| `install/opencart-en-gb.sql` (+fr-fr) | `color` column in oc_option_value CREATE TABLE | Modify (Task 1) |
| `admin/model/catalog/option.php` | addValue INSERT includes color | Modify (Task 1) |
| `admin/view/template/catalog/option_form.twig` | color input per value row (+ JS add-row) | Modify (Task 1) |
| `admin/language/en-gb/catalog/option.php` | entry_color | Modify (Task 1) |
| `catalog/view/template/product/product.twig` | swatch rendering for select/radio + preselect JS | Modify (Task 2) |
| `catalog/view/stylesheet/stylesheet.scss` + `.css` | swatch styles (detail + card) | Modify (Tasks 2-3) |
| `catalog/controller/product/thumb.php` | central swatch data building | Modify (Task 3) |
| `catalog/view/template/product/thumb.twig` | swatch row + hover/click JS | Modify (Task 3) |

---

## Task 1: Database + Admin

**Files:**
- Modify: `install/opencart-en-gb.sql`, `install/opencart-fr-fr.sql`
- Modify: `admin/model/catalog/option.php`
- Modify: `admin/view/template/catalog/option_form.twig`
- Modify: `admin/language/en-gb/catalog/option.php`

- [ ] **Step 1: Add the column to the install SQL**

In both `install/opencart-en-gb.sql` and `install/opencart-fr-fr.sql`, find the `CREATE TABLE` for `oc_option_value` and add the `color` column after `image`:

```sql
  `image` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '',
```

(Keep the exact existing column definitions around it unchanged.)

For the LIVE database, the user must run once (documented in the final summary):

```sql
ALTER TABLE `oc_option_value` ADD `color` VARCHAR(7) NOT NULL DEFAULT '' AFTER `image`;
```

- [ ] **Step 2: Persist color in addValue**

In `admin/model/catalog/option.php`, `addValue()`, both INSERT queries gain the color column:

```php
		if ($data['option_value_id']) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "option_value` SET `option_value_id` = '" . (int)$data['option_value_id'] . "', `option_id` = '" . (int)$option_id . "', `image` = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "', `color` = '" . $this->db->escape((string)($data['color'] ?? '')) . "', `sort_order` = '" . (int)$data['sort_order'] . "'");

			$option_value_id = $data['option_value_id'];
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = '" . (int)$option_id . "', `image` = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "', `color` = '" . $this->db->escape((string)($data['color'] ?? '')) . "', `sort_order` = '" . (int)$data['sort_order'] . "'");

			$option_value_id = $this->db->getLastId();
		}
```

- [ ] **Step 3: Color input in the option form**

In `admin/view/template/catalog/option_form.twig`, the option value row table (thead around line 78): add a color column header:

```twig
          <th>{{ entry_color }}</th>
```

In the value row (around line 96-104, next to the image td), add a td with a color input:

```twig
          <td style="width: 80px;"><input type="color" name="option_value[{{ option_value_row }}][color]" value="{{ option_value.color }}" id="input-color-{{ option_value_row }}" class="form-control form-control-color"/></td>
```

In the JS add-value template (the `html += ...` string that appends a new value row), add the same td after the image td:

```js
    html += '  <td style="width: 80px;"><input type="color" name="option_value[' + option_value_row + '][color]" value="#000000" id="input-color-' + option_value_row + '" class="form-control form-control-color"/></td>';
```

- [ ] **Step 4: Language key**

In `admin/language/en-gb/catalog/option.php`, in the Entry section:

```php
$_['entry_color'] = 'Color';
```

- [ ] **Step 5: Verify controller passthrough**

Read `admin/controller/catalog/option.php` `save()` — confirm the option_value POST array is passed to `addValue()` without field stripping (stock passes `$option_value` directly). If any normalization strips unknown keys, add `'color' => $option_value['color'] ?? ''` to the passed array. Report what you found.

- [ ] **Step 6: Verify & commit**

```powershell
git diff --check
git add install/opencart-en-gb.sql install/opencart-fr-fr.sql admin/model/catalog/option.php admin/view/template/catalog/option_form.twig admin/language/en-gb/catalog/option.php
git commit -m "feat: option value color field in admin"
```

---

## Task 2: Product Detail Page Swatches

**Files:**
- Modify: `catalog/view/template/product/product.twig`
- Modify: `catalog/view/stylesheet/stylesheet.scss` (+ recompile `.css`)

- [ ] **Step 1: Select option → swatch group**

In `product.twig`, the `option.type == 'select'` branch (currently a `<select name="option[...]">`). Wrap: if any value has `color`, render hidden select + swatch buttons. Replace the select block with:

```twig
                    {% if option.type == 'select' %}
                      <div class="mb-3{% if option.required %} required{% endif %}">
                        <label for="input-option-{{ option.product_option_id }}" class="form-label">{{ option.name }}</label>
                        {% set has_swatch = false %}
                        {% for option_value in option.product_option_value %}
                          {% if option_value.color %}{% set has_swatch = true %}{% endif %}
                        {% endfor %}
                        {% if has_swatch %}
                          <select name="option[{{ option.product_option_id }}]" id="input-option-{{ option.product_option_id }}" class="form-select d-none">
                            <option value="">{{ text_select }}</option>
                            {% for option_value in option.product_option_value %}
                              <option value="{{ option_value.product_option_value_id }}"{% if option_value.price %} data-price="{{ option_value.price_prefix }}{{ option_value.price }}"{% endif %}>{{ option_value.name }}{% if option_value.price %} ({{ option_value.price_prefix }}{{ option_value.price }}){% endif %}</option>
                            {% endfor %}
                          </select>
                          <div class="va-swatches" data-target="#input-option-{{ option.product_option_id }}">
                            {% for option_value in option.product_option_value %}
                              <button type="button" class="va-swatch" data-value="{{ option_value.product_option_value_id }}" data-color="{{ option_value.color }}" style="background: {{ option_value.color }};" title="{{ option_value.name }}"></button>
                            {% endfor %}
                          </div>
                        {% else %}
                          <select name="option[{{ option.product_option_id }}]" id="input-option-{{ option.product_option_id }}" class="form-select">
                            <option value="">{{ text_select }}</option>
                            {% for option_value in option.product_option_value %}
                              <option value="{{ option_value.product_option_value_id }}">{{ option_value.name }}
                                {% if option_value.price %}
                                  ({{ option_value.price_prefix }}{{ option_value.price }})
                                {% endif %}</option>
                            {% endfor %}
                          </select>
                        {% endif %}
                        <div id="error-option-{{ option.product_option_id }}" class="invalid-feedback"></div>
                      </div>
                    {% endif %}
```

- [ ] **Step 2: Radio option → swatch circles**

In the `option.type == 'radio'` branch, add the same `has_swatch` logic: when any value has a color, render swatch buttons that set the hidden radio inputs (the existing radio inputs stay in the DOM but are visually hidden via `.d-none` on the form-check group), i.e.:

```twig
                        {% set has_swatch = false %}
                        {% for option_value in option.product_option_value %}
                          {% if option_value.color %}{% set has_swatch = true %}{% endif %}
                        {% endfor %}
                        {% if has_swatch %}
                          <div class="va-swatches mb-2" data-target="input-option-{{ option.product_option_id }}">
                            {% for option_value in option.product_option_value %}
                              <button type="button" class="va-swatch" data-value="{{ option_value.product_option_value_id }}" data-color="{{ option_value.color }}" style="background: {{ option_value.color }};" title="{{ option_value.name }}"></button>
                            {% endfor %}
                          </div>
                          <div class="d-none">
                            {% for option_value in option.product_option_value %}
                              <div class="form-check">
                                <input type="radio" name="option[{{ option.product_option_id }}]" value="{{ option_value.product_option_value_id }}" id="input-option-value-{{ option_value.product_option_value_id }}" class="form-check-input"/>
                                <label for="input-option-value-{{ option_value.product_option_value_id }}" class="form-check-label">{{ option_value.name }}{% if option_value.price %} ({{ option_value.price_prefix }}{{ option_value.price }}){% endif %}</label>
                              </div>
                            {% endfor %}
                          </div>
                        {% else %}
                          (existing radio markup unchanged)
                        {% endif %}
```

(Note: the existing radio markup must remain untouched in the `{% else %}` branch; the hidden radios keep the same `name` so the form POST contract is unchanged.)

- [ ] **Step 3: Swatch selection JS + URL preselect**

Append to the existing `<script type="text/javascript"><!-- ... //--></script>` block in `product.twig` (before the closing `//-->`):

```js
    // ---- color swatches ----
    $('.va-swatches').on('click', '.va-swatch', function() {
        var container = $(this).closest('.va-swatches');
        var target = container.data('target');

        $(container).find('.va-swatch').removeClass('active');
        $(this).addClass('active');

        if ($(target).is('select')) {
            $(target).val($(this).data('value')).trigger('change');
        } else {
            $('#' + target + ' input[type="radio"][value="' + $(this).data('value') + '"]').prop('checked', true);
        }
    });

    // URL preselect: ?option[<product_option_id>]=<product_option_value_id>
    $(document).ready(function() {
        var params = new URLSearchParams(location.search);

        $('.va-swatches').each(function() {
            var container = $(this);
            var target = container.data('target');
            var id = $(target).is('select') ? $(target).attr('id') : target;
            var match = params.get('option[' + id.replace('input-option-', '') + ']');

            if (!match) {
                return;
            }

            var swatch = container.find('.va-swatch[data-value="' + match + '"]');

            if (swatch.length) {
                swatch.trigger('click');
            }
        });
    });
```

- [ ] **Step 4: Swatch styles**

Append to `catalog/view/stylesheet/stylesheet.scss`:

```scss
/* ---------- color swatches ---------- */
.va-swatches {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.va-swatch {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1px solid var(--va-border);
  padding: 0;
  cursor: pointer;
  transition: border-color .2s ease, box-shadow .2s ease;
}

.va-swatch:hover {
  border-color: var(--va-ink);
}

.va-swatch.active {
  border-color: var(--va-ink);
  box-shadow: 0 0 0 2px var(--va-surface), 0 0 0 3.5px var(--va-ink);
}
```

- [ ] **Step 5: Recompile & verify & commit**

```powershell
npx --yes sass catalog/view/stylesheet/stylesheet.scss catalog/view/stylesheet/stylesheet.css --style=compressed
Remove-Item catalog/view/stylesheet/stylesheet.css.map -ErrorAction SilentlyContinue
git diff --check
git add catalog/view/template/product/product.twig catalog/view/stylesheet/stylesheet.scss catalog/view/stylesheet/stylesheet.css
git commit -m "feat: color swatches on product detail page"
```

---

## Task 3: Product Card Swatches

**Files:**
- Modify: `catalog/controller/product/thumb.php`
- Modify: `catalog/view/template/product/thumb.twig`
- Modify: `catalog/view/stylesheet/stylesheet.scss` (+ recompile `.css`)

- [ ] **Step 1: Build swatch data in the thumb controller**

In `catalog/controller/product/thumb.php`, `index()`, after the existing URL/language setup and before `return $this->load->view('product/thumb', $data);`, add:

```php
		// Swatches (first option that has colored values)
		$data['swatches'] = [];
		$data['swatch_option_id'] = 0;

		if (!empty($data['product_id'])) {
			$this->load->model('catalog/product');

			$this->load->model('tool/image');

			$options = $this->model_catalog_product->getOptions((int)$data['product_id']);

			foreach ($options as $option) {
				$colored = [];

				foreach ($option['product_option_value'] as $value) {
					if (!empty($value['color'])) {
						$image = '';

						if ($value['image'] && is_file(DIR_IMAGE . html_entity_decode($value['image'], ENT_QUOTES, 'UTF-8'))) {
							$image = $this->model_tool_image->resize(html_entity_decode($value['image'], ENT_QUOTES, 'UTF-8'), 500, 500);
						}

						$colored[] = [
							'product_option_value_id' => $value['product_option_value_id'],
							'color'                   => $value['color'],
							'image'                   => $image,
							'name'                    => $value['name']
						];
					}
				}

				if ($colored) {
					$data['swatches'] = $colored;
					$data['swatch_option_id'] = (int)$option['product_option_id'];
					break;
				}
			}
		}
```

- [ ] **Step 2: Swatch row in the card template**

In `catalog/view/template/product/thumb.twig`, inside the `.description` div (after the rating block, before the `.va-customize` link), add:

```twig
      {% if swatches %}
        <div class="va-card-swatches" data-option-id="{{ swatch_option_id }}">
          {% for swatch in swatches %}
            <button type="button" class="va-card-swatch" data-value="{{ swatch.product_option_value_id }}" data-color="{{ swatch.color }}" data-image="{{ swatch.image }}" style="background: {{ swatch.color }};" title="{{ swatch.name }}"></button>
          {% endfor %}
        </div>
      {% endif %}
```

And add an id to the main image (change the image `<a href="{{ href }}"><img src="{{ thumb }}" ...` to include `id="va-card-image-{{ product_id }}"`).

- [ ] **Step 3: Card swatch JS**

Append to `catalog/view/template/product/thumb.twig` (before the final `</div>`):

```twig
<script type="text/javascript"><!--
$(document).ready(function() {
    $('.va-card-swatches').each(function() {
        var container = $(this);
        var image = $('#' + 'va-card-image-' + {{ product_id }});

        if (!image.length) {
            return;
        }

        var original = image.attr('src');

        container.on('mouseenter', '.va-card-swatch', function() {
            var hover = $(this).data('image');

            if (hover) {
                image.attr('src', hover);
            }
        });

        container.on('mouseleave', function() {
            image.attr('src', original);
        });

        container.on('click', '.va-card-swatch', function() {
            var href = '{{ href|escape('js') }}';

            var sep = href.indexOf('?') !== -1 ? '&' : '?';

            location = href + sep + 'option[' + container.data('option-id') + ']=' + $(this).data('value');
        });
    });
});
//--></script>
```

- [ ] **Step 4: Card swatch styles**

Append to `catalog/view/stylesheet/stylesheet.scss`:

```scss
.va-card-swatches {
  display: flex;
  gap: 6px;
  margin-top: 8px;
}

.va-card-swatch {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 1px solid var(--va-border);
  padding: 0;
  cursor: pointer;
  transition: border-color .2s ease;
}

.va-card-swatch:hover {
  border-color: var(--va-ink);
}
```

- [ ] **Step 5: Recompile & verify & commit**

```powershell
npx --yes sass catalog/view/stylesheet/stylesheet.scss catalog/view/stylesheet/stylesheet.css --style=compressed
Remove-Item catalog/view/stylesheet/stylesheet.css.map -ErrorAction SilentlyContinue
git diff --check
git add catalog/controller/product/thumb.php catalog/view/template/product/thumb.twig catalog/view/stylesheet/stylesheet.scss catalog/view/stylesheet/stylesheet.css
git commit -m "feat: color swatches on product cards"
```

---

## Self-Review Notes

- **Spec coverage:** DB column + install SQL + live ALTER → Task 1; admin color picker + persistence → Task 1; detail-page select/radio swatches + URL preselect → Task 2; card swatches (hover image swap, click preselect link) → Task 3. Fallbacks: values without color keep existing image/text rendering (else branches untouched). Single-language assumption holds.
- **Placeholder scan:** all code complete; no TBD.
- **Type consistency:** `va-swatch`/`va-card-swatch` naming consistent between templates, JS, and SCSS; swatch data keys (`product_option_value_id`, `color`, `image`, `name`) consistent between thumb.php and thumb.twig; URL preselect key format `option[<product_option_id>]` consistent between product.twig JS and thumb.twig click link.
- **Deployment note:** the live DB needs the ALTER TABLE from Task 1 Step 1 before admin/storefront color features work (otherwise the admin form's `option_value.color` read fails on the missing column).