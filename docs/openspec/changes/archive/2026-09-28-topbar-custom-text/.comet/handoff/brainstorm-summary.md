# Brainstorm Summary

- Change: topbar-custom-text
- Date: 2026-09-28

## Confirmed Technical Approach

Follow existing architecture: `config_topbar_text` store setting via `editSetting` (no new interfaces); `header.php` passthrough like `telephone`; `#top` left holds only conditional text, right holds language → currency → account; no CSS changes.

## Key Tradeoffs and Risks

- Long text may wrap on narrow screens (accepted, operator controls copy).
- Raw HTML output accepted for operator-only input.

## Test Strategy

Save non-empty text → appears left; empty → no element; language/currency switching unchanged; narrow-screen glance.

## Spec Patch

None.
