---
change: topbar-custom-text
design-doc: docs/openspec/changes/topbar-custom-text/design.md
base-ref: 291300538a78d6002d340d9bf761f420da78b529
archived-with: 2026-09-28-topbar-custom-text
---

# Topbar Custom Text Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Progress is tracked in the change's tasks.md via Comet task IDs.

**Goal:** Add a backend-editable Topbar Text setting and rearrange the storefront topbar (text left alone; language, currency, account links right in that order).

**Architecture:** One new store setting (`config_topbar_text`, persisted via the existing `editSetting('config', POST)` flow, no migration); `header.php` passes it through like `telephone`; `header.twig` `#top` block rearranged with an empty-text guard. Selectors keep their sub-controllers untouched.

**Tech Stack:** PHP 8 / OpenCart 4.1.0.4 (admin settings + catalog MVC), Twig.

**Spec:** `docs/openspec/changes/topbar-custom-text/specs/topbar-custom-text/spec.md` — the plan argues from the spec; executors read both. Design decisions live in `docs/openspec/changes/topbar-custom-text/design.md`.

## Global Constraints

- User-facing copy stays English (single-language storefront).
- No database migration — the setting lives in the `oc_setting` key-value store.
- Empty setting MUST render no text element (no empty placeholder).
- Do not change language/currency/account behavior, only their placement.

---

<!-- comet-task-authority: docs/openspec/changes/topbar-custom-text/tasks.md -->

### Task 1: Backend setting field

**Files:**
- Modify: `admin/controller/setting/setting.php` (display line near the `config_telephone` line)
- Modify: `admin/view/template/setting/setting.twig` (text row after the telephone row in `#tab-general`)
- Modify: `admin/language/en-gb/setting/setting.php` (add `entry_topbar_text`)

**Interfaces:**
- Consumes: existing `editSetting('config', POST)` persistence (no changes needed there).
- Produces: `config_topbar_text` setting readable via `$this->config->get('config_topbar_text')` (string once saved, `null`/`''` when never saved).

<!-- comet-task-ref:5a3699d0-4cc7-40df-b2da-d79a49fc7b8f -->

**Step 1: Add the controller display line**

In `admin/controller/setting/setting.php`, immediately after line 76 (`$data['config_telephone'] = $this->config->get('config_telephone');`), add:

```php
		$data['config_topbar_text'] = $this->config->get('config_topbar_text');
```

**Step 2: Add the form row**

In `admin/view/template/setting/setting.twig`, immediately after the telephone row block (lines 152–158), insert:

```twig
              <div class="row mb-3">
                <label for="input-topbar-text" class="col-sm-2 col-form-label">{{ entry_topbar_text }}</label>
                <div class="col-sm-10">
                  <input type="text" name="config_topbar_text" value="{{ config_topbar_text }}" placeholder="{{ entry_topbar_text }}" id="input-topbar-text" class="form-control"/>
                  <div id="error-topbar-text" class="invalid-feedback"></div>
                </div>
              </div>
```

**Step 3: Add the language key**

In `admin/language/en-gb/setting/setting.php`, next to line 67 (`entry_telephone`), add:

```php
$_['entry_topbar_text']                   = 'Topbar Text';
```

**Step 4: Verify and commit**

Run: `git diff --check`
Expected: clean, no whitespace errors.

```bash
git add admin/controller/setting/setting.php admin/view/template/setting/setting.twig admin/language/en-gb/setting/setting.php
git commit -m "feat: topbar text setting in store settings"
```

### Task 2: Setting round-trip check

**Files:** (verification only, no code)

**Interfaces:**
- Consumes: Task 1's setting field.
- Produces: confirmed persistence behavior for Task 3.

<!-- comet-task-ref:3b2c9292-da3f-4ad1-bf28-019ad75f37b6 -->

**Step 1: Verify the save path needs no controller change**

Confirm in `admin/controller/setting/setting.php` `save()` that the settings persist via `$this->model_setting_setting->editSetting('config', $this->request->post);` (whole-POST save, so `config_topbar_text` is stored with zero extra code). Record the line number in task notes.

**Step 2: No commit**

If no code change was needed, there is nothing to commit — mark done after the storefront tasks land (verified by the end-to-end check). Do NOT create an empty commit.

### Task 3: Header passthrough

**Files:**
- Modify: `catalog/controller/common/header.php` (flag next to the telephone line)

**Interfaces:**
- Consumes: `config_topbar_text` setting from Task 1.
- Produces: `topbar_text` string in header view data (empty string when unset).

<!-- comet-task-ref:7afad231-8b91-4fa9-bcb8-a28d0f9f0338 -->

**Step 1: Pass the text through**

In `catalog/controller/common/header.php`, immediately after line 97 (`$data['telephone'] = $this->config->get('config_telephone');`), add:

```php
		$data['topbar_text'] = $this->config->get('config_topbar_text');
```

**Step 2: Verify and commit**

Run: `git diff --check` — Expected: clean.

```bash
git add catalog/controller/common/header.php
git commit -m "feat: pass topbar text to header template"
```

### Task 4: Topbar rearrangement

**Files:**
- Modify: `catalog/view/template/common/header.twig` (rearrange the `#top` block)

**Interfaces:**
- Consumes: `topbar_text` string from Task 3; `language`/`currency` sub-controller outputs (unchanged).
- Produces: new topbar layout (text left alone; selectors right).

<!-- comet-task-ref:df86a7db-008e-40e8-ac10-608f321457cd -->

**Step 1: Rearrange the `#top` block**

In `catalog/view/template/common/header.twig`, replace the two-column row (lines 44–62) with:

```twig
      <div class="row">
        <div class="col-6">
          <ul class="list-inline">
            {% if topbar_text %}<li class="list-inline-item">{{ topbar_text }}</li>{% endif %}
          </ul>
        </div>
        <div class="col-6 text-end">
          <ul class="list-inline">
            <li class="list-inline-item">{{ language }}</li>
            <li class="list-inline-item me-3">{{ currency }}</li>
            {% if not logged %}
              <li class="list-inline-item"><a href="{{ register }}">{{ text_register }}</a></li>
              <li class="list-inline-item"><a href="{{ login }}">{{ text_login }}</a></li>
            {% else %}
              <li class="list-inline-item"><a href="{{ account }}">{{ text_account }}</a></li>
              <li class="list-inline-item"><a href="{{ logout }}">{{ text_logout }}</a></li>
            {% endif %}
          </ul>
        </div>
      </div>
```

Keep the account-links block byte-identical to the current lines 53–59; only the left column content and the first two right-column items change.

**Step 2: Verify and commit**

Run: `git diff --check` — Expected: clean.

```bash
git add catalog/view/template/common/header.twig
git commit -m "feat: rearrange topbar with custom text and right-grouped selectors"
```

**Step 3: End-to-end check (requires running store)**

With text set → left shows text, right shows language/currency/account in order; selectors still switch correctly. With text empty → no text element. Record results; do not commit.

## Self-Review

**1. Spec coverage:** backend-editable text + empty-hides → Tasks 1–2; right-grouped layout → Tasks 3–4; selector behavior unchanged → Task 4 end-to-end check. No gaps.

**2. Placeholder scan:** no TBD/TODO/"appropriate handling" language; every step has exact file paths, code, and commands.

**3. Type consistency:** setting key `config_topbar_text` everywhere; template variable `topbar_text` everywhere; language key `entry_topbar_text` everywhere.
