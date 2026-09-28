# Tasks

## 1. Backend setting

- [x] 1.1 Add `config_topbar_text` to the Settings → General form (controller passthrough, template text row, language strings) and verify it persists after save <!-- comet-task:5a3699d0-4cc7-40df-b2da-d79a49fc7b8f -->
- [x] 1.2 Verify empty value saves cleanly and the setting round-trips through `oc_setting` <!-- comet-task:3b2c9292-da3f-4ad1-bf28-019ad75f37b6 -->

## 2. Storefront topbar

- [x] 2.1 Pass `topbar_text` through in `catalog/controller/common/header.php` and verify it reaches the template <!-- comet-task:7afad231-8b91-4fa9-bcb8-a28d0f9f0338 -->
- [x] 2.2 Rearrange the `#top` block in `header.twig` (text left alone; language, currency, account links right in that order) with empty-text guard; verify layout and that selectors still work <!-- comet-task:df86a7db-008e-40e8-ac10-608f321457cd -->
