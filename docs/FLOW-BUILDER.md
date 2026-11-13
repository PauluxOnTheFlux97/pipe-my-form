# Structured Flow Builder (M1)

The initial WordPress admin builder lets editors define a flow without writing JSON.

## Usage

1. Install the development plugin on a **staging** WordPress instance and activate it.
2. Go to **Pipe My Form flows → Add New**, enter a title, and save as **Draft**.
3. In the flow list, select **Flow Builder** for that item.
4. Edit questions, their unique IDs, types, labels, required flags, and (for choice questions) options separated by newlines.
5. Add connections between question IDs. Pick a comparison operator and a value for conditional branches. Leave the operator empty for a fallback connection. Drag-and-drop is not required; use the **Move up / Move down** buttons.
6. Set **Starting question ID** to an existing question.
7. Select **Save draft** to preserve work or **Save & publish** to validate and release the new snapshot.

Connection order matters: matching conditions run in order, then the first unconditional connection is the default. A question without outgoing connections completes the flow. Every node must be reachable from the starting node and published graphs cannot contain cycles.

## Editing principles

- IDs stay stable across reordering. Changing an ID requires updating incoming connections.
- The visual controls represent the schema-v1 fields: `id`, `type`, `label`, `required`, `options`, `edges`, `start`.
- An optional JavaScript enhancement adds/removes/moves questions and edges. Form submission and display remain server-rendered.
- The existing **Advanced JSON editor** remains available for advanced configuration.
- Save draft is optimistic-concurrency-protected; a stale revision reports a conflict.
- Publishing validates the flow and replaces only the current public snapshot. Historical published snapshots remain intact.

## Current limitations and staging checklist

- The builder is a **prototype**, not a page-canvas or drag-and-drop graph editor.
- Only schema-v1 fields are supported by the structured interface. Advanced unsupported custom fields are not preserved when saving through this editor; use the JSON editor in that case.
- There is no live end-user preview, submission capture, analytics, duplication, or visual revision history yet.
- [ ] Validate admin roles: subscriber cannot edit; author permissions are per-post; publishing requires `publish_posts`.
- [ ] Verify new, existing, and published flows; refresh and stale-editor conflicts.
- [ ] Verify conditional branches, multiple choice options, removing a referenced node and cyclic flow rejection.
- [ ] Verify the public REST endpoint keeps the last validated publication when a draft is modified.
- [ ] Verify mobile layout, keyboard operation and accessibility labels on a supported WordPress release.
