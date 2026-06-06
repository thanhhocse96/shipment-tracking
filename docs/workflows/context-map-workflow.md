# Context Mapping Workflow

## Purpose

The human-maintained plugin fact/design document defines product scope.
`.context/` is generated working state for milestones and tensions.
`docs/` stores durable engineering workflows and standards.

## Source Order

```text
human fact/design
-> AGENTS.md and AGENTS_*.md
-> .context current state
-> docs and tooling
```

If generated context disagrees with the fact, do not silently choose one.
Record a tension and ask the human.

## Context Files

```text
.context/MILESTONES.md
.context/MILESTONE_ROADMAP.md
.context/TENSIONS_OPEN.md
.context/TENSIONS_ACTIVE.md
.context/TENSIONS_HISTORY.md
```

Rules:

- Do not manually overwrite generated/manual sections.
- Do not promote a milestone without human approval.
- Do not archive tension entries automatically.
- `TENSIONS_OPEN.md` contains unresolved issues.
- `TENSIONS_ACTIVE.md` contains human-resolved decisions active now.
- `TENSIONS_HISTORY.md` changes only during approved transition/archive work.

## When Project Files Need Sync

Update `AGENTS_*.md`, standards, or workflows when a human fact changes:

- plugin identity or prefix
- ownership boundary
- URL, access, SEO, or security contract
- image pipeline behavior
- environment/deploy procedure
- repeatable verification process

Do not copy milestone state into general docs when it belongs only in
`.context/`.

## Verification

```bash
python ../context-mapping/cli.py check-consistency .
git diff --check
git status --short
```

Confirm that:

- Current milestone is unchanged unless explicitly promoted.
- No tension was resolved or archived implicitly.
- `AGENTS.md` and `.context/MILESTONES.md` agree.
- No GeneratePress, theme, WooCommerce, or external plugin source changed.
