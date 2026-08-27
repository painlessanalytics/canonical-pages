# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Pull request review workflow

- **When you open a PR** (or when the user asks you to create one), subscribe to
  it with `subscribe_pr_activity` so this session receives its activity
  (comments, reviews, CI). Do this right after the PR is created.
- **When Copilot (or any reviewer) leaves comments**, read them and review each
  one on its merits. Do **not** apply changes automatically.
- **List the findings back to the user here in chat**, and for each one give:
  - a short assessment of whether it's valid (and whether it's in scope /
    pre-existing), and
  - concrete options with a recommended choice.
- **Let the user decide** what to change and how. Only implement after they pick.
- After implementing approved fixes, rebuild any generated assets (see below),
  commit, and push to the PR branch.

## Build notes

- The editor sidebar is authored in `admin/edit.js` (JSX) and bundled to
  `admin/edit.min.js` via webpack. After changing `edit.js`, rebuild:
  `cd admin && npm install && npx webpack -p`. Commit both files.
- `node_modules` is gitignored and may be absent in a fresh session — run
  `npm install` in `admin/` before building.
