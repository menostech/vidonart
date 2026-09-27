# Brainstorm Summary

- Change: customize-toggle
- Date: 2026-09-27

## Confirmed Technical Approach

`oc_setting.config_customize_status` toggle (default on) on the store Settings Option tab; each catalog controller passes an explicit boolean flag and Twig `{% if %}` guards wrap all four entry points (nav item, homepage CTA band hidden wholesale, product-card link, product-detail block hidden wholesale). Thumb controller supplies the flag since its template URL is hardcoded. No DB migration; `editSetting` persists the key automatically.

## Key Tradeoffs and Risks

- Cached pages may still show entry points until cache refresh (documented).
- DB Theme Editor overrides could bypass Twig guards (out of scope, operator-managed).

## Test Strategy

Flip the switch both ways; walk homepage/category/detail/nav entry points; verify direct `information/custom` URL always serves the form.

## Spec Patch

None.
