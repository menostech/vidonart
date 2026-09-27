# Tasks

## 1. Backend setting

- [x] 1.1 Add `config_customize_status` display line in `admin/controller/setting/setting.php` and a Yes/No switch on the Option tab of `admin/view/template/setting/setting.twig` plus language key; verify the switch renders and persists after save <!-- comet-task:5a7e83a9-dfb0-4973-8f01-00bd367432a8 -->
- [x] 1.2 Confirm the saved value round-trips via `oc_setting` (save, reload settings page, value retained) <!-- comet-task:bcf3320b-02b3-4a67-be97-147854a7c40b -->

## 2. Storefront gating

- [x] 2.1 Gate the nav Custom Order item in `menu.php`/`menu.twig` on the flag; verify it disappears when disabled and returns when enabled <!-- comet-task:3c750b05-c5e9-4d6d-a469-e727d265067e -->
- [x] 2.2 Gate the homepage CTA band button in `home.php`/`home.twig`; verify visibility follows the flag <!-- comet-task:78752971-7363-4a5a-9efb-fb857ad266b6 -->
- [x] 2.3 Gate the product-card "Customize" link in `thumb.php`/`thumb.twig`; verify on category/search pages <!-- comet-task:aa8800aa-fc7c-4c83-828a-208e54c4a90f -->
- [x] 2.4 Gate the product-detail "Customize This Piece" block in `product.twig`; verify the whole block hides when disabled <!-- comet-task:8be9b01c-103f-472c-8b5a-e8ead0952340 -->

## 3. Verification

- [x] 3.1 End-to-end check: toggle off → all four entry points hidden, direct `information/custom` URL still serves the form; toggle on → everything renders as before <!-- comet-task:6409785d-09be-4062-9490-e81ff7fbcab0 -->
