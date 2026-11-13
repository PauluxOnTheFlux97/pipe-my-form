# Development guide

## Requirements
WordPress 6.5+ and PHP 8.2+ are initial targets to validate in CI, not a tested compatibility guarantee. Node.js and Composer will be configured in the foundation milestone.

## Workflow
1. Create a GitHub issue with scope and acceptance criteria.
2. Branch from `main`: `feat/...`, `fix/...`, `chore/...` or `docs/...`.
3. Add tests and update docs for behavior changes.
4. Open a pull request; run CI; review before merge.
5. Package a reproducible ZIP and test in staging before tagging.

## Conventions
PHP classes use `PaulMaudry\\PipeMyForm` namespace and `PMF_` for global symbols. REST namespace: `pipe-my-form/v1`. Never make direct production changes or commit real customer data.
