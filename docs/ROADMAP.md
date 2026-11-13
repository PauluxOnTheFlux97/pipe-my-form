# Roadmap

The schedule is a target, not a guarantee. All milestones require tests, documentation and review.

## M0 — Project foundation
- [x] Plugin bootstrap and repository layout
- [x] Documentation and initial PHP syntax CI
- [ ] Composer/npm/Vite tooling and reproducible release ZIP
- [ ] WordPress integration test environment and full CI

## M1 — Flow engine
- [x] Initial versioned flow schema, validation and cycle/reachability detection
- [x] Pure PHP conditional navigation and answer validation
- [x] Private flow post type and read-only published snapshot REST endpoint
- [ ] Capability-checked draft editor and REST write API
- [ ] Explicit versioned publication, revisions and immutable history
- [ ] Full integration/unit test coverage and WordPress compatibility verification

## M2 — Public UX
- [ ] Floating launcher and modal
- [ ] Shortcode/block integration
- [ ] Responsive accessible components and design tokens
- [ ] Session persistence and reduced-motion support

## M3 — Leads and delivery
- [ ] Submission persistence, migrations, admin listing and CSV
- [ ] Email notifications and signed webhooks with retries
- [ ] Rate limits, anti-spam, privacy export/erase and retention

## M4 — Release 1.0
- [ ] E2E, security, accessibility and performance checks
- [ ] Staging, rollback, packaging and tagged release

## Post-MVP
1.1 optional Odoo Community integration; 1.2 AI and human handoff; 1.3 templates and analytics. WhatsApp is out of scope.
