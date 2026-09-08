# Login Page Simplification — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4

## 1. Goal

Simplify the storefront login page (`account/login`): remove the "New Customer" block and the right column, and reduce the login form to a focused single card with minimal text.

## 2. Changes

### 2.1 `catalog/view/template/account/login.twig`

- Remove the left "New Customer" card (current lines 18–28, the `col mb-3` div containing `text_new_customer` / `text_register` / `text_register_account` / register button).
- Remove `{{ column_right }}` (line 55). The login page no longer renders a right column.
- Center the form card: the remaining card column gets `col-md-6 mx-auto` (was `col`).
- Form heading: `{{ text_returning_customer }}` → `{{ heading_title }}` (now "Log in to your account").
- Remove the "I am a returning customer" paragraph (`text_i_am_returning_customer`).
- Remove the two field labels (`entry_email`, `entry_password` labels).
- Email input: `placeholder="{{ entry_email }}"` → `placeholder="{{ placeholder_email }}"` ("Email"). Keep `type="email"`, `value="{{ email }}"`, `id="input-email"`.
- Password input: keep `placeholder="{{ entry_password }}"` ("Password").
- Under the password input, replace the current inline Forgotten Password link with a two-column row:

```twig
<div class="row mb-3">
  <div class="col-6">
    <a href="{{ forgotten }}">{{ text_forgotten }}</a>
  </div>
  <div class="col-6 text-end">
    <a href="{{ register }}">{{ text_create_account }}</a>
  </div>
</div>
```

- Submit button: `text-end` wrapper → `text-center`; keep `{{ button_login }}` (now "Sign in").

### 2.2 `catalog/language/en-gb/account/login.php`

- `$_['heading_title']` → `'Log in to your account'` (used by both the document `<title>` via the controller and the form heading).
- Add `$_['placeholder_email'] = 'Email';`
- Add `$_['text_create_account'] = 'Create an Account';`
- Add `$_['button_login'] = 'Sign in';` (overrides the stock `button_login` from `en-gb/default.php`; OC4 loads the route language file after default, so the override wins — no controller change needed).
- Leave unused keys (`text_new_customer`, `text_register_account`, `text_i_am_returning_customer`) in place; they are no longer referenced by the template but may be referenced elsewhere.

### 2.3 Unchanged

- `catalog/controller/account/login.php` — untouched (still loads `column_left`/`column_right` variables; the template simply stops rendering `column_right`).
- Left column (`{{ column_left }}`), breadcrumbs, alerts (success/error_warning), redirect hidden field — untouched.
- Other account pages — untouched.

## 3. Verification

- `git diff --check` clean; commit message `refactor: simplify login page — single centered card, sign in button`.
- Grep `login.twig` for `text_new_customer`, `text_register_account`, `text_i_am_returning_customer`, `column_right` — must return no hits in the template.
- Visual check after store install: single centered card, title "Log in to your account", no labels, placeholders "Email"/"Password", Forgotten Password (left) + Create an Account (right) under the password field, centered "Sign in" button, no right column.