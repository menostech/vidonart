# Proposal

## Problem

The footer link-section headings (Information / Customer Service / Extras / My Account) render too small and not bold enough against the links below them: `footer h5` uses a serif face at 600 weight at the same 0.8125rem size as the links, which reads visually smaller and weaker than the links.

## Root cause

`catalog/view/stylesheet/stylesheet.scss` styles `footer h5` with `font-weight: 600` and the serif family; the section links (`footer a`) render at the same 0.8125rem size but with default 400 weight. The titles therefore do not stand out and are perceived as too small relative to the links below them.

## Repair goal

Make the footer section titles render at the same font size as the links below them and bold, so the heading hierarchy reads clearly.

## Root cause check

Before repair: `footer h5 { font-size: 0.8125rem; font-weight: 600; }` vs `footer a { font-size: 0.8125rem; }` — same size, title weight not bold.
