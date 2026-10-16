# Proposal

## Problem

The storefront footer brand block hardcodes its company identity — `Vidon Art` as logo, `Oriental Heritage · Modern Craft` as tagline, and a language-file about text — so store operators cannot change the footer logo or footer info from the admin without editing templates.

## Root cause

`catalog/view/template/common/footer.twig` renders the brand block with fixed literals and `text_about`, and there is no `config_*` setting wired from the admin into `catalog/controller/common/footer.php`. The header already exposes an editable logo (`config_logo`); the footer has no equivalent.

## Repair goal

Make the footer company logo, tagline and about text admin-editable. Add three store settings — footer logo (image upload), footer tagline (text), footer about (text) — and render them in the footer with fallbacks to the current defaults when a field is empty, so deployment does not change the live footer appearance.

## Root cause check

Before repair: `grep` shows hardcoded `Vidon Art` / `Oriental Heritage · Modern Craft` in footer.twig and no `config_footer_*` handling in admin or catalog controllers.
