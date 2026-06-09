# Automatic Main Patch Version Design

## Goal

Create a new semantic-version tag after every successful push to `main`.

The current latest package tag is `v1.0.6`. The first workflow run after this
feature is merged must create `v1.0.7`, and subsequent pushes must create
`v1.0.8`, `v1.0.9`, and so on.

## Selected Approach

Use a GitHub Actions workflow triggered by pushes to `main`. The workflow reads
the repository tags, calculates the next patch version, creates an annotated
tag on the pushed commit, and sends the tag to the repository.

Composer libraries derive package versions from Git tags. The workflow must
not add a `version` field to `composer.json` and must not create version-bump
commits.

## Trigger And Permissions

The workflow runs on:

```yaml
on:
  push:
    branches:
      - main
```

It requires `contents: write` permission so `GITHUB_TOKEN` can create and push
tags.

The checkout step must fetch full history and all tags by using
`fetch-depth: 0`.

## Version Calculation

Only tags that exactly match `v<major>.<minor>.<patch>` participate in version
selection. Pre-release tags, malformed tags, and unrelated tags are ignored.

The workflow sorts valid semantic versions and selects the highest one. It
keeps the major and minor numbers and increments only the patch number.

Examples:

```text
v1.0.6 -> v1.0.7
v1.2.9 -> v1.2.10
v2.0.0 -> v2.0.1
```

If the repository has no valid semantic-version tag, the initial tag is
`v0.0.1`.

## Tag Creation

The new annotated tag points directly to the commit that triggered the
workflow. Its message is:

```text
Release vX.Y.Z
```

The workflow pushes only the newly created tag. A tag push does not retrigger
the workflow because the workflow listens only to pushes on the `main` branch.

## Concurrency And Duplicate Protection

The workflow uses one concurrency group for automatic versioning on `main`.
Runs are not cancelled when a newer push arrives; they execute sequentially so
every pushed commit can receive its own version.

Immediately before calculating the version, the job fetches tags from the
remote. Before creating the tag, it verifies that the target commit does not
already have an exact semantic-version tag. If it does, the workflow exits
successfully without creating another version.

If a tag collision still occurs because of an external concurrent tag push,
the push fails visibly instead of overwriting an existing tag.

## Failure Behavior

The workflow must fail when:

- Tags cannot be fetched.
- The calculated tag already exists unexpectedly.
- Git cannot create the annotated tag.
- The tag cannot be pushed.

Existing tags must never be moved, deleted, or force-pushed.

## Verification

Validation includes:

1. YAML syntax and workflow structure.
2. Trigger restricted to `main`.
3. `contents: write` permission.
4. Full tag checkout.
5. SemVer filtering and numeric sorting.
6. Patch increment from `v1.0.6` to `v1.0.7`.
7. Initial fallback to `v0.0.1`.
8. Duplicate protection for an already-versioned commit.
9. Concurrency configured without cancelling pending runs.
10. No repository credentials or custom secrets in the workflow.
