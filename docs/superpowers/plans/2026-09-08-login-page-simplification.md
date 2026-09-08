# Login Page Simplification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplify the storefront login page to a single centered card: remove the New Customer block and the right column, replace the form heading with "Log in to your account", drop the field labels, use "Email" as the email placeholder, add a Forgotten Password / Create an Account row under the password field, and center a "Sign in" button.

**Architecture:** Two-file change: `catalog/view/template/account/login.twig` (template restructure) + `catalog/language/en-gb/account/login.php` (title, new keys, button override). No controller changes — the login controller already passes `heading_title`, `register`, `forgotten`, `email`, `password`, and the stock `button_login` key gets overridden by the route language file (OC4 loads `en-gb/default.php` first, then the route file on top).

**Tech Stack:** Twig (OpenCart 4.1.0.4 storefront template), OC4 PHP language arrays.

---

### Task 1: Restructure the login template

**Files:**
- Modify: `catalog/view/template/account/login.twig`

- [ ] **Step 1: Replace the two-card row with a single centered form card**

Read the file first. Replace the inner two-card block (current lines 18–52, from `<div class="row">` after `{{ content_top }}` through the closing `</div>` before `{{ content_bottom }}`) with:

```twig
      <div class="row">
        <div class="col-md-6 mx-auto mb-3">
          <div class="border rounded p-3 d-flex flex-column h-100">
            <form id="form-login" action="{{ login }}" method="post" data-oc-toggle="ajax">
              <h2>{{ heading_title }}</h2>
              <div class="mb-3">
                <input type="email" name="email" value="{{ email }}" placeholder="{{ placeholder_email }}" id="input-email" class="form-control"/>
              </div>
              <div class="mb-3">
                <input type="password" name="password" value="{{ password }}" placeholder="{{ entry_password }}" id="input-password" class="form-control"/>
              </div>
              <div class="row mb-3">
                <div class="col-6">
                  <a href="{{ forgotten }}">{{ text_forgotten }}</a>
                </div>
                <div class="col-6 text-end">
                  <a href="{{ register }}">{{ text_create_account }}</a>
                </div>
              </div>
              {% if redirect %}
                <input type="hidden" name="redirect" value="{{ redirect }}"/>
              {% endif %}
              <div class="text-center">
                <button type="submit" class="btn btn-primary">{{ button_login }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
```

- [ ] **Step 2: Remove the right column**

In the same file, delete this line:

```twig
    {{ column_right }}
```

- [ ] **Step 3: Verify the template**

Run:

```powershell
git diff --check
Select-String -Path "catalog/view/template/account/login.twig" -Pattern "text_new_customer|text_register_account|text_i_am_returning_customer|column_right|entry_email"
```

Expected: no hits (all five patterns gone). Also confirm the remaining variables resolve: `heading_title`, `placeholder_email`, `text_create_account`, `text_forgotten`, `entry_password`, `register`, `forgotten`, `login`, `email`, `password`, `redirect`, `button_login`, `column_left`, `content_top`, `content_bottom` — all exist in the language file (after Task 2) or are passed by `catalog/controller/account/login.php` (verified: it passes `column_left`, `content_top`, `content_bottom`, `register`, `forgotten`, `login`, `email`, `password`, `redirect`).

- [ ] **Step 4: Commit (after Task 2 — see Task 2 Step 4 for the combined commit)**

---

### Task 2: Update the login language file

**Files:**
- Modify: `catalog/language/en-gb/account/login.php`

- [ ] **Step 1: Change the heading title**

In `catalog/language/en-gb/account/login.php`, change line 3:

```php
$_['heading_title']                = 'Account Login';
```

to:

```php
$_['heading_title']                = 'Log in to your account';
```

- [ ] **Step 2: Add the new keys**

Append to the end of the file:

```php
// Simplified login
$_['placeholder_email']     = 'Email';
$_['text_create_account']   = 'Create an Account';
$_['button_login']          = 'Sign in';
```

- [ ] **Step 3: Verify the language file**

Run:

```powershell
git diff --check
Select-String -Path "catalog/language/en-gb/account/login.php" -Pattern "heading_title|placeholder_email|text_create_account|button_login"
```

Expected: `heading_title = 'Log in to your account'` and the three new keys present. The unused keys (`text_new_customer`, `text_register_account`, `text_i_am_returning_customer`) must remain untouched.

- [ ] **Step 4: Commit both files**

```powershell
git add catalog/view/template/account/login.twig catalog/language/en-gb/account/login.php
git commit -m "refactor: simplify login page — single centered card, sign in button"
```

---

## Self-Review Notes

- **Spec coverage:** New Customer block removal → Task 1 Step 1; right column removal → Task 1 Step 2; heading "Log in to your account" → Task 1 Step 1 (`{{ heading_title }}`) + Task 2 Step 1; remove "I am a returning customer" → Task 1 Step 1 (text not included); remove Email/Password labels → Task 1 Step 1 (labels omitted); email placeholder "Email" → Task 1 Step 1 + Task 2 Step 2; password placeholder "Password" kept → Task 1 Step 1 (`{{ entry_password }}`); Forgotten Password (left) / Create an Account (right) under password → Task 1 Step 1; centered "Sign in" button → Task 1 Step 1 (`text-center` + `button_login` override in Task 2 Step 2). Controller untouched, `column_left`/breadcrumbs/alerts/redirect untouched.
- **Placeholder scan:** no TBD/TODO; all keys defined.
- **Type consistency:** `placeholder_email`, `text_create_account`, `button_login`, `heading_title` — single naming across both tasks.
- **Note:** `text_register` was only used in the removed New Customer card; the new register entry uses `text_create_account` (link target `{{ register }}` unchanged).