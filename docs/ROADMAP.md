# Roadmap

The schedule is a target, not a guarantee. Prioritize a small, secure and usable MVP. All milestones require tests, documentation and review.

## M0 — Project foundation (days 1–3)
- [ ] Plugin bootstrap and WordPress compatibility checks
- [ ] Composer/npm/Vite tooling and build scripts
- [ ] CI for lint, static analysis and automated tests
- [ ] GitHub issue templates, release workflow and ZIP packaging
- [ ] Architecture decision records and contribution standards

**Exit:** clean install/activation, repeatable build and green CI.

## M1 — Flow engine (days 4–7)
- [ ] Versioned flow schema and validation
- [ ] Question types and conditional navigation
- [ ] Draft/publish/duplicate flows with basic admin editor
- [ ] Progress, back navigation and completion state
- [ ] Flow engine tests including malformed/cyclic graphs

**Exit:** editable and published multi-step flow works end to end.

## M2 — Public experience (days 8–11)
- [ ] Floating launcher and modal
- [ ] Embedded shortcode/block integration
- [ ] Responsive and accessible components
- [ ] Global design tokens and display options
- [ ] Session continuation and reduced-motion support

**Exit:** usable on mobile and desktop with keyboard navigation.

## M3 — Leads and delivery (days 12–15)
- [ ] Submission tables, migrations and admin listing
- [ ] Email notifications and CSV export
- [ ] Signed webhooks with idempotency and retries
- [ ] Rate limiting, spam prevention and permission checks
- [ ] Privacy policy text, export/erase and retention controls

**Exit:** reliable lead capture and secure delivery under failure conditions.

## M4 — Release 1.0 (days 16–20)
- [ ] Integration and browser tests
- [ ] Security/accessibility/performance audit
- [ ] WordPress compatibility matrix and staging verification
- [ ] Installation/upgrade/uninstall/rollback documentation
- [ ] Tagged release and downloadable ZIP

**Exit:** release checklist passes; deploy to first production site.

## Post-MVP
- **1.1:** optional Odoo Community adapter, field mapping and synchronization log.
- **1.2:** AI Q&A, knowledge sources and human handoff.
- **1.3:** templates, analytics, import/export and contextual triggers.

**Out of scope:** WhatsApp.
