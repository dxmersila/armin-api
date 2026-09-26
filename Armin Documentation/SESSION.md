# Armin — Current Session

## Current Status

| Component      | Status            |
| -------------- | ----------------- |
| Armin Template | Complete / Stable |
| Laravel API    | Complete / Stable |
| React Admin    | Complete / Stable |
| Next.js Web    | Complete / Stable |
| GitHub         | Synchronized      |
| Known Blockers | None              |

## Current Lifecycle

The Armin Template development phase is complete.

The project has moved from **template development** to **product instance productionization**.

```text
Armin Template
      ↓
Clone / Create Product Instance
      ↓
Production Configuration
      ↓
Product-Specific Development
      ↓
Launch
```

## Immediate Next Step

Obtain and configure the real **ZarinPal production credentials** for the first product instance.

Production credentials must be configured at the product-instance level and must never be committed to the reusable template repository.

## First Product Instance

The first real product built on Armin is **Prompt Box**.

Prompt Box-specific work belongs to the product instance, including:

* Branding
* Product copy
* Content
* Catalog data
* Domain
* Production configuration
* Production payment credentials

These are not part of the Armin Template itself.

## Working Rule

Armin remains the reusable foundation.

Future changes to the Template should be driven by a real, recurring requirement that benefits multiple product instances—not by speculative features or premature abstraction.
