---
comet_change: footer-settings
role: technical-design
canonical_spec: openspec
---

# Design

## Approach

Add three single-language store settings in Settings → Store, alongside the existing Topbar Text row:

- `config_footer_logo` — image upload using the standard filemanager widget (same as Store Logo `config_logo`)
- `config_footer_tagline` — plain text input
- `config_footer_about` — plain textarea

Wire admin passthrough (`admin/controller/setting/setting.php`, `admin/view/template/setting/setting.twig`, `admin/language/en-gb/setting/setting.php`) and catalog passthrough (`catalog/controller/common/footer.php`) into `catalog/view/template/common/footer.twig`, replacing the hardcoded brand block.

Empty settings fall back to current defaults in the footer controller: store name (`config_name`) when no footer logo is set, the existing tagline literal, and `text_about`. This keeps the live footer unchanged on deploy while making all values editable. One scoped SCSS rule caps the footer logo image height so it matches the previous text-logo rhythm.

The setting save needs no special handling — OpenCart persists any posted `config_*` field automatically (same mechanism the archived Topbar Text setting relies on).
