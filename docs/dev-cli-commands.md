---
title: "CLI Commands Reference"
slug: "cli-commands"
audience: "developer"
category: "reference"
order: 10
prerequisites:
  - "installation"
---

# CLI Commands Reference

> **What you'll learn:** All available artisan commands for managing TallCMS, plugins, themes, and content.

TallCMS provides a comprehensive set of CLI commands for installation, maintenance, and development workflows.

---

## TallCMS Core Commands

### tallcms:install

Install TallCMS with migrations, roles, and permissions.

```bash
php artisan tallcms:install
```

| Option | Description |
|--------|-------------|
| `--skip-checks` | Skip prerequisite checks |
| `--skip-migrations` | Skip running migrations |
| `--skip-setup` | Skip roles and permissions setup |
| `--force` | Force installation even if already installed |

**Example:**

```bash
# Fresh install
php artisan tallcms:install

# Reinstall without running migrations
php artisan tallcms:install --force --skip-migrations
```

---

### tallcms:setup

Setup TallCMS with initial roles, permissions, and admin user.

```bash
php artisan tallcms:setup
```

| Option | Description |
|--------|-------------|
| `--force` | Force setup even if already configured |
| `--name=NAME` | Admin full name |
| `--email=EMAIL` | Admin email address |
| `--password=PASSWORD` | Admin password (min 8 chars) |

**Example:**

```bash
# Interactive setup
php artisan tallcms:setup

# Non-interactive setup (CI/CD)
php artisan tallcms:setup --name="Admin" --email="admin@example.com" --password="secret123"
```

---

### tallcms:update

Update TallCMS to the latest version.

```bash
php artisan tallcms:update
```

| Option | Description |
|--------|-------------|
| `--target=VERSION` | Specific version to update to (default: latest) |
| `--force` | Skip confirmation prompts |
| `--skip-backup` | Skip file and database backups |
| `--skip-db-backup` | Skip database backup only |
| `--dry-run` | Show what would happen without making changes |

**Example:**

```bash
# Update to latest
php artisan tallcms:update

# Update to specific version
php artisan tallcms:update --target=2.12.0

# Preview changes without applying
php artisan tallcms:update --dry-run
```

---

### tallcms:version

Display TallCMS version information.

```bash
php artisan tallcms:version
```

| Option | Description |
|--------|-------------|
| `--check` | Check for available updates |

**Example:**

```bash
# Show current version
php artisan tallcms:version

# Check for updates
php artisan tallcms:version --check
```

---

### tallcms:search-index

Rebuild the search_content column for all CMS content.

```bash
php artisan tallcms:search-index
```

| Option | Description |
|--------|-------------|
| `--model=MODEL` | Only index specific model (`page` or `post`) |

**Example:**

```bash
# Rebuild all content
php artisan tallcms:search-index

# Rebuild posts only
php artisan tallcms:search-index --model=post
```

---

### tallcms:backfill-author-slugs

Generate slugs for existing users who have authored posts.

```bash
php artisan tallcms:backfill-author-slugs
```

| Option | Description |
|--------|-------------|
| `--dry-run` | Show what would be updated without making changes |
| `--force` | Skip confirmation prompt |

**Example:**

```bash
# Preview changes
php artisan tallcms:backfill-author-slugs --dry-run

# Apply changes
php artisan tallcms:backfill-author-slugs --force
```

---

## Plugin Commands

### plugin:list

List all installed plugins.

```bash
php artisan plugin:list
```

| Option | Description |
|--------|-------------|
| `--detailed` | Show detailed information |
| `--tag=TAG` | Filter by tag |
| `--vendor=VENDOR` | Filter by vendor |

**Example:**

```bash
# List all plugins
php artisan plugin:list

# Show detailed info
php artisan plugin:list --detailed

# Filter by vendor
php artisan plugin:list --vendor=acme
```

---

### plugin:install

Install a plugin from a ZIP file.

```bash
php artisan plugin:install <path>
```

| Argument | Description |
|----------|-------------|
| `path` | Path to the plugin ZIP file |

| Option | Description |
|--------|-------------|
| `--no-migrate` | Skip running migrations |

**Example:**

```bash
# Install plugin
php artisan plugin:install ~/Downloads/acme-gallery.zip

# Install without migrations
php artisan plugin:install ~/Downloads/acme-gallery.zip --no-migrate
```

---

### plugin:uninstall

Uninstall a plugin.

```bash
php artisan plugin:uninstall <plugin>
```

| Argument | Description |
|----------|-------------|
| `plugin` | Plugin slug (vendor/slug) |

| Option | Description |
|--------|-------------|
| `--force` | Skip confirmation |

**Example:**

```bash
# Uninstall with confirmation
php artisan plugin:uninstall acme/gallery

# Force uninstall
php artisan plugin:uninstall acme/gallery --force
```

---

### plugin:migrate

Run or rollback migrations for plugins.

```bash
php artisan plugin:migrate [plugin]
```

| Argument | Description |
|----------|-------------|
| `plugin` | Plugin slug (vendor/slug) or omit to migrate all |

| Option | Description |
|--------|-------------|
| `--rollback` | Rollback migrations instead of running them |
| `--status` | Show migration status |

**Example:**

```bash
# Migrate all plugins
php artisan plugin:migrate

# Migrate specific plugin
php artisan plugin:migrate acme/gallery

# Check migration status
php artisan plugin:migrate acme/gallery --status

# Rollback migrations
php artisan plugin:migrate acme/gallery --rollback
```

---

### plugin:cleanup-backups

Clean up old plugin backups.

```bash
php artisan plugin:cleanup-backups [plugin]
```

| Argument | Description |
|----------|-------------|
| `plugin` | Plugin slug (vendor/slug) or omit to clean all |

| Option | Description |
|--------|-------------|
| `--keep=N` | Number of backups to keep per plugin (default: 3) |
| `--force` | Skip confirmation |

**Example:**

```bash
# Clean all backups (keep 3)
php artisan plugin:cleanup-backups

# Keep only 1 backup per plugin
php artisan plugin:cleanup-backups --keep=1 --force
```

---

### make:plugin

Create a new plugin scaffold.

```bash
php artisan make:plugin <name>
```

| Argument | Description |
|----------|-------------|
| `name` | Plugin name (e.g., "Pro Blocks") |

| Option | Description |
|--------|-------------|
| `--vendor=VENDOR` | Vendor name (defaults to "tallcms") |
| `--description=DESC` | Plugin description |
| `--author=AUTHOR` | Plugin author name |
| `--with-migration` | Include example migration |
| `--with-filament` | Include Filament plugin integration |
| `--with-routes` | Include route files |

**Example:**

```bash
# Basic plugin
php artisan make:plugin "Image Gallery"

# Full-featured plugin
php artisan make:plugin "Pro Gallery" \
  --vendor=acme \
  --author="Acme Inc." \
  --with-migration \
  --with-filament \
  --with-routes
```

---

## Theme Commands

### theme:list

List all available TallCMS themes.

```bash
php artisan theme:list
```

| Option | Description |
|--------|-------------|
| `--detailed` | Show detailed theme information |

**Example:**

```bash
# List themes
php artisan theme:list

# Show details
php artisan theme:list --detailed
```

---

### theme:activate

Activate a TallCMS theme.

```bash
php artisan theme:activate <slug>
```

| Argument | Description |
|----------|-------------|
| `slug` | The theme slug to activate |

| Option | Description |
|--------|-------------|
| `--force` | Force activation even if theme is not installed |

**Example:**

```bash
php artisan theme:activate corporate
```

---

### theme:build

Build TallCMS theme assets.

```bash
php artisan theme:build [slug]
```

| Argument | Description |
|----------|-------------|
| `slug` | Theme slug (optional, builds active theme if not specified) |

| Option | Description |
|--------|-------------|
| `--force` | Force rebuild even if assets exist |

**Example:**

```bash
# Build active theme
php artisan theme:build

# Build specific theme
php artisan theme:build corporate --force
```

---

### theme:install

Install a theme (publish assets and build).

```bash
php artisan theme:install <slug>
```

| Argument | Description |
|----------|-------------|
| `slug` | The theme slug to install |

**Example:**

```bash
php artisan theme:install corporate
```

---

### theme:cache:clear

Clear the theme discovery cache.

```bash
php artisan theme:cache:clear
```

---

### make:theme

Create a new TallCMS theme with daisyUI integration.

```bash
php artisan make:theme [name]
```

| Argument | Description |
|----------|-------------|
| `name` | The name of the theme |

| Option | Description |
|--------|-------------|
| `--preset=PRESET` | DaisyUI preset (light, dark, cupcake, etc.) or "custom" |
| `--prefers-dark=PRESET` | Dark mode preset (optional) |
| `--all-presets` | Include all daisyUI presets for theme-controller |
| `--custom` | Create a custom daisyUI theme with your own color palette |
| `--author=AUTHOR` | Theme author name |
| `--description=DESC` | Theme description |
| `--theme-version=VER` | Theme version |
| `--interactive` | Force interactive mode even with arguments |

**Example:**

```bash
# Interactive theme creation
php artisan make:theme

# Create with preset
php artisan make:theme "Corporate" --preset=corporate --author="Acme Inc."

# Create with dark mode
php artisan make:theme "Modern" --preset=light --prefers-dark=dark

# Create custom theme
php artisan make:theme "Brand" --custom --interactive
```

---

## Block Commands

### make:tallcms-block

Create a new TallCMS custom block with CSS custom properties integration.

```bash
php artisan make:tallcms-block <name>
```

| Argument | Description |
|----------|-------------|
| `name` | The name of the custom block |

**Example:**

```bash
php artisan make:tallcms-block Testimonials
php artisan make:tallcms-block ImageCarousel
```

This creates:
- `app/Filament/Blocks/{Name}Block.php` - Block class
- `resources/views/blocks/{name}.blade.php` - Block view

---

## Shield Commands (Permissions)

TallCMS integrates with Filament Shield for role-based access control.

### shield:generate

Generate permissions and/or policies for Filament entities.

```bash
php artisan shield:generate
```

| Option | Description |
|--------|-------------|
| `--all` | Generate for all entities |
| `--resource=NAME` | One or many resources (comma-separated) |
| `--page=NAME` | One or many pages (comma-separated) |
| `--widget=NAME` | One or many widgets (comma-separated) |
| `--panel=ID` | Panel ID to get components from |
| `--exclude` | Exclude the given entities |
| `--ignore-existing-policies` | Skip policies that already exist |

**Example:**

```bash
# Generate all permissions
php artisan shield:generate --all

# Generate for specific resources
php artisan shield:generate --resource=CmsPageResource,CmsPostResource

# Generate for admin panel
php artisan shield:generate --all --panel=admin
```

---

### shield:super-admin

Assign the super admin role to a user.

```bash
php artisan shield:super-admin
```

| Option | Description |
|--------|-------------|
| `--user=ID` | ID of user to be made super admin |
| `--panel=ID` | Panel ID to get configuration from |
| `--tenant=ID` | Team/Tenant ID to assign role to user |

**Example:**

```bash
# Interactive
php artisan shield:super-admin

# Assign to specific user
php artisan shield:super-admin --user=1
```

---

## Maintenance Commands

### Clear All Caches

```bash
php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear
```

### Rebuild Assets

```bash
php artisan filament:assets && php artisan livewire:publish --assets
```

### Full Reset (Development)

```bash
php artisan migrate:fresh --seed && php artisan tallcms:setup --force
```

---

## Common Pitfalls

**"Command not found"**
Run `composer dump-autoload` and `php artisan package:discover`.

**"Permission denied"**
Check file permissions on `storage/` and `bootstrap/cache/`.

**"Plugin migrations failed"**
Run `php artisan plugin:migrate --status` to check migration state.

**"Theme not loading"**
Clear theme cache with `php artisan theme:cache:clear`.

---

## Next Steps

- [Plugin development](plugins)
- [Theme development](themes)
- [Block development](blocks)
