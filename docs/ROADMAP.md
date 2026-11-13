# Roadmap

The schedule is a target, not a guarantee. All milestones require tests, documentation and review. Checkboxes mean **merged and verified on `main`**, not merely proposed in a PR.

## M0 — Project foundation — partial
- [x] Plugin bootstrap and repository layout
- [x] Documentation and initial PHP syntax CI
- [ ] Composer/npm/Vite tooling and reproducible release ZIP
- [ ] WordPress integration test environment and full CI

## M1 — Flow engine — in progress ([issue #2](https://github.com/PauluxOnTheFlux97/pipe-my-form/issues/2))
- [x] Initial versioned flow schema, validation and cycle/reachability detection (PR #9)
- [x] Pure PHP conditional navigation and answer validation (PR #9)
- [x] Private flow post type and read-only published snapshot REST endpoint (PR #9)
- [x] JSON-based WordPress admin editing, capability-checked draft REST API and explicit validated publishing (PR #10)
- [x] Immutable version snapshots, optimistic draft revisions and lifecycle smoke tests (PR #10)
- [ ] Structured question editor (addition, removal, order, choices, conditional connections) — proposed in PR #11; pending review, CI and merge
- [ ] Flow revisions/history UI and draft duplication
- [ ] WordPress integration tests, audit of publish permissions and compatibility verification
- [ ] Schema-level cross-field checks, type-specific validation, comprehensive edge-case tests

**M1 acceptance:** an authorized editor can create a flow, edit it without exposing the draft, validate and publish a version, view the published flow in the read-only REST endpoint, and safely update it without modifying prior snapshots. Verify in staging.

## M2 — Public UX ([issue #3](https://github.com/PauluxOnTheFlux97/pipe-my-form/issues/3))
- [ ] Floating launcher and modal
- [ ] Shortcode/block integration
- [ ] Responsive accessible components and design tokens
- [ ] Session persistence and reduced-motion support

## M3 — Leads and delivery ([issue #4](https://github.com/PauluxOnTheFlux97/pipe-my-form/issues/4))
- [ ] Submission persistence, migrations, admin listing and CSV
- [ ] Email notifications and signed webhooks with retries
- [ ] Rate limits, anti-spam, privacy export/erase and retention

## M4 — Release 1.0 ([issue #5](https://github.com/PauluxOnTheFlux97/pipe-my-form/issues/5))
- [ ] E2E, security, accessibility and performance checks
- [ ] Staging, rollback, packaging and tagged release

## Post-MVP
- [ ] 1.1: optional Odoo Community integration (#6)
- [ ] 1.2: AI and human handoff (#7)
- [ ] 1.3: templates and analytics

**Out of scope:** WhatsApp.
