# Spec Delta

## ADDED Requirements

### Requirement: Admin-editable footer brand
The system SHALL provide Footer Logo, Footer Tagline, and Footer About settings in Settings → Store, persisted as plain store configuration values. When a value is empty, the footer SHALL fall back to the store name, the default tagline, and the default about text.

#### Scenario: Operator sets footer logo and info
- **WHEN** an operator saves a non-empty Footer Logo, Footer Tagline, or Footer About value in Settings
- **THEN** the storefront footer renders the configured footer logo (capped height) and the configured text on subsequent page loads

#### Scenario: Footer fields left empty
- **WHEN** the Footer Logo, Footer Tagline, or Footer About settings are empty
- **THEN** the footer renders the store name text, the default tagline, and the default about text

#### Scenario: Footer brand stays intact
- **WHEN** a shopper loads any storefront page
- **THEN** the footer brand block renders without layout breakage
