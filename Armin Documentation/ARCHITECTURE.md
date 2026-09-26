# Armin Architecture

## 1. Purpose

This document defines the technical architecture, boundaries, responsibilities, and intentional design decisions of the Armin Template.

Armin is a reusable, API-first foundation for SaaS and digital-product applications.

The architecture is designed to remain:

* Simple
* Explicit
* Modular
* Testable
* Reusable
* Maintainable

The goal is not to build a universal framework.

The goal is to provide a stable foundation that can be cloned and adapted into different product instances.

---

## 2. System Overview

Armin consists of three independent applications:

```text id="d8i9tq"
                         ┌─────────────────────┐
                         │      Armin API      │
                         │ Laravel / PHP / DB  │
                         └──────────┬──────────┘
                                    │
                              REST API /v1
                                    │
                     ┌──────────────┴──────────────┐
                     │                             │
                     ▼                             ▼
          ┌─────────────────────┐       ┌─────────────────────┐
          │    Armin Admin      │       │      Armin Web       │
          │ React + Vite        │       │ Next.js + React      │
          │ Admin Application    │       │ Public Application   │
          └─────────────────────┘       └─────────────────────┘
```

The API is the source of truth for backend state and business rules.

Admin and Web are consumers of the API.

Neither frontend application is a second backend.

---

## 3. Application Boundaries

### 3.1 Laravel API

The API owns:

* Authentication
* Authorization
* Users
* Domain entities
* Business rules
* Access control
* Pricing
* Orders
* Payments
* Subscriptions
* Persistence
* API Resources
* API contracts

The API must remain independent of the frontend technologies.

It must not depend on:

* React
* Next.js
* Vite
* Tailwind CSS

---

### 3.2 React + Vite Admin

Admin owns:

* Administrative navigation
* Tables
* Forms
* Filters
* Pagination
* Dashboard presentation
* Administrative workflows
* Media management UI
* User-facing administrative feedback
* Localization
* RTL/LTR presentation
* Responsive administrative UX

Admin consumes the API.

It must not duplicate backend business rules.

For example, Admin may display whether an item is free or paid, but the API remains authoritative for pricing and access behavior.

---

### 3.3 Next.js Web

Web owns:

* Public routing
* Public pages
* Catalog discovery
* Search
* Filtering
* Sorting
* Item presentation
* Responsive public UX
* Localization
* SEO-oriented presentation

Web consumes the API.

It must not make authoritative decisions about:

* Access
* Pricing
* Payment
* Subscription validity
* Entitlements

Those decisions belong to the API.

---

## 4. API-First Contract

The API is the integration boundary between the applications.

The current API namespace is:

```text id="8fj1h4"
/api/v1
```

The contract defines:

* Endpoint paths
* HTTP methods
* Authentication requirements
* Request validation
* Response structures
* Resource representations
* Pagination
* Status codes
* Business semantics

Frontend applications should depend on the documented API contract rather than internal Laravel implementation details.

See `API Contract.md` for the detailed contract.

---

## 5. Backend Request Flow

The backend follows a conventional Laravel request flow:

```text id="yk7vcl"
HTTP Request
     │
     ▼
   Route
     │
     ▼
 Controller
     │
     ├── Form Request
     │
     ├── Authorization
     │
     └── Service / Domain Logic
     │
     ▼
   Model
     │
     ▼
 Database
     │
     ▼
 Resource / Response
     │
     ▼
 HTTP Response
```

Not every request requires every layer.

The architecture intentionally avoids adding a layer when the workflow does not justify it.

---

## 6. Controllers

Controllers are intentionally thin.

A controller should primarily:

1. Receive the request.
2. Coordinate validation/authorization.
3. Invoke the required business operation.
4. Return the appropriate response/resource.

Controllers should not become large containers for business logic.

---

## 7. Form Requests

Form Requests are used for meaningful request validation and authorization concerns.

They keep validation rules out of controllers and provide a clear boundary for incoming data.

Validation should remain explicit and close to the request that requires it.

---

## 8. Services

Services are used when a workflow contains meaningful business orchestration.

Examples include workflows involving:

* Orders
* Payments
* Subscriptions
* Access decisions
* Other multi-step business operations

A service should represent a meaningful operation.

Armin does not require every model to have a service.

---

## 9. Models and Persistence

Eloquent models represent the persistence/domain entities used by the application.

Current core models include:

* User
* Item
* Category
* Tag
* Media
* File
* Access
* Order
* OrderItem
* Payment
* SubscriptionPlan
* Subscription

The database remains an important integrity boundary.

Where a rule can be enforced reliably at the database level, it should be.

Examples include:

* Unique user/item access
* Referential integrity
* Required relationships
* Consistent foreign-key behavior

---

## 10. Domain Model

Armin intentionally uses product-neutral domain terminology.

The primary digital-content entity is:

> `Item`

The Template does not define the core entity as Prompt, Pattern, or Product.

This allows different product instances to reuse the same foundation.

A future product may present `Item` using product-specific terminology at the presentation level without forcing that terminology into the generic backend.

---

## 11. Authentication and Authorization

Authentication is handled by the API.

The current authentication model uses Laravel Sanctum.

Authorization is enforced by the backend.

Administrative endpoints require authenticated administrative access.

Frontend route protection is useful for UX, but it is not a replacement for API authorization.

The API must remain secure even if a client bypasses frontend restrictions.

---

## 12. Access Control

Access decisions are explicit.

Current rules include:

### Free Items

Authenticated users can access free items where the relevant protected endpoint permits access.

### Paid Items

Paid items can be accessed when the user has:

* A direct `Access` record, or
* An active global subscription

### Direct Access

A separately granted `Access` remains valid even if a later subscription expires.

### Hidden Items

Hidden items are not exposed through protected content/access endpoints.

### Guests

Protected access endpoints require authentication.

The API is authoritative for all of these decisions.

---

## 13. AccessResolver

The core access decision is centralized in:

```text
AccessResolver::canAccess(User $user, Item $item): bool
```

This provides one explicit place for the current access rules rather than duplicating them across controllers.

Armin deliberately does not replace this with a generic entitlement engine.

If future products require substantially more complex entitlements, that should be introduced only when justified by real requirements.

---

## 14. Commerce Architecture

The commerce model currently supports two primary order types:

* Item orders
* Subscription orders

The basic relationship is:

```text id="n0v8i7"
Order
 ├── OrderItem
 │     └── Item
 │
 └── Payment
```

Subscription purchases additionally result in a subscription associated with the user and subscription plan.

---

## 15. Price Snapshots

Historical order pricing must not depend on the current price of an Item or Subscription Plan.

Therefore:

```text id="0v6bwo"
Current Item Price
       │
       ▼
   OrderItem
       │
       └── Historical Price Snapshot
```

The same principle applies to subscription purchase pricing.

This preserves historical financial data when product prices change later.

---

## 16. Payment Architecture

Payment processing is abstracted through a gateway contract/resolver.

The current gateway implementations include:

* Fake
* ZarinPal

The general flow is:

```text id="m8u6cp"
Create Order
    │
    ▼
Create Payment
    │
    ▼
Initialize Gateway Payment
    │
    ▼
External Gateway
    │
    ▼
Callback / Verification
    │
    ▼
Verify Payment
    │
    ▼
Transactional Internal Fulfillment
```

External gateway communication should not be treated as if it were part of the database transaction.

Once payment verification succeeds, internal state changes and fulfillment should be performed transactionally.

---

## 17. ZarinPal

ZarinPal is implemented as one of the supported payment gateways.

The Template contains the integration.

Production credentials do not belong in the Template.

They are configured per product instance during productionization.

Therefore:

```text id="q6a4dz"
Template
 └── ZarinPal Integration

Product Instance
 └── Production ZarinPal Credentials
```

This keeps the reusable Template product-independent and prevents production secrets from becoming part of the shared foundation.

---

## 18. Subscription Architecture

Subscriptions are based on:

```text id="2b7p7m"
User
  │
  └── Subscription
          │
          └── SubscriptionPlan
```

A subscription has:

* User
* Plan
* Start time
* End time
* Purchase/order relationship

A subscription is active when:

```text
starts_at <= now < ends_at
```

The current subscription model is intentionally global.

There is no plan-to-item mapping.

An active subscription grants access to paid items according to the current access rules.

---

## 19. Subscription Scope

The current Template intentionally does not implement:

* Recurring billing
* Automatic renewal
* Subscription tiers
* Generic entitlements
* Plan-to-item mapping
* Complex subscription lifecycle orchestration

These are potential future extensions rather than requirements of the current architecture.

---

## 20. Media Architecture

Media uses an explicit Item → Media → File relationship:

```text id="k7x4x2"
Item
 │
 └── Media
       │
       └── File
```

The current model supports:

* Multiple media records per Item
* One primary Media
* Ordering
* Upload
* Delete
* File metadata
* Public file URLs where appropriate

Files must not be deleted when they remain referenced elsewhere.

Media is intentionally not implemented as a universal polymorphic media system.

---

## 21. Protected Content

Public media and protected digital content are separate concerns.

Publicly accessible assets may use public URLs where appropriate.

Protected content must be delivered through an authorization-aware backend capability.

The client should never be trusted to enforce paid-content access by itself.

---

## 22. Admin Architecture

Admin is an API consumer.

Its internal architecture should favor:

* Reusable UI components
* Shared form controls
* Shared tables and filters
* Shared feedback states
* Centralized API types
* Centralized authentication
* Consistent localization
* Responsive layouts

When a real common pattern exists, it should be extracted and reused.

However, extraction should not become abstraction for its own sake.

---

## 23. Web Architecture

Web is an independent Next.js application.

It should favor:

* Reusable UI components
* Clear page composition
* API-driven data
* Responsive layouts
* RTL/LTR support
* Localization
* Accessible interactions
* Appropriate rendering strategies

The Web application should not reproduce backend business rules.

---

## 24. Frontend Independence

The frontend applications are independent of the backend implementation.

This means the API should remain usable by:

* Admin
* Web
* Future applications
* External clients where the contract permits

Changing the frontend technology should not require redesigning the backend domain.

Likewise, backend implementation details should not leak into frontend architecture unnecessarily.

---

## 25. Pagination

Pagination is part of the API contract.

Current defaults distinguish public and administrative workloads.

The current Template uses:

* Public collections: 12 items per page
* Admin collections: 15 items per page

Specific endpoints may use their own documented pagination behavior where required.

---

## 26. Deterministic Ordering

Paginated API responses should use deterministic ordering.

When sorting by a field that can contain duplicate values, a stable secondary ordering should be used where necessary.

This prevents records from moving unpredictably between pages when multiple records share the same primary sort value.

---

## 27. Security Principles

Security responsibilities belong primarily to the backend.

Important principles include:

* Server-authoritative pricing
* Server-authoritative access control
* Authentication on protected endpoints
* Admin authorization
* Request validation
* Ownership checks
* Rate limiting where appropriate
* No production secrets in source control
* Safe payment callback handling
* Transactional fulfillment
* Hidden-resource protection

Frontend restrictions are UX mechanisms, not security boundaries.

---

## 28. Testing Philosophy

The backend is expected to have automated coverage for business-critical behavior.

Important areas include:

* Authentication
* Authorization
* Access control
* Items
* Categories
* Orders
* Payments
* Subscriptions
* Admin endpoints
* Edge cases

Backend tests must run inside the project's Docker environment.

Frontend validation currently relies on:

* TypeScript
* ESLint
* Production builds
* Manual functional verification
* Responsive checks
* Localization checks

A frontend automated test suite may be added later if product complexity justifies it.

---

## 29. Intentional Non-Architecture

Armin intentionally does not currently include:

* Generic repository layers
* Universal service layers
* Generic entitlement engines
* Universal billing engines
* Universal fulfillment engines
* Event buses
* Message buses
* Microservices
* Complex permission frameworks
* Subscription tier abstractions
* Plan-to-item mapping
* Generic polymorphic commerce
* Unnecessary domain abstractions

This is a deliberate architectural decision, not an omission.

The Template should only absorb complexity that has a real and repeated justification.

---

## 30. Product Instance Boundary

Armin provides the reusable technical foundation.

A product instance provides its own:

* Brand
* Product terminology at the presentation level
* Content
* Catalog data
* Pricing configuration
* Domain
* Production infrastructure
* Environment variables
* Payment credentials
* Product-specific business requirements

For example:

```text id="l9s1t4"
Armin
 │
 ├── Generic Item model
 ├── Generic commerce
 ├── Generic access
 ├── Generic subscriptions
 └── Generic frontend foundation
          │
          ▼
      Prompt Box
          │
          ├── Product branding
          ├── Prompt-specific content
          ├── Product copy
          ├── Real catalog
          ├── Production domain
          └── Production credentials
```

Product-specific requirements should not be added to the Template merely because one product needs them.

---

## 31. Evolution Rule

The architecture should evolve in response to real product requirements.

The preferred sequence is:

```text id="l1e5p3"
Real Requirement
      │
      ▼
Identify Repeated Complexity
      │
      ▼
Evaluate Simplest Suitable Solution
      │
      ▼
Introduce Abstraction if Justified
      │
      ▼
Document the Decision
```

Do not introduce infrastructure solely to prepare for hypothetical complexity.

---

## 32. Central Architectural Rule

The central architectural rule of Armin is:

> **Keep the foundation explicit and modular until real product complexity requires more.**

Armin should remain a practical reusable foundation rather than evolving into an abstract framework for every possible SaaS architecture.

The Template exists to accelerate product development, not to become the product itself.
