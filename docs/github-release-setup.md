# GitHub Release Setup

These steps are for the framework maintainer repository at `lprzybylskidev-prog/lpwork.git`.

## Before The First Push

- Keep the repository public only when the source-available permission model in `LICENSE.md` is acceptable for portfolio/CV review.
- Confirm GitHub Actions permissions allow workflows to run with read-only repository contents access.
- Do not create the `v1.0.0` tag yet.
- Do not configure branch protection or rulesets yet; GitHub needs the first `main` push and one successful workflow run before the real branch and check names are selectable.

## First Local Git Setup

When the release-prep files are ready and local verification passes:

```bash
git init
git branch -M main
git remote add origin https://github.com/lprzybylskidev-prog/lpwork.git
git config core.hooksPath .githooks
```

Make focused commits rather than one mixed release-prep commit when unrelated changes exist. Push only after reviewing the worktree and running the verification suite.

## After The First Successful CI Run

Configure branch protection or a repository ruleset for `main`:

- require pull request review before merging if future changes will use PRs;
- require status checks to pass before merging;
- select the actual CI check name exposed by GitHub, expected to be similar to `Framework verification`;
- require branches to be up to date before merging when that fits the workflow;
- block force pushes and branch deletion for `main`.

Then update repository About metadata:

- Description: `Personal PHP framework learning project and source-available portfolio snapshot.`
- Website: leave empty unless a real project page exists.
- Topics: consider `php`, `framework`, `portfolio`, `learning-project`, `source-available`.

## Preparing `v1.0.0`

Only after local hooks and CI pass:

- set LPWork framework version metadata to `v1.0.0`;
- fill both standalone installers with the immutable GitHub tag archive URL;
- commit those release-specific changes;
- create immutable tag `v1.0.0`;
- publish a GitHub release from the tag with notes that this is the first public snapshot, not the end of LPWork development.
