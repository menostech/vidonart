---
comet_change: footer-section-title
role: technical-design
canonical_spec: openspec
---

# Design

## Approach

Change the `footer h5` rule in `catalog/view/stylesheet/stylesheet.scss`: set `font-weight: 700` (bold) while keeping `font-size: 0.8125rem` so it exactly matches the link text size below. Recompile `stylesheet.css`. Pure CSS change — no template, controller, language, or behavior changes.
