# Context Mapping Workflow

## Purpose

`.context/` is the AI working memory and constraint layer.

`docs/` is the human architecture memory.

## V1 Decision

`.context/` may temporarily live in `main`.

This is acceptable because V1 is local/dev-oriented.

## V2 Decision

Separate branches:

```txt
main = production clean
dev = active development + context
feature/* = small tasks
```

Move `.context/` out of production branch if needed.

## Recommended Context Files

```txt
.context/
  PROJECT.md
  THEME_SKVN_MARINE.md
  PLUGIN_SKVN_MARINE_BLOCKS.md
  BLOCK_SLIDER.md
  QUOTE_FLOW.md
  MILESTONES.md
  MILESTONES_HISTORY.md
  TENSIONS_OPEN.md
  TENSIONS_ACTIVE.md
  TENSIONS_HISTORY.md
```

## Tension Register

Use `.context/TENSIONS_OPEN.md` whenever AI finds a new unresolved conflict, such as:

- Wanting to edit GeneratePress parent theme.
- Wanting to add a dependency without approval.
- Wanting to place custom blocks in the theme.
- Wanting to overwrite editor-provided ALT text.

V3 state files:

- `.context/TENSIONS_OPEN.md`: only `Status: OPEN`.
- `.context/TENSIONS_ACTIVE.md`: only `Status: RESOLVED_ACTIVE` for the current milestone.
- `.context/TENSIONS_HISTORY.md`: only `Status: ARCHIVED`.

Run consistency check after changing context files:

```bash
context-gen check-consistency .
```

## Decision Docs And Context Updates

Use this section when a human-approved change lands outside the normal workflow, such as CSS hardening discovered from real Tailwind artifacts, a debugging session, artifact test data, customer feedback, or a one-off scope decision.

The goal is to make the decision discoverable for future agents without scattering assumptions.

### When To Update Docs

Update docs/context when a change:

- Changes what future agents should assume.
- Adds or narrows milestone scope without switching milestone.
- Documents an exception to an existing workflow.
- Updates a visual, architecture, data, security, or editor-governance contract.
- Comes from real testing evidence that should not be lost.

Do not use this path to silently bypass project rules. If the change conflicts with a manual invariant, create or update a tension first.

### Update Targets

1. Decision doc

Create or update `docs/decisions/<topic>.md` when the decision should be auditable by humans.

Include:

- Decision source: who approved it and why it exists.
- Scope: milestone/version and affected layer.
- What is allowed.
- What is explicitly not allowed.
- Evidence: bug, artifact, test data, user requirement, or review finding.
- Follow-up checklist if implementation is not complete.

2. Milestone checklist

Update `.context/MILESTONES.md` only when the decision changes acceptance criteria, confirms a checklist item, or adds a milestone-specific guardrail.

Rules:

- Keep the current milestone unchanged unless the human explicitly approves a milestone transition.
- Add a checked item only when the decision is already documented or implemented.
- Add an unchecked item when work remains required before milestone completion.
- Do not archive milestone notes unless the human approves moving to a new milestone.

3. Module context

Update the relevant `.context/modules/<module>.md` when future agents need the decision before editing that module.

Use the module that owns the behavior:

| Change type | Module context |
|---|---|
| Theme CSS, tokens, patterns, editor parity | `.context/modules/THEME_SKVN_MARINE.md` |
| Gutenberg block logic, HTML-2-Gutenberg tooling, validators | `.context/modules/PLUGIN_SKVN_MARINE_BLOCKS.md` |
| Quote path, CF7/CFDB7, n8n, request quote UX | `.context/modules/QUOTE_FLOW.md` |

Module context should state:

- The decision in one short section.
- The ownership boundary.
- What future agents must check before changing code.
- What remains forbidden.

4. Global index

Update `.context/GLOBAL.md` when adding a new active doc under `docs/`.

Add the doc to `Current active docs` if future agents should know it exists during normal context scanning.

Do not add proposal files or temporary review scratch files. `.context/proposals/` and `docs/artifacts/` are not automatically source of truth.

5. Tension files

Use `.context/TENSIONS_OPEN.md` when the decision is not fully resolved or conflicts with an existing manual rule.

Use `.context/TENSIONS_ACTIVE.md` only when the human resolves an open tension for the current milestone.

Rules:

- `TENSIONS_OPEN.md` contains only `Status: OPEN`.
- `TENSIONS_ACTIVE.md` contains only `Status: RESOLVED_ACTIVE`.
- `TENSIONS_HISTORY.md` is touched only during human-approved milestone transition.
- Do not infer resolved state from `Decision`; use `Status`.

6. Workflow or standard docs

Update `docs/workflows/` when the change affects repeatable process.

Update `docs/standards/` when the change affects coding, security, branding, AI behavior, or review standards.

Keep decision docs and workflow docs separate:

- Decision docs answer: what did we decide and why?
- Workflow docs answer: what should devs/agents do next time?

### File Count Rule

Prefer 2-4 files:

```text
docs/decisions/<decision>.md
.context/MILESTONES.md
.context/modules/<module>.md
.context/GLOBAL.md
```

Add a workflow or standards doc only when the process itself changed.

Do not edit more than 5 files unless the decision truly crosses multiple modules.

### Minimal Decision Template

```markdown
# <Decision Title>

Status: active decision.
Milestone: V1 / <version>.
Owner: <theme/plugin/workflow/module>.
Decision source: <human/debug/artifact/review source>.

## Decision

<What is now allowed or required.>

## Scope

- Allowed:
- Not allowed:

## Evidence

<Why this was needed.>

## Agent Update Checklist

- [ ] `.context/MILESTONES.md` updated if milestone acceptance changed.
- [ ] Relevant `.context/modules/<module>.md` updated.
- [ ] `.context/GLOBAL.md` updated if this doc is active.
- [ ] Tension file updated if conflict remains unresolved.
```

### Verification

Before submit:

```powershell
git diff --check -- docs .context
git status --short
```

Review:

- New docs are UTF-8 and readable.
- No GeneratePress parent files changed.
- Current milestone in `AGENTS.md` still matches `.context/MILESTONES.md`.
- No resolved tensions were archived without human milestone transition approval.
- New decision is discoverable from `.context/GLOBAL.md` if it should guide future agents.
