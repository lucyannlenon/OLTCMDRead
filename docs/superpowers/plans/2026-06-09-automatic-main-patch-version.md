# Automatic Main Patch Version Implementation Plan

## Objective

Add a GitHub Actions workflow that creates the next patch SemVer tag for every
new commit pushed to `main`.

## Tasks

1. Create `.github/workflows/auto-version.yml`.
2. Trigger only on pushes to `main`.
3. Grant `contents: write` and fetch complete history and tags.
4. Serialize runs with a repository-wide versioning concurrency group.
5. Fetch remote tags immediately before version calculation.
6. Skip commits that already have an exact `vX.Y.Z` tag.
7. Select the highest valid SemVer tag and increment its patch number.
8. Create an annotated tag on `GITHUB_SHA` and push only that tag.
9. Validate YAML structure and shell behavior for existing tags, no tags, and
   already-versioned commits.
