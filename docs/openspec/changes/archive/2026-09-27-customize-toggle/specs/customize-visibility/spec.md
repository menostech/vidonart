# Spec Delta

## Purpose

Lets store operators turn the Customize (custom-order consultation) feature on or off from the backend, controlling whether shoppers see any Customize entry points on the storefront.

## ADDED Requirements

### Requirement: Customize visibility toggle in store settings
The system SHALL provide a Yes/No "Customize" setting on the store's Settings → Option tab, defaulting to Yes, persisted per store.

#### Scenario: Operator disables Customize
- **WHEN** an operator sets Customize to No and saves the store settings
- **THEN** the saved value persists and is used for all subsequent storefront renders

#### Scenario: Default is enabled
- **WHEN** the setting has never been saved
- **THEN** the storefront behaves as if Customize is enabled

### Requirement: Entry points hidden when Customize is disabled
The system SHALL hide every Customize entry point on the storefront when the toggle is off: the navigation Custom Order item, the homepage CTA band button, the product-card "Customize" link, and the product-detail "Customize This Piece" block.

#### Scenario: Shopper browses with Customize off
- **WHEN** a shopper visits the homepage, a category page, or a product page while Customize is disabled
- **THEN** none of the Customize entry points are rendered on those pages

#### Scenario: Shopper browses with Customize on
- **WHEN** a shopper visits the same pages while Customize is enabled
- **THEN** all Customize entry points render as before

### Requirement: Direct custom-order URL keeps working when disabled
The system SHALL continue to serve the custom-order form at its direct URL even when the toggle is off.

#### Scenario: Direct access while disabled
- **WHEN** a shopper opens the custom-order page URL directly while Customize is disabled
- **THEN** the form renders and submissions are accepted normally
