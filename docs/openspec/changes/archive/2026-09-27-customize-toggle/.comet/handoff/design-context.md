# Comet Design Handoff

- Change: customize-toggle
- Phase: design
- Mode: compact
- Context hash: 31406296a127e4dd5fb2e633f5c94467801f4e2f9a4423bc120647f0acdffa93

Generated-by: comet-handoff.sh
Task hash policy: task-content-v1. Read tasks.md for live completion; excerpts are design-time context.

OpenSpec remains the canonical capability spec. This handoff is a deterministic, source-traceable context pack, not an agent-authored summary.

## docs/openspec/changes/customize-toggle/proposal.md

- Source: docs/openspec/changes/customize-toggle/proposal.md
- Lines: 1-27
- SHA256: ce7140d0764c47d9f018515fdb085fac02459e5045b9bf1bf20ab1dbbdcd7ed3

```md
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

```

## docs/openspec/changes/customize-toggle/design.md

- Source: docs/openspec/changes/customize-toggle/design.md
- Lines: 1-39
- SHA256: fdb1caf965852e8d93860b9289f13195284f7893f209ef01c59fcfb562a60201

```md
---
comet_change: customize-toggle
role: technical-design
canonical_spec: openspec
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

```

## docs/openspec/changes/customize-toggle/tasks.md

- Source: docs/openspec/changes/customize-toggle/tasks.md
- Lines: 1-17
- SHA256: dd528956dfe8d193fb62d0163fe105a6fb8ff3586accce64aa803992b50ea733

```md
# Tasks

## 1. Backend setting

- [ ] 1.1 Add `config_customize_status` display line in `admin/controller/setting/setting.php` and a Yes/No switch on the Option tab of `admin/view/template/setting/setting.twig` plus language key; verify the switch renders and persists after save
- [ ] 1.2 Confirm the saved value round-trips via `oc_setting` (save, reload settings page, value retained)

## 2. Storefront gating

- [ ] 2.1 Gate the nav Custom Order item in `menu.php`/`menu.twig` on the flag; verify it disappears when disabled and returns when enabled
- [ ] 2.2 Gate the homepage CTA band button in `home.php`/`home.twig`; verify visibility follows the flag
- [ ] 2.3 Gate the product-card "Customize" link in `thumb.php`/`thumb.twig`; verify on category/search pages
- [ ] 2.4 Gate the product-detail "Customize This Piece" block in `product.twig`; verify the whole block hides when disabled

## 3. Verification

- [ ] 3.1 End-to-end check: toggle off → all four entry points hidden, direct `information/custom` URL still serves the form; toggle on → everything renders as before

```

## docs/openspec/changes/customize-toggle/.openspec.yaml

- Source: docs/openspec/changes/customize-toggle/.openspec.yaml
- Lines: 1-2
- SHA256: 65ff9df577b54daf8b21917c880c564135cf80d9e6a2de690feb0f4a842393f1

```md
schema: spec-driven
created: 2026-09-27

```

## docs/openspec/changes/customize-toggle/specs/customize-visibility/spec.md

- Source: docs/openspec/changes/customize-toggle/specs/customize-visibility/spec.md
- Lines: 1-36
- SHA256: 69f54341d11e73d7356f82d30b7574468a3ecf0b63838dd792cbd18751c2e529

```md
# Spec Delta

## Purpose

Lets store operators turn the Customize (custom-order consultation) feature on or off from the backend, controlling whether shoppers see any Customize entry points on the storefront.

## ADDED Requirements

### Requirement: Customize visibility toggle in store settings
The system SHALL provide a Yes/No "Customize" setting on the store's Settings → Option tab, defaulting to Yes, persisted per store.

#### Scenario: Operator disables Customize
- **WHEN** an operator sets Customize to No and saves the store settings
- **THEN** the saved value persists and is used for all subsequent storefront renders

#### Scenario: Default is enabled
- **WHEN** the setting has never been saved
- **THEN** the storefront behaves as if Customize is enabled

### Requirement: Entry points hidden when Customize is disabled
The system SHALL hide every Customize entry point on the storefront when the toggle is off: the navigation Custom Order item, the homepage CTA band button, the product-card "Customize" link, and the product-detail "Customize This Piece" block.

#### Scenario: Shopper browses with Customize off
- **WHEN** a shopper visits the homepage, a category page, or a product page while Customize is disabled
- **THEN** none of the Customize entry points are rendered on those pages

#### Scenario: Shopper browses with Customize on
- **WHEN** a shopper visits the same pages while Customize is enabled
- **THEN** all Customize entry points render as before

### Requirement: Direct custom-order URL keeps working when disabled
The system SHALL continue to serve the custom-order form at its direct URL even when the toggle is off.

#### Scenario: Direct access while disabled
- **WHEN** a shopper opens the custom-order page URL directly while Customize is disabled
- **THEN** the form renders and submissions are accepted normally

```
