# topbar-custom-text Specification

## Purpose
Lets store operators display a custom backend-editable text message in the storefront topbar while keeping currency, language, and account controls grouped on the right side.

## Requirements

### Requirement: Backend-editable topbar text
The system SHALL provide a Topbar Text setting in Settings → General, stored as a plain-text store configuration value. When the value is empty, no text element is rendered in the topbar.

#### Scenario: Operator sets topbar text
- **WHEN** an operator saves a non-empty Topbar Text value in Settings
- **THEN** the storefront topbar left column displays exactly that text on subsequent page loads

#### Scenario: Topbar text left empty
- **WHEN** the Topbar Text setting is empty
- **THEN** the topbar left column renders no text element

### Requirement: Topbar layout with right-grouped selectors
The system SHALL render the topbar with the custom text (if any) in the left column, and the language selector, currency selector, and account links in that order in the right column. The custom text SHALL be vertically centered with the other topbar items.

#### Scenario: Full topbar render
- **WHEN** a shopper loads any storefront page with topbar text configured
- **THEN** the left shows the custom text and the right shows language, currency, then account links in that order

#### Scenario: Selectors keep working after rearrangement
- **WHEN** a shopper uses the language or currency selector in the new position
- **THEN** language and currency switching behave exactly as before

#### Scenario: Topbar text vertically centered
- **WHEN** a shopper loads any storefront page with topbar text configured
- **THEN** the custom text is vertically centered with the language, currency, and account items in the same row
