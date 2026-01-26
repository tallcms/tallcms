---
title: "Testing Checklist"
slug: "testing-checklist"
audience: "developer"
category: "reference"
order: 99
hidden: true
---

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
- [ ] **"Available Plugins" section visible**
- [ ] Download button present for catalog plugins
- [ ] Upload Plugin button visible (if enabled)

#### Theme Manager
- [ ] Page loads without errors
- [ ] Theme list displays (if themes exist)
- [ ] Upload Theme button visible (if enabled)

---

### Standalone Mode Only

```bash
cd /path/to/tallcms
php artisan serve
```

#### Mode Detection
- [ ] `.tallcms-standalone` file exists
- [ ] `config('tallcms.mode')` returns 'standalone' or null

#### Standalone Features
- [ ] System Updates page visible (Settings > System Updates)
- [ ] Frontend routes work at root (`/`, `/{slug}`)
- [ ] Themes directory exists and is scanned
- [ ] Plugins directory exists and is scanned

---

### Plugin Mode Only

```bash
cd /path/to/your-laravel-app
php artisan serve
```

#### Mode Detection
- [ ] No `.tallcms-standalone` file
- [ ] `config('tallcms.mode')` returns 'plugin' or null

#### Plugin Mode Behavior
- [ ] System Updates page NOT visible
- [ ] Frontend routes respect `routes_prefix` config
- [ ] Essential routes work:
  - [ ] `/preview/page/{id}` - requires auth
  - [ ] `/preview/post/{id}` - requires auth
  - [ ] `/preview/share/{token}` - public
  - [ ] `/api/tallcms/contact` - POST works

---

## Quick Regression Checks

### 5-Minute Smoke Test

1. **Standalone**:
   - Start server, login to admin
   - Open Pages, click "Create"
   - Add any block, save
   - Click Preview button

2. **Plugin Mode**:
   - Start server, login to admin
   - Open Pages, click "Create"
   - Add any block, save
   - Click Preview button
   - Open Plugin Manager

### Console Check

```bash
tail -50 storage/logs/laravel.log | grep -i error
php artisan route:list --name=tallcms.preview
```

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
