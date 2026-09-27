---
comet_change: customize-toggle
role: technical-design
canonical_spec: openspec
archived-with: 2026-09-27-customize-toggle
status: final
---

# Design: customize-toggle

## Context

See proposal.md (Why). Current Customize entry points and their wiring:

- Nav Custom Order item: `catalog/controller/common/menu.php` sets `$data['custom']`; `menu.twig` renders it.
- Homepage CTA band button: `catalog/controller/common/home.php` sets `$data['custom']`; `home.twig` renders the band.
- Product-card link: `catalog/view/template/product/thumb.twig` hardcodes `index.php?route=information/custom&product_id=...`.
- Product-detail block: `catalog/view/template/product/product.twig` renders the gold "Customize This Piece" block unconditionally.
- Admin store settings save via `editSetting('config', POST)` (`admin/controller/setting/setting.php:678`), which persists every posted key — a new `config_customize_status` field needs only a form input plus a display line in the controller.

## Goals / Non-Goals

- Goals: one backend toggle; all four entry points respect it; zero behavior change when enabled (default).
- Non-Goals: blocking direct URL access to `information/custom`; per-product/per-category toggles; touching cart/checkout logic; database migrations (uses `oc_setting` key-value store).

## Decisions

1. **Setting key `config_customize_status` on the Option tab** (over a dedicated page): matches how OpenCart exposes feature switches (e.g. voucher/coupon toggles) and needs no new routes, permissions, or tables. Alternative (dedicated Design-menu page) rejected as overkill for one boolean.
2. **Controller-supplied flags, not Twig config reads** (over reading config in templates): each catalog controller passes an explicit boolean (`customize_status` / `show_customize`), keeping templates dumb and matching the existing `$data['custom']` pattern. Twig `{% if %}` guards wrap each entry point.
3. **Product-detail block hidden wholesale** (over hiding just the button): the whole gold dashed block including its copy disappears, so no orphan heading remains.
4. **Homepage CTA band hidden wholesale** (confirmed in design review): the band exists solely for the CTA, so an empty band must not remain.
5. **Thumb flag from the thumb controller** (confirmed in design review): `thumb.twig` hardcodes its URL, so `catalog/controller/product/thumb.php` supplies the boolean flag alongside the existing data.

## Risks / Trade-offs

- [Risk] Cached pages rendered while enabled still show entry points until cache refresh → Mitigation: document that a cache clear may be needed; the flag is read live on every render.
- [Risk] Theme overrides via DB Theme Editor could bypass Twig guards → Mitigation: out of scope; DB overrides are operator-managed.

## Migration Plan

1. Deploy code changes. 2. Toggle defaults to enabled — no visible change. 3. Operator flips the switch when desired. Rollback: revert the commits; the unused `config_customize_status` row in `oc_setting` is harmless.
