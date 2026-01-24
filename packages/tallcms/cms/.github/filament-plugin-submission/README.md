# Filament Plugin Directory Submission

This folder contains the files needed to submit TallCMS to the [Filament Plugin Directory](https://filamentphp.com/plugins).

## Submission Process

1. Fork the [filamentphp/filamentphp.com](https://github.com/filamentphp/filamentphp.com) repository
2. Copy the author profile and plugin files to the appropriate directories
3. Add screenshots to the repository
4. Submit a Pull Request

## Files to Copy

| Source File | Destination |
|-------------|-------------|
| `author-dan-aquino.md` | `content/authors/dan-aquino.md` |
| `plugin-dan-aquino-tallcms.md` | `content/plugins/dan-aquino-tallcms.md` |

## Screenshots Needed

Create screenshots for the plugin listing page. Recommended screenshots:

1. **Dashboard/Overview** - Show the CMS dashboard with widgets
2. **Page Editor** - Block-based content editing experience
3. **Posts List** - Post management with filters and actions
4. **Media Library** - Media management interface
5. **Menu Builder** - Drag-and-drop menu editing
6. **Site Settings** - Configuration panel
7. **Publishing Workflow** - Draft/Review/Published states
8. **Revision History** - Diff comparison view

### Screenshot Requirements

- **Format**: PNG or WebP
- **Size**: 1200x800px recommended (16:9 or similar aspect ratio)
- **Theme**: Include both light and dark mode if possible
- **Quality**: Clear, high-resolution, no personal data visible

Screenshots should be placed in the filamentphp.com repository, typically referenced in the plugin markdown file.

## Pre-Submission Checklist

- [ ] Package published on Packagist (tallcms/cms)
- [ ] GitHub repository is public (tallcms/cms)
- [ ] README.md is comprehensive
- [ ] LICENSE.md exists (MIT)
- [ ] CHANGELOG.md is up to date
- [ ] Documentation available at tallcms.com/docs
- [ ] Discord server set up (discord.gg/tallcms)
- [ ] Screenshots prepared
- [ ] Tested fresh installation via `composer require tallcms/cms`

## Version Compatibility

Currently supports:
- **Filament**: ^4.0 (Filament 5 support pending filament-shield update)
- **Laravel**: ^11.0 || ^12.0
- **PHP**: ^8.2

## Notes

- Plugin name: "TallCMS"
- Plugin slug: `dan-aquino-tallcms`
- Author: Dan Aquino (links to tallcms GitHub organization)
- Categories: `kit`, `panel-builder`, `form-builder`
