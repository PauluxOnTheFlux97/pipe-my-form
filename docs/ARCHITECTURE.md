# Architecture

## Design principles
WordPress-first, modular monolith, progressive enhancement, minimum dependencies and explicit public API contracts. Never hard-code Infocare branding or endpoints.

## Components
- PHP 8.2+ backend: Core, Admin, Api, Flows, Submissions, Integrations and Security.
- React + TypeScript public renderer and admin UI (planned).
- WordPress REST routes under `pipe-my-form/v1` (planned).
- Custom post type `pmf_flow` for editable flow definitions; dedicated versioned SQL tables for submissions, sessions and delivery attempts (planned).
- WordPress options for global settings and integrations; secrets stored server-side, never exposed to browser configuration.

## Flow format
Published flows are immutable snapshots with a schema version, stable node IDs, typed questions, conditional edges, a default route and completion actions. Validate all input and detect unreachable nodes/cycles before publishing.

## Submission lifecycle
Start anonymous session -> validate responses -> persist submission atomically -> enqueue delivery -> retry idempotently with bounded backoff -> expose delivery status in admin. Do not claim success until durable persistence.

## Extensibility
PHP actions/filters and versioned JavaScript events; integration adapters implement a common contract. Email and signed webhook adapters ship in MVP; Odoo adapter is post-MVP.

## Suggested endpoints (not implemented)
`GET /config`, `GET /flows/{id}`, `POST /sessions`, `POST /sessions/{id}/answers`, `POST /sessions/{id}/complete`, `POST /leads`.

## Testing
PHPUnit for domain rules and security, Vitest for React/TypeScript logic, Playwright for user journeys; WordPress integration tests in CI. Verify PHP and WordPress compatibility before release.
