# Spec Delta

## MODIFIED Requirements

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
