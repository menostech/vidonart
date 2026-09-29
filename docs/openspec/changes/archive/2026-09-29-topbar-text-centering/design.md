---
comet_change: topbar-text-centering
role: technical-design
canonical_spec: openspec
---

# Design

## Approach

Wrap the topbar text in a `<span>` inside its `li` so it inherits the existing `#top .list-inline-item .dropdown > span` 36px line-height rule — one-line template change, no new CSS, no new selectors. Alternative (dedicated CSS rule for the text `li`) rejected: reuses the exact rhythm the neighbors already use, so future line-height changes stay in sync automatically.
