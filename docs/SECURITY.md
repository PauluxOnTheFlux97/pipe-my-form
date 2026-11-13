# Security and privacy baseline

- Escape output; sanitize and validate every request field server-side.
- Use WordPress capabilities for administrative actions; nonces are not authorization and do not stop anonymous spam.
- Rate-limit public routes, prevent enumeration and use anti-abuse controls.
- Encrypt or protect integration credentials at rest where feasible; never commit secrets.
- Sign outbound webhook requests, limit destinations to mitigate SSRF, use bounded timeouts and retries, and avoid duplicate delivery effects.
- Collect only necessary personal data; document purpose, retention, export and erasure mechanisms.
- Use prepared SQL statements, schema migrations and least-privilege database access.
- Do not send customer data to an AI service without a documented configuration and appropriate disclosures.
- Security reports: contact hello@paulmaudry.fr privately; do not publish exploit details in issues.
