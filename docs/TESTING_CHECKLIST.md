# TallCMS Testing Checklist

> **Pre-release checklist** for validating both standalone and plugin modes.

## Automated Tests

Run these before manual testing:

```bash
# Package tests (from package directory)
cd packages/tallcms/cms
./vendor/bin/phpunit

# Standalone tests (from root)
php artisan test

# Quick validation
./vendor/bin/phpunit --filter=RouteRegistration
./vendor/bin/phpunit --filter=ConfigSchema
./vendor/bin/phpunit --filter=FilamentSmoke
```

---

## Manual Testing Checklist

### Both Modes

Complete these tests in **both** standalone and plugin mode.

#### Admin Panel Access
- [ ] Admin panel loads at `/admin` without errors
- [ ] Login works correctly
- [ ] Dark mode toggle works

#### CMS Navigation
- [ ] CMS navigation group visible with all resources:
  - [ ] Pages
  - [ ] Posts
  - [ ] Categories
  - [ ] Media
  - [ ] Menus
  - [ ] Contact Submissions

#### Pages Resource
- [ ] List pages loads without errors
- [ ] Create new page - form loads
- [ ] Block editor displays all 17 blocks in picker
- [ ] Add Hero block - renders in preview
- [ ] Add Content block - renders in preview
- [ ] Add Contact Form block - renders in preview
- [ ] Save page successfully
- [ ] Edit existing page
- [ ] **Preview button works** (opens /preview/page/{id})
- [ ] Revision History panel displays
- [ ] Create revision/snapshot works

#### Posts Resource
- [ ] List posts loads without errors
- [ ] Create new post with categories
- [ ] Block editor works
- [ ] **Preview button works** (opens /preview/post/{id})
- [ ] Revision History displays

#### Categories Resource
- [ ] List categories works
- [ ] Create/edit/delete category

#### Media Resource
- [ ] List media works
- [ ] Upload new media file
- [ ] Edit media metadata

#### Menus Resource
- [ ] List menus works
- [ ] Create menu with items
- [ ] Nested menu items work

#### Contact Submissions
- [ ] List submissions works
- [ ] View submission details

#### Settings
- [ ] Site Settings page loads
- [ ] Can update settings
- [ ] Settings persist after save

#### Plugin Manager
- [ ] Page loads without errors
- [ ] **"Available Plugins" section visible** with download links
- [ ] Download button present for catalog plugins
- [ ] Purchase License button present
- [ ] Learn More button present
- [ ] Upload Plugin button visible (if enabled)

#### Theme Manager
- [ ] Page loads without errors
- [ ] Theme list displays (if themes exist)
- [ ] Upload Theme button visible (if enabled)

---

### Standalone Mode Only

Test using the main TallCMS repository (standalone installation).

```bash
cd /path/to/tallcms
php artisan serve
```

#### Mode Detection
- [ ] `.tallcms-standalone` file exists
- [ ] `config('tallcms.mode')` returns 'standalone' or null (auto-detect)

#### Standalone Features
- [ ] System Updates page visible (Settings > System Updates)
- [ ] Frontend routes work at root (`/`, `/{slug}`)
- [ ] Themes directory exists and is scanned
- [ ] Plugins directory exists and is scanned

#### Routes
- [ ] `php artisan route:list --name=tallcms` shows all routes
- [ ] No duplicate route warnings

---

### Plugin Mode Only

Test using a separate Laravel project with TallCMS installed as a plugin.

```bash
cd /path/to/your-laravel-app
php artisan serve
```

#### Mode Detection
- [ ] No `.tallcms-standalone` file
- [ ] `config('tallcms.mode')` returns 'plugin' or null (auto-detect)

#### Plugin Mode Behavior
- [ ] System Updates page NOT visible
- [ ] Frontend routes respect `routes_prefix` config
- [ ] Essential routes work:
  - [ ] `/preview/page/{id}` - requires auth
  - [ ] `/preview/post/{id}` - requires auth
  - [ ] `/preview/share/{token}` - public
  - [ ] `/api/tallcms/contact` - POST works

#### View Namespacing
- [ ] All views load from package (`tallcms::`)
- [ ] No "View not found" errors

#### Config
- [ ] Package config merged correctly
- [ ] Published config overrides work
- [ ] All catalog entries have `download_url`

---

## Quick Regression Checks

After any code change, verify these critical paths:

### 5-Minute Smoke Test

1. **Standalone**:
   - Start server, login to admin
   - Open Pages, click "Create"
   - Add any block, save
   - Click Preview button → should open preview page

2. **Plugin Mode**:
   - Start server, login to admin
   - Open Pages, click "Create"
   - Add any block, save
   - Click Preview button → should open preview page
   - Open Plugin Manager → should show Available Plugins with Download buttons

### Console Check

```bash
# Check for errors in logs
tail -50 storage/logs/laravel.log | grep -i error

# Verify routes
php artisan route:list --name=tallcms.preview
```

---

## CI/CD Integration

### GitHub Actions Workflow

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  package-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: |
          cd packages/tallcms/cms
          composer install
      - name: Run package tests
        run: |
          cd packages/tallcms/cms
          ./vendor/bin/phpunit

  standalone-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test
```

---

## Common Issues & Solutions

| Symptom | Likely Cause | Solution |
|---------|--------------|----------|
| "Route [tallcms.preview.page] not defined" | Duplicate route definitions | Check routes/web.php for duplicate URIs |
| Download button missing | Config missing `download_url` | Add to `tallcms.plugins.catalog` |
| "View [xyz] not found" | Missing `tallcms::` prefix | Update view path in Block class |
| Blade component not found | Missing alias registration | Check TallCmsServiceProvider |
| Preview not working | Routes not loaded | Check `preview_routes_enabled` config |
| Styles broken in dark mode | Missing DaisyUI in admin theme | Add DaisyUI plugin to theme.css |

---

## Test Data Setup

### Quick Seed for Testing

```bash
# Create test user
php artisan make:user

# Or use tinker
php artisan tinker
>>> \App\Models\User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password')])
```

### Sample Content

Create at least:
- 1 Page with multiple blocks
- 1 Post with categories
- 1 Menu with nested items
- Upload 1 media file

---

## Sign-Off

Before release, both modes must pass:

- [ ] All automated tests pass
- [ ] Manual checklist complete for standalone
- [ ] Manual checklist complete for plugin mode
- [ ] No errors in laravel.log
- [ ] Documentation updated

**Tested by**: ________________
**Date**: ________________
**Version**: ________________
