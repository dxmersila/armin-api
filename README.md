# Armin API

Armin API is the Laravel backend of the Armin Template ecosystem.

It provides the API, business rules, authentication, authorization, commerce, payments, subscriptions, access control, and data management used by the independent Armin Admin and Armin Web applications.

The API is designed as a reusable, domain-neutral foundation for SaaS and digital-product applications.

## Role in the Armin Ecosystem

Armin consists of three independent applications:

* **Armin API** — Laravel backend and source of truth for business rules and data.
* **Armin Admin** — React-based administrative interface.
* **Armin Web** — Next.js public-facing web application.

```text
                         Armin
                           │
          ┌────────────────┼────────────────┐
          │                │                │
          ▼                ▼                ▼
        API              Admin             Web
      Laravel          React/Vite        Next.js
          │                │                │
          └────────────────┼────────────────┘
                           │
                     REST API Contract
```

The API is the authoritative backend. Admin and Web consume the API and do not own authoritative business rules.

## Tech Stack

* Laravel 13
* PHP 8.4
* MySQL
* Laravel Sanctum
* REST API
* Docker
* Nginx
* PHP-FPM

The API is versioned under:

```text
/api/v1
```

## Core Responsibilities

The API provides the backend foundation for:

* Authentication and user management
* Role-based administration
* Item and category management
* Tags and media
* Free and paid access
* Orders and order items
* Payments and payment gateways
* ZarinPal integration
* Subscription plans and subscriptions
* Access control and protected content
* API resources and stable HTTP contracts

Business-critical decisions remain on the backend, including:

* Authorization
* Access control
* Pricing
* Order state
* Payment state
* Subscription state
* User entitlements
* Protected content access

Frontend applications consume these rules through the API.

## Architecture

Armin API follows a deliberately simple architecture:

* API First
* Backend / Frontend Separation
* Thin Controllers
* Explicit Business Rules
* Meaningful Services
* Form Requests for meaningful validation
* Reusable Components
* Testable Code
* Docker First
* Stable API Contracts
* Convention Over Configuration
* Avoid Premature Abstraction

The backend intentionally avoids unnecessary abstraction layers unless real product requirements justify them.

## Development

The API is developed and tested inside the Docker environment.

Typical development commands include:

```bash
docker compose up -d
```

Run the test suite with:

```bash
docker compose exec app php artisan test
```

The exact development environment and operational details are maintained in the project documentation.

## Documentation

The complete Armin Template documentation is maintained in:

```text
Armin Documentation/
```

Start with:

* [`Armin Documentation/README.md`](Armin%20Documentation/README.md) — documentation overview
* [`Armin Documentation/ARMIN.md`](Armin%20Documentation/ARMIN.md) — ecosystem definition and Template → Product lifecycle
* [`Armin Documentation/ARCHITECTURE.md`](Armin%20Documentation/ARCHITECTURE.md) — technical architecture and decisions
* [`Armin Documentation/FEATURES.md`](Armin%20Documentation/FEATURES.md) — implemented capabilities and boundaries
* [`Armin Documentation/API Contract.md`](Armin%20Documentation/API%20Contract.md) — HTTP API contract
* [`Armin Documentation/SESSION.md`](Armin%20Documentation/SESSION.md) — current project checkpoint

## Template to Product

Armin API is part of the reusable Armin Template.

The Template is intended to be cloned into independent product instances:

```text
Armin Template
      ↓
Product Instance
      ↓
Product-specific configuration
      ↓
Production
```

Product-specific branding, content, catalog data, domains, and production credentials do not belong in the generic Template.

The first product instance built from Armin is **Prompt Box**.

## Payment Configuration

Armin includes the payment gateway integration required by supported payment workflows, including ZarinPal.

The Template does **not** contain real production payment credentials.

Production credentials belong to each individual product instance and are configured only in the appropriate production environment.

```text
Armin Template
    └── ZarinPal integration

Prompt Box
    └── Production ZarinPal credentials
```

## Status

**Complete / Stable**

The current Armin API represents the stable backend baseline of the Armin Template.

Future changes should be introduced when a real, reusable requirement benefits the Template or multiple product instances.

## Repository Structure

```text
armin-api/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── routes/
├── tests/
├── Armin Documentation/
├── README.md
└── ...
```

The API repository is the source of truth for the backend implementation and the central documentation for the Armin Template.
