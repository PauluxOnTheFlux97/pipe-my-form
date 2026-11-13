# Flow schema v1 (draft)

This document describes the initial contract implemented by the PHP validator and navigation engine. It is not yet a full public API.

```json
{
  "schema_version": 1,
  "start": "intent",
  "nodes": [
    {
      "id": "intent",
      "type": "single_choice",
      "label": "How can we help?",
      "required": true,
      "options": ["sales", "support"],
      "edges": [
        {"to": "email", "when": {"operator": "equals", "value": "sales"}},
        {"to": "support"}
      ]
    },
    {"id": "email", "type": "email", "label": "Your email", "required": true, "edges": []},
    {"id": "support", "type": "info", "label": "Contact support", "edges": []}
  ]
}
```

Nodes are a nonempty list (max 100) with unique lowercase IDs. Types: text, textarea, email, tel, single_choice, multiple_choice, consent, info. A node may have up to 50 edges; the first matching conditional edge wins, otherwise the first unconditional edge is the fallback. No outgoing edge means completion. Conditions compare the current node's answer using equals, not_equals or contains. Graphs must be acyclic and every node reachable.

## WordPress integration (M1 editor prototype)

`pmf_flow` is a private custom post type. The regular WordPress **Publish** action is guarded. Create a flow with **Add New**, save it as a draft, then select **Edit flow JSON** in the flow list. This dedicated editor allows saving a JSON draft or publishing a validated snapshot; incomplete but syntactically valid JSON objects are allowed as drafts.

Publishing creates immutable post-meta version keys (`_pmf_snapshot_v1`, `_pmf_snapshot_v2`, ...), advances the published version number and updates the public snapshot. A new draft never changes the active public snapshot until explicitly published. Previous snapshots are stored for audit but no history UI has been built yet.

### REST endpoints

| Method | Route | Authorization | Purpose |
| --- | --- | --- | --- |
| GET | `/wp-json/pipe-my-form/v1/flows/{id}` | Public | Published snapshot only (404 for drafts) |
| GET | `/wp-json/pipe-my-form/v1/admin/flows/{id}` | `edit_post` | Draft definition, revision, published version |
| PUT | `/wp-json/pipe-my-form/v1/admin/flows/{id}` | `edit_post` | Save JSON draft |
| POST | `/wp-json/pipe-my-form/v1/admin/flows/{id}/publish` | `edit_post` + `publish_posts` | Validate and publish |

PUT body example: `{"definition":{"schema_version":1,"start":"intro","nodes":[{"id":"intro","type":"info","label":"Hello","edges":[]}]},"expected_revision":0}`.

Publish body example: `{"expected_revision":1}`.

Authenticated browser requests use a WordPress REST nonce, and external clients use properly authenticated WordPress credentials. An outdated `expected_revision` receives HTTP 409. Invalid flows cannot be published and return HTTP 422. A previously published flow keeps serving its last snapshot.

### Remaining limitations

- JSON editing only, not the structured visual builder.
- No public form renderer, sessions, submissions, emails or CRM delivery.
- No draft duplication, rich revisions UI, cross-node answer type checks, or WordPress integration suite yet.
- Inspect GitHub CI and test against a real WordPress installation before merging or production use.
