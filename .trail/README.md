# .trail

Short notes from earlier work sessions on this repo, kept next to the code so the next session starts from them instead of digging again. They are plain markdown files. No tool reads or writes them.

## Learnings

`learnings/` holds one file per session that took real digging. Read the ones that bear on your task before you explore.

File name: `<date>-<slug>-<author>.md`, for example `2026-10-25-boot-cost-with-many-modules-where-nwidart.md`.

Each file starts with YAML frontmatter:

| Field | Meaning |
| --- | --- |
| `title` | One line naming the question and its answer. |
| `author` | Who wrote it. |
| `date` | Day of the session, `YYYY-MM-DD`. |
| `branch` | Branch the work happened on. |
| `cost` | What the session took, for example `{ minutes: 14, tokens: 53134 }`. |
| `id` | Unique id, for example `l-869d3998`. |
| `scope` | `repo` for notes that apply to the whole repo. |
| `status` | `active` while the note holds. |
| `src` | `agent` when an agent wrote it. |
| `touches` | List of files the note concerns. |

The body uses these sections, and skips one when it has nothing to say:

- `## Decided`: what was chosen, and why.
- `## Tried and ruled out`: options that failed or lost, so nobody retries them.
- `## Watch out`: traps the next change in this area can hit.

## Writing a learning

Keep it a short summary of what the next session needs. Never paste a transcript. Commit the new file with the change it came from.
