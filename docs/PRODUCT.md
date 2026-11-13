# Product specification

## Audience and promise
A reusable WordPress plugin for businesses that want to replace generic contact forms with guided, configurable experiences. Infocare is the first target implementation, not a hard-coded dependency.

## MVP user journeys
1. Visitor opens a floating launcher, a modal or an embedded flow.
2. Visitor selects a purpose (guided diagnosis, general inquiry, appointment link or existing-customer external link).
3. Conditional questions adapt to previous answers.
4. Visitor reviews a contextual summary and optionally submits contact information.
5. Submission is stored, notification is sent and configured webhook delivery is queued.

## Admin capabilities
- Create, draft, publish, duplicate and archive flows.
- Configure question types: text, textarea, email, telephone, single/multiple choice, consent and informational screens.
- Define conditional routing with a safe fallback and cycle detection.
- Configure theme tokens, launcher position, labels, motion preference and display contexts.
- Review/export/delete submissions with capability checks.
- Configure notification recipients, webhook destinations and retention.

## Non-goals for 1.0
Visual canvas editor, AI chatbot, shared human inbox, Odoo-native connector, WhatsApp, booking engine, customer SSO and advanced analytics.

## Definition of done
Installable ZIP; no fatal errors; documented migrations; automated unit and browser tests; accessible keyboard flow; no secret exposure; reliable submission persistence and webhook retries; staging sign-off and rollback plan.
