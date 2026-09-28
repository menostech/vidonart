# Comet Design Handoff

- Change: topbar-custom-text
- Phase: design
- Mode: compact
- Context hash: ac0d9ad4cb13aaa9446e359b90f7d832280407121d0468d6d618434cb1c4a9d8

Generated-by: comet-handoff.sh
Task hash policy: task-content-v1. Read tasks.md for live completion; excerpts are design-time context.

OpenSpec remains the canonical capability spec. This handoff is a deterministic, source-traceable context pack, not an agent-authored summary.

## docs/openspec/changes/topbar-custom-text/proposal.md

- Source: docs/openspec/changes/topbar-custom-text/proposal.md
- Lines: 1-27
- SHA256: 822682a4c5c9c0c83bd62c608b8850789fd9a9f40f532c3b74f36fc588aa36e5

```md
# Proposal

## Why

The topbar currently shows only the currency and language selectors on the left, leaving no room for store messaging (e.g. promotions, announcements). Operators need a backend-editable text line in the topbar without touching templates.

## What Changes

- Add a `Topbar Text` setting (Settings → General) stored as `config_topbar_text`; empty value renders nothing.
- Rearrange the topbar: left column shows only the custom text; right column shows language selector, currency selector, then account links.
- No changes to currency/language/account functionality themselves — only their placement.

## Capabilities

### New Capabilities

- `topbar-custom-text`: backend-editable custom text line in the storefront topbar, plus the new left-text / right-selectors topbar layout.

### Modified Capabilities

- None.

## Impact

- Admin: setting controller + setting template + language file (one new text field).
- Catalog: header controller (pass text through) + header template (rearrange `#top` block).
- No database migration (uses `oc_setting` key-value store); no CSS changes needed.

```

## docs/openspec/changes/topbar-custom-text/design.md

- Source: docs/openspec/changes/topbar-custom-text/design.md
- Lines: 1-31
- SHA256: 7e74c5b98441058226c2a31ab3f9c5672136afff1e5d70571cbd9e7cefc4f45e

```md
---
comet_change: topbar-custom-text
role: technical-design
canonical_spec: openspec
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

```

## docs/openspec/changes/topbar-custom-text/tasks.md

- Source: docs/openspec/changes/topbar-custom-text/tasks.md
- Lines: 1-11
- SHA256: ccca7252756b987bdeb53abdda6af6573120e014efaabf11a739457b4da2ff8d

```md
# Tasks

## 1. Backend setting

- [ ] 1.1 Add `config_topbar_text` to the Settings → General form (controller passthrough, template text row, language strings) and verify it persists after save
- [ ] 1.2 Verify empty value saves cleanly and the setting round-trips through `oc_setting`

## 2. Storefront topbar

- [ ] 2.1 Pass `topbar_text` through in `catalog/controller/common/header.php` and verify it reaches the template
- [ ] 2.2 Rearrange the `#top` block in `header.twig` (text left alone; language, currency, account links right in that order) with empty-text guard; verify layout and that selectors still work

```

## docs/openspec/changes/topbar-custom-text/.openspec.yaml

- Source: docs/openspec/changes/topbar-custom-text/.openspec.yaml
- Lines: 1-2
- SHA256: d707823457f58b4f938a99bc049bddd9b937affec2d09c976c9a16a9dcd03f77

```md
schema: spec-driven
created: 2026-09-28

```

## docs/openspec/changes/topbar-custom-text/specs/topbar-custom-text/spec.md

- Source: docs/openspec/changes/topbar-custom-text/specs/topbar-custom-text/spec.md
- Lines: 1-29
- SHA256: 1ba329ec9c88c367107cfb239db104979c9406edb5ccc5082d1f64865b25072b

```md
# Spec Delta

## Purpose

Lets store operators display a custom backend-editable text message in the storefront topbar while keeping currency, language, and account controls grouped on the right side.

## ADDED Requirements

### Requirement: Backend-editable topbar text
The system SHALL provide a Topbar Text setting in Settings → General, stored as a plain-text store configuration value. When the value is empty, no text element is rendered in the topbar.

#### Scenario: Operator sets topbar text
- **WHEN** an operator saves a non-empty Topbar Text value in Settings
- **THEN** the storefront topbar left column displays exactly that text on subsequent page loads

#### Scenario: Topbar text left empty
- **WHEN** the Topbar Text setting is empty
- **THEN** the topbar left column renders no text element

### Requirement: Topbar layout with right-grouped selectors
The system SHALL render the topbar with the custom text (if any) in the left column, and the language selector, currency selector, and account links in that order in the right column.

#### Scenario: Full topbar render
- **WHEN** a shopper loads any storefront page with topbar text configured
- **THEN** the left shows the custom text and the right shows language, currency, then account links in that order

#### Scenario: Selectors keep working after rearrangement
- **WHEN** a shopper uses the language or currency selector in the new position
- **THEN** language and currency switching behave exactly as before

```
