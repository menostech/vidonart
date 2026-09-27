# Proposal

## Why

Store operators need a backend switch to turn the Customize (custom-order consultation) feature on or off without code changes, e.g. when artisan capacity is full or during promotions.

## What Changes

- Add a `config_customize_status` store setting (Yes/No, default Yes) editable on the System → Settings → store edit → Option tab.
- When disabled, hide all Customize entry points on the storefront: the nav Custom Order item, the homepage CTA band button, the product-card "Customize →" link, and the product-detail "Customize This Piece" block.
- Direct URL access to `information/custom` keeps working (explicitly out of scope to block it).

## Capabilities

### New Capabilities

- `customize-visibility`: admin-controllable visibility of the Customize entry points on the storefront.

### Modified Capabilities

- None.

## Impact

- Admin: `admin/controller/setting/setting.php`, `admin/view/template/setting/setting.twig`, `admin/language/en-gb/setting/setting.php`.
- Catalog: `catalog/controller/common/menu.php`, `catalog/controller/common/home.php`, `catalog/controller/product/thumb.php`, `catalog/view/template/common/menu.twig`, `catalog/view/template/common/home.twig`, `catalog/view/template/product/thumb.twig`, `catalog/view/template/product/product.twig`.
- No database migration (uses the `oc_setting` key-value store); no cart/checkout logic changes.
