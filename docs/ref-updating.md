---
title: "Updating TallCMS"
slug: "updating"
audience: "all"
category: "reference"
order: 60
---

# Updating TallCMS

Complete reference for keeping a TallCMS installation up to date. The steps differ depending on whether you run TallCMS as a **standalone** application or as a **plugin** in an existing Filament app.

> **Not sure which mode you run?** If a `.tallcms-standalone` file exists at your project root, you're in standalone mode. Otherwise you're in plugin mode.

---

## Standalone Mode

Standalone installations ship with a built-in updater that downloads signed release bundles from GitHub, verifies them, and replaces the package files in place.

### Two Ways to Update

#### Admin UI

Navigate to **Admin > System > Updates**. Click **Update Now** when a new version is available. A progress screen shows each step (download → verify → backup → apply → migrate → clear caches).

Use this path when:
- You're already in the admin and want a one-click update.
- You want the visual progress feedback.

#### CLI

```bash
php artisan tallcms:update
```

Use this path when:
- You're deploying via SSH, Forge, Envoyer, etc.
- You want to script the update as part of a deploy pipeline.
- The admin session is unreachable.

### What the Updater Does

Every update runs these steps in order:

1. **Preflight checks** — verifies the `sodium` PHP extension, disk space, and that no other update is in progress.
2. **Fetch latest release** metadata from GitHub.
3. **Download** the release archive, checksums, and signature.
4. **Verify** the Ed25519 signature against the checksums — aborts if tampered.
5. **Back up files** to `storage/app/tallcms-backups/files/<timestamp>/`.
6. **Back up the database** to `storage/app/tallcms-backups/db/<timestamp>.sql` (or `.sqlite`).
7. **Extract** the release to a temp directory.
8. **Detect changes** — compares checksums to your installed manifest. Locally modified files that upstream also changed are quarantined.
9. **Apply** the file updates, respecting preserved paths (see below).
10. **Run `migrate --force`** to apply schema changes.
11. **Run `composer install --no-dev --optimize-autoloader`** to sync dependencies.
12. **Clear caches and re-sync shipped assets/roles**:
    - `config:clear`, `route:clear`, `view:clear`, `cache:clear`
    - `filament:assets` — republishes Filament CSS/JS to match the installed component versions
    - `tallcms:shield-sync-site-owner` — ensures the `site_owner` role (introduced in v4.0.14) exists on the install. Idempotent; no-op on installs that already have it.
    - `opcache_reset()` if the extension is enabled
13. **Save the new manifest** and release the update lock.

### Preserved Paths

The updater never overwrites these — they contain your data and customizations:

- `.env` and `.env.backup`
- `storage/` (uploads, logs, backups, sessions)
- `themes/` (installed themes)
- `plugins/` (installed plugins)
- `public/storage` (symlink to storage/app/public)
- `public/themes/` (theme-published assets)
- `database/database.sqlite` (SQLite database)

### CLI Options

| Option | Effect |
|--------|--------|
| `--target=<version>` | Install a specific version instead of the latest. Example: `--target=4.0.8`. |
| `--dry-run` | Show what would happen without making changes. Useful for previewing a risky update. |
| `--force` | Skip the interactive confirmation prompt. Useful in CI. |
| `--skip-backup` | Skip both file and database backups. **Not recommended** outside of CI/testing. |
| `--skip-db-backup` | Skip only the database backup. Use when `mysqldump`/`pg_dump` isn't on the server's PATH or you back up externally. See [troubleshooting](#database-backup-not-available-mysqldump-not-available-on-this-server). |

### Pre-Update Checklist

1. **Note your current version** — `php artisan about` or visit **Admin > System > Updates**.
2. **Check disk space** — you need roughly 3× the current install size (temp extraction + file backup + database backup).
3. **Run the dry-run** to preview changes — `php artisan tallcms:update --dry-run`.
4. **Review quarantine warnings** — any locally modified core file that upstream also changed will be moved aside. If you've intentionally patched the package, stop and merge your changes upstream first.
5. **Back up externally** — the updater creates local backups, but a disk failure mid-update defeats that. Snapshot your environment (database dump, storage directory) to off-box storage.

### Post-Update Steps

The updater already runs migrations, composer install, and cache clears. Usually nothing else is needed. But if the admin still shows stale behavior:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

If you run a queue worker, restart it to pick up the new package classes:

```bash
php artisan queue:restart
```

### Rollback

If an update leaves you broken and you need to roll back:

1. **Restore the file backup:**
   ```bash
   cp -r storage/app/tallcms-backups/files/<timestamp>/. ./
   ```
2. **Restore the database backup:**
   ```bash
   # MySQL / PostgreSQL
   mysql -u <user> -p <dbname> < storage/app/tallcms-backups/db/<timestamp>.sql
   # SQLite
   cp storage/app/tallcms-backups/db/<timestamp>.sqlite database/database.sqlite
   ```
3. **Reinstall the previous composer lock:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. **Clear caches** as in "Post-Update Steps".

> Backups are kept indefinitely in `storage/app/tallcms-backups/`. Rotate them out of that directory (or to cold storage) if disk pressure becomes an issue.

---

## Plugin Mode

Plugin mode uses standard Composer workflow — no built-in updater, no signed bundle.

```bash
composer update tallcms/cms
php artisan migrate
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

Restart queue workers if you run them:

```bash
php artisan queue:restart
```

### Pinning Versions

To update only to a specific version (e.g., stay on the `4.x` line):

```bash
composer require tallcms/cms:^4.0.8
```

To preview the update without applying:

```bash
composer update tallcms/cms --dry-run
```

---

## Updating Plugins

The core CMS and its plugins (Multisite, Redirect Manager, Registration, etc.) version independently. After updating core, check plugin compatibility:

```bash
php artisan tallcms:plugin-list
```

If a plugin's minimum `tallcms` requirement is higher than your current version, update core first. If a plugin needs updating:

```bash
# Standalone: re-upload the plugin zip via Admin > Plugins
# Plugin mode: composer update <vendor>/<plugin-package>
```

---

## Troubleshooting

### "Another update is in progress"

The updater creates a lock file at `storage/app/tallcms-update.lock`. If a previous run crashed, this file may linger. Verify nothing is actually running, then:

```bash
rm storage/app/tallcms-update.lock
```

### "Signature verification failed"

The downloaded archive's signature didn't match the embedded public key. Causes:
- Network MITM or proxy stripping the signature file.
- Release was re-tagged (signature regenerated) but your cache has the old payload.

Run the update again. If it fails twice, report the issue at https://github.com/tallcms/tallcms/issues with the full log.

### "sodium extension not available"

The updater requires the `sodium` PHP extension for Ed25519 signature verification. On most modern PHP builds it's compiled in. To verify:

```bash
php -m | grep sodium
```

If missing, enable it in `php.ini`:

```
extension=sodium
```

Then restart PHP-FPM / your web server.

### Files in `tallcms-backups/quarantine/`

Every update moves locally modified core files here rather than overwriting them. If you see files you didn't intentionally modify, the previous update may have been applied to a dirty working copy. Review them; either re-apply your edits upstream or delete them.

### "Database backup not available: mysqldump not available on this server"

The updater shells out to `mysqldump` (MySQL/MariaDB) or `pg_dump` (PostgreSQL) for the database backup step. If the binary isn't in the web server's PATH, the update aborts with this error.

**Two ways to resolve:**

#### 1. Make the binary available

Find the existing binary (it often ships with your MySQL client but isn't symlinked):

```bash
# Laravel Herd — MySQL lives in Herd's Resources bundle
ls /Applications/Herd.app/Contents/Resources/mysql/bin/mysqldump

# DBngin
ls /Applications/DBngin.app/Contents/Resources/mysql-*/bin/mysqldump

# Homebrew
which mysqldump || ls $(brew --prefix mysql-client)/bin/mysqldump
```

Then either symlink to `/usr/local/bin/mysqldump` or add the directory to your shell's PATH in `~/.zshrc`. If PHP-FPM is caching the old environment, restart it (`herd restart` or the equivalent).

#### 2. Use `--skip-db-backup`

When it's safe:
- **Local/dev installs** — you can drop and re-seed the database.
- **You've already backed up externally** — e.g., via a managed provider's snapshot, a nightly cron, or `mysqldump` run by hand before the update.
- **CI pipelines** — each run uses an ephemeral database.

When it's not safe:
- **Production installs with no other backup strategy.** The updater's DB backup is the only rollback lifeline if a migration breaks your schema. If you skip it, you'd better have external backups.

```bash
php artisan tallcms:update --skip-db-backup
```

The file backup (`storage/app/tallcms-backups/files/<timestamp>/`) still runs — only the database backup is skipped.

#### Verify before trusting the skip

Before running with `--skip-db-backup` in production, take a manual dump:

```bash
# MySQL
mysqldump -u <user> -p <dbname> > backup-pre-update.sql

# PostgreSQL
pg_dump -U <user> <dbname> > backup-pre-update.sql
```

Then proceed. Keep the dump until the update is confirmed working.

### Update appears stuck

Check the update state:

```bash
php artisan tinker --execute="dump(json_decode(file_get_contents(storage_path('app/tallcms-update-state.json')), true));"
```

This shows the current step and timestamps. If a step has been `in_progress` for more than ~10 minutes with no output, the process probably died. Clear the lock (see above) and retry.

### "Composer install failed"

The updater runs `composer install --no-dev --optimize-autoloader` after applying files. If composer can't find a binary, set the path:

```bash
export TALLCMS_COMPOSER_BIN=/usr/local/bin/composer
```

Or pass it via `.env`:

```
TALLCMS_COMPOSER_BIN=/usr/local/bin/composer
```

---

## Version Compatibility

TallCMS follows [Semantic Versioning](https://semver.org/):

- **Patch (4.0.x)** — bug fixes only, always safe to apply.
- **Minor (4.x.0)** — new features, backwards compatible, safe to apply.
- **Major (x.0.0)** — may include breaking changes. Read the release notes and migration guide before updating.

Release notes live at https://github.com/tallcms/tallcms/releases.

---

## Next Steps

- [Architecture Reference](ref-architecture) — understand what the updater is modifying.
- [Plugin Licensing](ref-plugin-licensing) — keep paid plugins licensed across updates.
