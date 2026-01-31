# Releasing TallCMS

This document describes the release process for TallCMS.

## Prerequisites

- GitHub CLI (`gh`) installed and authenticated
- Push access to `tallcms/tallcms` repository
- The `PACKAGE_SPLIT_TOKEN` secret configured for automated package sync

## Release Process

### 1. Prepare the Release

Ensure all changes are merged to `main` and tests pass.

### 2. Update Package Version

**Important:** The version in `packages/tallcms/cms/composer.json` must match the tag version, otherwise Packagist will reject the release.

```bash
# Edit the version field in composer.json
# Change: "version": "X.Y.Z"
```

### 3. Commit Version Bump

```bash
git add packages/tallcms/cms/composer.json
git commit -m "Bump package version to X.Y.Z"
git push origin main
```

### 4. Create Release

Use `gh release create` to create both the tag and GitHub Release in one step:

```bash
gh release create vX.Y.Z --title "vX.Y.Z - Release Title" --notes "Release notes here..."
```

For longer release notes, use a heredoc:

```bash
gh release create vX.Y.Z --title "vX.Y.Z - Release Title" --notes "$(cat <<'EOF'
## New Features

- Feature 1
- Feature 2

## Bug Fixes

- Fix 1
- Fix 2
EOF
)"
```

### 5. Verify

After creating the release:

1. **GitHub Actions**: The `sync-package-release.yml` workflow automatically:
   - Waits for the package subtree split to complete
   - Creates a matching release on `tallcms/cms`

2. **Packagist**: Check https://packagist.org/packages/tallcms/cms to verify the new version appears

## Fixing a Bad Release

If you need to fix a release (e.g., forgot to update composer.json):

```bash
# 1. Delete the GitHub release
gh release delete vX.Y.Z --repo tallcms/tallcms --yes

# 2. Delete the tag locally and remotely (main repo)
git tag -d vX.Y.Z
git push origin :refs/tags/vX.Y.Z

# 3. Delete the tag from package repo via API
gh api -X DELETE repos/tallcms/cms/git/refs/tags/vX.Y.Z

# 4. Delete the release from package repo (if created)
gh release delete vX.Y.Z --repo tallcms/cms --yes

# 5. Make your fixes, commit, and push
git add .
git commit -m "Fix for vX.Y.Z"
git push origin main

# 6. Recreate the release
gh release create vX.Y.Z --title "vX.Y.Z - Title" --notes "..."
```

## Version Numbering

We follow [Semantic Versioning](https://semver.org/):

- **Major (X.0.0)**: Breaking changes
- **Minor (X.Y.0)**: New features, backwards compatible
- **Patch (X.Y.Z)**: Bug fixes, backwards compatible

## Updating the Roadmap

For significant releases, update `ROADMAP.md`:

1. Add release notes under the appropriate version section
2. Update the version history table
3. Update the "Last updated" date at the bottom

## Checklist

- [ ] All changes merged to `main`
- [ ] Tests passing
- [ ] `packages/tallcms/cms/composer.json` version updated
- [ ] Version bump committed and pushed
- [ ] `ROADMAP.md` updated (for significant releases)
- [ ] Release created with `gh release create`
- [ ] Packagist shows new version
- [ ] Package repo has matching release
