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
