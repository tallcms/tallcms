# TallCMS Documentation Style Guide

> **For Contributors** - Guidelines for writing TallCMS documentation.

This file is excluded from the documentation seeder and serves as an internal reference for documentation contributors.

---

## Voice & Tone

| Do | Don't |
|----|-------|
| "Click **Save**" | "You can click the Save button" |
| "Add a Hero block" | "A Hero block can be added" |
| "This guide shows you how to..." | "This document describes..." |
| "~5 minutes" | "This should take approximately 5 minutes" |
| Direct, active voice | Passive, wordy constructions |

### Examples

**Good:**
> Navigate to **Admin > Pages** and click **New Page**.

**Avoid:**
> You can navigate to the Admin section and then go to Pages where you will find a button that allows you to create a new page.

---

## Formatting Rules

### Headings

- **H1**: Title only (one per document)
- **H2**: Main sections
- **H3**: Subsections
- **H4**: Use sparingly for sub-subsections

### UI Elements

Bold for buttons, labels, and navigation paths:
- `**Save**`
- `**Admin > Pages > New Page**`
- `**Settings** tab`

### Code

Backticks for inline code:
- `php artisan migrate`
- `config('tallcms.version')`

Fenced blocks for multi-line:
```php
$page = CmsPage::find($id);
$page->title = 'New Title';
$page->save();
```

### Links

Use slugs, not filenames:
- Good: `[menus guide](menus)`
- Avoid: `[menus](MENUS.md)`

### Lists

- Numbered for sequential steps
- Bullets for options/features

---

## Terminology

| Use | Instead of |
|-----|------------|
| Admin panel | Dashboard, backend, CMS |
| Block | Widget, component, section |
| Page | Static page, content page |
| Post | Article, blog post, entry |
| Slug | URL path, permalink |
| Click | Press, hit, tap |
| Select | Choose, pick |

---

## Document Structure

Every document should have:

1. **Frontmatter** with all required fields
2. **"What you'll learn"** callout (for guides)
3. **Time estimate** (if task-based)
4. **Numbered steps** for procedures
5. **"Common Pitfalls"** section
6. **"Next Steps"** with 2-3 links

---

## Frontmatter Specification

```yaml
---
title: "Create Your First Page"
slug: "first-page"
audience: "site-owner"
category: "getting-started"
order: 20
time: 5
prerequisites:
  - "installation"
---
```

### Required Fields

| Field | Description |
|-------|-------------|
| `title` | Display title |
| `slug` | URL slug (canonical) |
| `audience` | `site-owner`, `developer`, or `all` |
| `category` | Category slug |
| `order` | Sort order (use gaps of 10) |

### Optional Fields

| Field | Description |
|-------|-------------|
| `time` | Estimated minutes |
| `prerequisites` | Array of slugs |
| `hidden` | If `true`, not seeded |

---

## Categories

| Slug | Name | Order |
|------|------|-------|
| `getting-started` | Getting Started | 1 |
| `site-management` | Site Management | 2 |
| `developers` | For Developers | 3 |
| `reference` | Reference | 4 |

---

## File Naming

- Lowercase with hyphens
- Prefix indicates audience:
  - `gs-` → Getting Started
  - `site-` → Site Management
  - `dev-` → Developers
  - `ref-` → Reference

---

## Templates

### Quick-Start Guide

```markdown
---
title: "[Action] Your First [Thing]"
slug: "first-thing"
audience: "site-owner"
category: "getting-started"
order: 1
time: 5
---

# [Action] Your First [Thing]

> **What you'll learn:** [One sentence outcome]

**Time:** ~X minutes

---

## 1. [First action verb]

[Instructions]

## 2. [Second action verb]

[Continue numbered steps]

---

## Common Pitfalls

**"[Symptom]"**
[One-line fix]

---

## Next Steps

- [Related quick-start](slug)
- [Deeper dive](slug)
```

### Developer Guide

```markdown
---
title: "[Feature] Development"
slug: "feature-development"
audience: "developer"
category: "developers"
order: 1
prerequisites:
  - "installation"
---

# [Feature] Development

> **What you'll learn:** How to build custom [features].

---

## Quick Start

[Minimal working example]

## Configuration

[Options and settings]

## API Reference

[Methods and parameters]

---

## Common Pitfalls

**"[Error message]"**
[Technical fix]

---

## Next Steps

- [Related dev guide](slug)
```

---

## Writing Tips

1. **Start with the outcome** - Tell users what they'll achieve
2. **Use numbered steps** - Make procedures scannable
3. **Show, don't tell** - Include code examples
4. **Anticipate problems** - Add "Common Pitfalls"
5. **Link forward** - Always include "Next Steps"

---

## Review Checklist

Before submitting documentation:

- [ ] Frontmatter is complete and valid
- [ ] Title uses active voice
- [ ] Steps are numbered and scannable
- [ ] Code examples are tested
- [ ] Links use slugs, not filenames
- [ ] Common Pitfalls section included
- [ ] Next Steps has 2-3 relevant links
- [ ] No spelling/grammar errors
