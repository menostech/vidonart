# Nav Top Right Restructure — Design Spec

- **Date**: 2026-09-08
- **Status**: Approved by user
- **Repo**: menostech/vidonart — OpenCart 4.1.0.4

## 1. Goal

Simplify the storefront top bar (`#top`, right side) in `catalog/view/template/common/header.twig`: remove the telephone link and the wishlist/cart icons, and surface account actions as first-level links instead of a dropdown.

## 2. Changes (single file: `catalog/view/template/common/header.twig`)

Current right-side block (lines 51–74) contains: telephone link, user dropdown (Register/Login when guest, Account/Order/Transaction/Download/Logout when logged in), wishlist heart icon, cart icon.

After the change, the right-side list contains:

- **Guest** (`{% if not logged %}`): two `list-inline-item` text links — Register (`{{ register }}`, `text_register`) and Login (`{{ login }}`, `text_login`).
- **Logged in**: two `list-inline-item` text links — My Account (`{{ account }}`, `text_account`) and Logout (`{{ logout }}`, `text_logout`).

Removed: the telephone `<a>` (line 53), the whole `.dropdown` block (lines 54–70), the wishlist link (line 71), the shopping-cart link (line 72).

## 3. Out of Scope / Unchanged

- Left side of `#top` (currency, language switchers) — unchanged.
- The `{{ cart }}` widget in `#header` (add-to-cart/cart contents) — unchanged; only the redundant top-bar cart icon is removed.
- Order / Transaction / Download links disappear from the top bar; they remain reachable inside the account page (stock account menu) — no controller or language changes.
- No new language keys: `text_register`, `text_login`, `text_account`, `text_logout` already exist in `catalog/language/en-gb/common/header.php`.
- No CSS changes: existing `#top .list-inline-item` rules apply.

## 4. Verification

- `git diff --check` clean; commit message `refactor: simplify top bar — drop telephone/wishlist/cart, first-level account links`.
- Grep `header.twig` to confirm no remaining references to `telephone`, `wishlist`, `shopping_cart` in the right-side block (wishlist/cart vars may still be used elsewhere in the template — check before removing).
- Visual check after store install: guest sees Register | Login; logged-in sees My Account | Logout; no dropdown remains.