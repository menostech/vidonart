---
comet_change: topbar-custom-text
role: technical-design
canonical_spec: openspec
archived-with: 2026-09-28-topbar-custom-text
status: final
---

# Design

## Context

The `#top` block in `catalog/view/template/common/header.twig` currently renders currency + language (left) and account links (right); `catalog/controller/common/header.php` supplies `currency`/`language` via sub-controllers. Store settings persist through the existing `editSetting` flow, so a new text key needs only a form row + language strings. No CSS changes are required — the `list-inline` styles already cover both columns.

## Goals / Non-Goals

**Goals:**
- Backend-editable topbar text with empty-means-hidden behavior.
- Right-grouped language → currency → account order; left holds only the text.

**Non-Goals:**
- Multi-language text variants; changing selector behavior; new styles or breakpoints.

## Decisions

1. **Store setting `config_topbar_text` (plain text)** over multi-language variants: matches single-language text settings already in use and keeps the admin form to one row; rejected multi-language as disproportionate for this store.
2. **Passthrough in `header.php`** (`$data['topbar_text']`) over reading config in Twig: keeps data flow explicit and testable, consistent with how `telephone` is passed today.
3. **Conditional render (`{% if topbar_text %}`)** over always rendering an empty element: avoids an empty list item shifting layout when unset.

## Risks / Trade-offs

- Long text can wrap on narrow screens — accepted; operators control the copy length. Mitigation: keep copy short (documented in help text).
- HTML in the text field would render raw — accepted for operator-only input; escaped output is unnecessary behind admin auth, consistent with similar text settings.
