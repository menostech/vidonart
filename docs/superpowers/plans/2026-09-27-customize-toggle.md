---
change: customize-toggle
design-doc: docs/openspec/changes/customize-toggle/design.md
base-ref: dd6e9b3bb2338ab144e668f181bd6f477246ec4d
---

# Customize Toggle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Progress is tracked in the change's tasks.md via Comet task IDs.

**Goal:** Add a backend Yes/No switch that hides all four Customize entry points on the storefront when off, with zero behavior change when on.

**Architecture:** One new store setting (`config_customize_status`, persisted via the existing `editSetting('config', POST)` flow, no migration); each of the four catalog controllers passes an explicit boolean flag and the Twig templates guard the entry points with `{% if %}`. Direct URL access to `information/custom` is intentionally untouched.

**Tech Stack:** PHP 8 / OpenCart 4.1.0.4 (admin settings + catalog MVC), Twig.

**Spec:** `docs/openspec/changes/customize-toggle/specs/customize-visibility/spec.md` 鈥?the plan argues from the spec; executors read both. Design decisions live in `docs/openspec/changes/customize-toggle/design.md`.

## Global Constraints

- Artifact language is English; user-facing copy stays English (single-language storefront).
- No database migration 鈥?the setting lives in the `oc_setting` key-value store.
- Never-saved setting MUST behave as enabled (both in the admin form default and on the storefront).
- Do not touch cart/checkout logic, the `information/custom` controller, or the DB Theme Editor path.

---

<!-- comet-task-authority: docs/openspec/changes/customize-toggle/tasks.md -->

### Task 1: Backend setting switch

**Files:**
- Modify: `admin/controller/setting/setting.php` (add display line near the Checkout block)
- Modify: `admin/view/template/setting/setting.twig` (add switch row after the checkout-guest row)
- Modify: `admin/language/en-gb/setting/setting.php` (add `entry_customize`, `help_customize`)

**Interfaces:**
- Consumes: existing `editSetting('config', POST)` persistence (no changes needed there).
- Produces: `config_customize_status` setting readable via `$this->config->get('config_customize_status')` (`'1'`/`'0'` strings once saved, `null` when never saved).

<!-- comet-task-ref:5a7e83a9-dfb0-4973-8f01-00bd367432a8 -->

**Step 1: Add the controller display line**

In `admin/controller/setting/setting.php`, in the Checkout block (next to line 242 `$data['config_checkout_guest'] = ...`), add:

```php
		$data['config_customize_status'] = $this->config->get('config_customize_status');

		if ($data['config_customize_status'] === null) {
			$data['config_customize_status'] = true;
		}
```

**Step 2: Add the switch row to the Option tab**

In `admin/view/template/setting/setting.twig`, immediately after the `config_checkout_guest` row block (lines 726鈥?34), insert:

```twig
                      <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ entry_customize }}</label>
                        <div class="col-sm-10">
                          <div class="form-check form-switch form-switch-lg">
                            <input type="hidden" name="config_customize_status" value="0"/> <input type="checkbox" name="config_customize_status" value="1" id="input-customize-status" class="form-check-input"{% if config_customize_status %} checked{% endif %}/>
                          </div>
                          <div class="form-text">{{ help_customize }}</div>
                        </div>
                      </div>
```

**Step 3: Add the language keys**

In `admin/language/en-gb/setting/setting.php`, next to the other `entry_checkout_*` keys, add:

```php
$_['entry_customize'] = 'Customize';
$_['help_customize']  = 'Show or hide the Customize entry points on the storefront.';
```

**Step 4: Verify and commit**

Run: `git diff --check`
Expected: clean, no whitespace errors.

Run: `git status --short`
Expected: exactly the 3 files above modified.

```bash
git add admin/controller/setting/setting.php admin/view/template/setting/setting.twig admin/language/en-gb/setting/setting.php
git commit -m "feat: customize visibility toggle in store settings"
```

### Task 2: Setting round-trip check

**Files:** (verification only, no code)

**Interfaces:**
- Consumes: Task 1's setting field.
- Produces: confirmed persistence behavior for Tasks 3鈥?.

<!-- comet-task-ref:bcf3320b-02b3-4a67-be97-147854a7c40 -->

**Step 1: Verify the save path needs no controller change**

Confirm in `admin/controller/setting/setting.php` `save()` (around line 678) that `$this->model_setting_setting->editSetting('config', $this->request->post);` persists the whole POST (so `config_customize_status` is stored with zero extra code). Record the line number in the commit message body or task notes.

**Step 2: Verify and commit (empty if no change needed)**

If no code change was needed, there is nothing to commit 鈥?mark the task done after the storefront tasks land (it is verified by Task 7's end-to-end check). Do NOT create an empty commit.

### Task 3: Gate the nav Custom Order item

**Files:**
- Modify: `catalog/controller/common/menu.php` (flag next to the `$data['custom']` line)
- Modify: `catalog/view/template/common/menu.twig` (guard line 41)

**Interfaces:**
- Consumes: `config_customize_status` setting from Task 1.
- Produces: `show_customize` boolean in menu view data.

<!-- comet-task-ref:3c750b05-c5e9-4d6d-a469-e727d265067 -->

**Step 1: Pass the flag in the controller**

In `catalog/controller/common/menu.php`, immediately after line 54 (`$data['custom'] = ...`), add:

```php
		$customize_status = $this->config->get('config_customize_status');

		$data['show_customize'] = ($customize_status === null || $customize_status === '' || (bool)$customize_status);
```

**Step 2: Guard the nav item in the template**

In `catalog/view/template/common/menu.twig`, change line 41 from:

```twig
        <li class="nav-item"><a href="{{ custom }}" class="nav-link va-nav-custom">{{ text_custom_order }}</a></li>
```

to:

```twig
        {% if show_customize %}<li class="nav-item"><a href="{{ custom }}" class="nav-link va-nav-custom">{{ text_custom_order }}</a></li>{% endif %}
```

**Step 3: Verify and commit**

Run: `git diff --check` 鈥?Expected: clean.

```bash
git add catalog/controller/common/menu.php catalog/view/template/common/menu.twig
git commit -m "feat: gate nav custom order item on customize toggle"
```

### Task 4: Gate the homepage CTA band

**Files:**
- Modify: `catalog/controller/common/home.php` (flag next to the `$data['custom']` line)
- Modify: `catalog/view/template/common/home.twig` (guard the `va-cta-band` section)

**Interfaces:**
- Consumes: `config_customize_status` setting from Task 1.
- Produces: `show_customize` boolean in home view data.

<!-- comet-task-ref:78752971-7363-4a5a-9efb-fb857ad266b -->

**Step 1: Pass the flag in the controller**

In `catalog/controller/common/home.php`, next to the `$data['custom'] = $this->url->link('information/custom', ...)` line, add:

```php
		$customize_status = $this->config->get('config_customize_status');

		$data['show_customize'] = ($customize_status === null || $customize_status === '' || (bool)$customize_status);
```

**Step 2: Guard the whole CTA band**

In `catalog/view/template/common/home.twig`, wrap lines 44鈥?0 (the entire `<section class="va-cta-band">...</section>` block) as:

```twig
  {% if show_customize %}
  <section class="va-cta-band">
    <div class="container">
      <h2>{{ text_cta_title }}</h2>
      <p>{{ text_cta_sub }}</p>
      <a href="{{ custom }}" class="btn va-btn-gold btn-lg">{{ text_start_custom }}</a>
    </div>
  </section>
  {% endif %}
```

**Step 3: Verify and commit**

Run: `git diff --check` 鈥?Expected: clean.

```bash
git add catalog/controller/common/home.php catalog/view/template/common/home.twig
git commit -m "feat: gate homepage CTA band on customize toggle"
```

### Task 5: Gate the product-card Customize link

**Files:**
- Modify: `catalog/controller/product/thumb.php` (flag before the view return)
- Modify: `catalog/view/template/product/thumb.twig` (guard line 38)

**Interfaces:**
- Consumes: `config_customize_status` setting from Task 1.
- Produces: `show_customize` boolean in thumb view data.

<!-- comet-task-ref:aa8800aa-fc7c-4c83-828a-208e54c4a90f -->

**Step 1: Pass the flag in the thumb controller**

In `catalog/controller/product/thumb.php`, in `index()`, before `return $this->load->view('product/thumb', $data);`, add:

```php
		$customize_status = $this->config->get('config_customize_status');

		$data['show_customize'] = ($customize_status === null || $customize_status === '' || (bool)$customize_status);
```

**Step 2: Guard the card link**

In `catalog/view/template/product/thumb.twig`, change line 38 from:

```twig
      <a href="index.php?route=information/custom&product_id={{ product_id }}" class="va-customize">{{ text_customize }} 鈫?/a>
```

to:

```twig
      {% if show_customize %}<a href="index.php?route=information/custom&product_id={{ product_id }}" class="va-customize">{{ text_customize }} 鈫?/a>{% endif %}
```

**Step 3: Verify and commit**

Run: `git diff --check` 鈥?Expected: clean.

```bash
git add catalog/controller/product/thumb.php catalog/view/template/product/thumb.twig
git commit -m "feat: gate product card customize link on toggle"
```

### Task 6: Gate the product-detail Customize block

**Files:**
- Modify: `catalog/controller/product/product.php` (flag before the view render)
- Modify: `catalog/view/template/product/product.twig` (guard the `va-custom-block` div)

**Interfaces:**
- Consumes: `config_customize_status` setting from Task 1.
- Produces: `show_customize` boolean in product view data.

<!-- comet-task-ref:8be9b01c-103f-472c-8b5a-e8ead0952340 -->

**Step 1: Pass the flag in the product controller**

In `catalog/controller/product/product.php`, in `index()`, before `$this->response->setOutput($this->load->view('product/product', $data));`, add:

```php
		$customize_status = $this->config->get('config_customize_status');

		$data['show_customize'] = ($customize_status === null || $customize_status === '' || (bool)$customize_status);
```

**Step 2: Guard the whole block**

In `catalog/view/template/product/product.twig`, wrap lines 80鈥?4 (the entire `<div class="va-custom-block">...</div>`) as:

```twig
          {% if show_customize %}
          <div class="va-custom-block">
            <p class="va-custom-title">{{ text_custom_title }}</p>
            <p class="va-custom-copy">{{ text_custom_copy }}</p>
            <a href="index.php?route=information/custom&product_id={{ product_id }}" class="btn btn-outline-dark">{{ button_customize }}</a>
          </div>
          {% endif %}
```

**Step 3: Verify and commit**

Run: `git diff --check` 鈥?Expected: clean.

```bash
git add catalog/controller/product/product.php catalog/view/template/product/product.twig
git commit -m "feat: gate product detail customize block on toggle"
```

### Task 7: End-to-end verification

**Files:** (verification only, no code)

**Interfaces:**
- Consumes: all tasks above.
- Produces: acceptance evidence for Verify phase.

<!-- comet-task-ref:6409785d-09be-4062-9490-e81ff7fbcab0 -->

**Step 1: Static wiring check**

Run and expect all hits:

```powershell
Select-String -Path "D:\projects\vidonart\catalog\controller\common\menu.php","D:\projects\vidonart\catalog\controller\common\home.php","D:\projects\vidonart\catalog\controller\product\thumb.php","D:\projects\vidonart\catalog\controller\product\product.php" -Pattern "show_customize"
Select-String -Path "D:\projects\vidonart\catalog\view\template\common\menu.twig","D:\projects\vidonart\catalog\view\template\common\home.twig","D:\projects\vidonart\catalog\view\template\product\thumb.twig","D:\projects\vidonart\catalog\view\template\product\product.twig" -Pattern "if show_customize"
```

Expected: 4 controller hits + 4 template hits.

**Step 2: Browser check (requires running store)**

Toggle off 鈫?homepage, category page, product page show no Customize entry points; direct `index.php?route=information/custom` still serves the form and accepts submissions. Toggle on 鈫?all four entry points render as before. Record results; do not commit.

## Self-Review

**1. Spec coverage:** toggle persistence + default-on 鈫?Tasks 1鈥?; hide all four entry points 鈫?Tasks 3鈥?; direct URL keeps working 鈫?untouched controller + Task 7 scenario. No gaps.

**2. Placeholder scan:** no TBD/TODO/"appropriate handling" language; every step has exact file paths, code, and commands.

**3. Type consistency:** the flag expression `($customize_status === null || $customize_status === '' || (bool)$customize_status)` is identical in Tasks 3鈥?; template variable is `show_customize` everywhere; setting key is `config_customize_status` everywhere.
