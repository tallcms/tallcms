---
title: "Block Animations"
slug: "block-animations"
audience: "site-owner"
category: "blocks"
order: 50
---

# Block Animations

> Scroll-triggered entrance animations that bring your content to life.

---

## Overview

Block animations play once when content scrolls into view, creating engaging page experiences. Animations are configurable per block and respect accessibility settings.

---

## Accessing Animation Settings

1. Open any supported block in the page editor
2. Expand the **Animation** section (collapsed by default)
3. Select an **Entrance Animation** type
4. Choose an **Animation Speed**

---

## Animation Types

### Core

| Type | Description |
|------|-------------|
| **None** | No animation (default) |
| **Fade In** | Content fades from invisible to visible |
| **Fade In Up** | Content fades in while sliding upward |

### Pro

| Type | Description |
|------|-------------|
| **Fade In Down** | Content fades in while sliding downward |
| **Fade In Left** | Content fades in while sliding from the left |
| **Fade In Right** | Content fades in while sliding from the right |
| **Zoom In** | Content fades in while scaling up |
| **Zoom In Up** | Content zooms in with upward movement |

---

## Animation Speed

### Core

| Speed | Duration | Best For |
|-------|----------|----------|
| **Normal** | 0.7s | Most content |
| **Relaxed** | 1s | Hero sections, featured content |
| **Dramatic** | 1.5s | Landing pages, key messages |

### Pro

| Speed | Duration | Best For |
|-------|----------|----------|
| **Snappy** | 0.3s | Quick interactions |
| **Quick** | 0.5s | Subtle entrances |

> **Note:** Blocks saved with "Quick (0.5s)" before upgrading to Pro will continue to work, even without a Pro license.

---

## Stagger Effect (Pro)

For blocks with multiple items (grids, cards), enable **Stagger Items** to animate each item sequentially instead of all at once.

### Settings

| Field | Description |
|-------|-------------|
| **Stagger Items** | Toggle sequential animation on/off |
| **Stagger Delay** | Time between each item's animation |

### Delay Options

| Option | Effect |
|--------|--------|
| **None (0ms)** | Items animate together |
| **Short (100ms)** | Subtle cascade effect |
| **Medium (200ms)** | Noticeable sequence |
| **Long (300ms)** | Dramatic reveal |

---

## Supported Blocks

| Block | Stagger Support |
|-------|-----------------|
| Features | Yes |
| Testimonials | Yes |
| Pricing | Yes |
| Stats | Yes |
| Team | Yes |
| Call to Action | No |

---

## Accessibility

Animations automatically respect the **Reduce Motion** setting in your visitor's operating system. When enabled:

- All animations are disabled
- Content appears immediately
- No motion effects play

This ensures your site remains accessible to users sensitive to motion.

---

## Tips

**Choose subtle for professional sites**
Use **Fade In** with **Normal** speed for corporate or professional content.

**Use dramatic for landing pages**
Try **Fade In Up** with **Dramatic** speed to create impact on key sections.

**Stagger for visual interest**
Enable stagger on feature grids to guide the eye through your content.

**Don't overdo it**
Apply animations to key sections, not every block. Too many animations can feel overwhelming.

---

## Common Pitfalls

**"Animation isn't playing"**
Check that an animation type is selected (not "None"). The block must scroll into view to trigger—animations don't play on page load if already visible.

**"Animation seems too subtle"**
Try a longer speed like **Relaxed** or **Dramatic**, or use movement-based animations like **Fade In Up** instead of simple **Fade In**.

**"Items animate all at once"**
Enable **Stagger Items** in the Animation section (Pro feature).

---

## Next Steps

- [Content Blocks Overview](blocks)
- [Features Block](block-features)
- [Pricing Block](block-pricing)
