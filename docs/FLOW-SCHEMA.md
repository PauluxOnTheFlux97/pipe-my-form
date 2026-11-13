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

## WordPress integration
`pmf_flow` is a private custom post type. The current prototype expects a JSON definition in protected post meta `_pmf_flow_definition`; on first publication it validates and freezes a snapshot in `_pmf_published_snapshot`. Published snapshots are readable via `GET /wp-json/pipe-my-form/v1/flows/{id}`.

**Important limitations:** No admin editor or meta registration exists yet; publishing through the stock WordPress screen will be rejected without the definition. There is no session storage, lead capture, submission endpoint, versioned republishing or public UI. This initial API is read-only. A proper editor, capability-checked draft API and explicit versioned publish operation are next.
