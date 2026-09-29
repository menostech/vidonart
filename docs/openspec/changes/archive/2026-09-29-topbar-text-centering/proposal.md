# Proposal

## Problem

The Topbar Text renders top-aligned (or misaligned) inside the `#top` bar instead of vertically centered with the row, because the plain-text `li` lacks the `line-height` the neighboring selector links get from `#top .list-inline-item` rules.

## Root cause

`#top .list-inline-item > a, ... span` sets `line-height: 36px`, but the new `{% if topbar_text %}<li>` contains bare text with no `a`/`span` wrapper, so it falls back to body line-height and sits off-center.

## Repair goal

Give the topbar text the same 36px line rhythm so it centers vertically with the language/currency/account items, without changing any other topbar behavior.
