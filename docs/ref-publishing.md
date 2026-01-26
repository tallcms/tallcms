---
title: "Publishing Workflow"
slug: "publishing"
audience: "all"
category: "reference"
order: 20
---

# Publishing Workflow & Revision System

Complete reference for content publishing workflow, revision history, and preview system.

---

## Publishing Workflow

### Content Status Lifecycle

```
Draft → Pending Review → Published
   ↑          │
   └──────────┘ (Reject)
```

### Status Definitions

| Status | Description |
|--------|-------------|
| `draft` | Initial state. Not visible publicly. |
| `pending` | Submitted for review. Awaiting approval. |
| `published` | Approved and visible (respects publish date). |

> **Note**: Rejection returns content to `draft` with a `rejection_reason`.

### Workflow Actions

#### Submit for Review
- **Available when**: Status is `draft`
- **Permission**: `SubmitForReview:CmsPost` / `SubmitForReview:CmsPage`
- **Effect**: Changes to `pending`, notifies approvers

#### Approve & Publish
- **Available when**: Status is `pending`
- **Permission**: `Approve:CmsPost` / `Approve:CmsPage`
- **Effect**: Changes to `published`, notifies author

#### Reject
- **Available when**: Status is `pending`
- **Permission**: `Approve:CmsPost` / `Approve:CmsPage`
- **Effect**: Returns to `draft` with rejection reason

### Scheduled Publishing

```php
// Check if scheduled
$post->isScheduled(); // true if published AND published_at > now()

// Scopes
CmsPost::scheduled()->get();  // Approved but future
CmsPost::published()->get();  // Currently visible
```

### Database Fields

| Field | Type | Description |
|-------|------|-------------|
| `status` | enum | Workflow status |
| `published_at` | datetime | When visible |
| `submitted_at` | datetime | When submitted |
| `submitted_by` | foreignId | Submitter |
| `approved_at` | datetime | When approved |
| `approved_by` | foreignId | Approver |
| `rejection_reason` | text | Feedback when rejected |

---

## Revision History

### How Revisions Work

Two revisions are created on every save:

1. **Pre-update**: State BEFORE the change
2. **Post-update**: State AFTER the change

Uses SHA-256 content hashing to detect changes.

### Manual Snapshots (Pinned)

Create milestones via "Save Snapshot" action:
- Saves current form changes
- Creates pinned milestone
- Optional notes
- Displayed with "Pinned" badge

### Revision Timeline

```
● Revision #5              [Current]
  2 minutes ago by John Doe

● Revision #4              [Pinned]
  1 hour ago by Jane Smith
  — Ready for review
  [vs Current] [vs Prev] [Restore]

● Revision #3
  3 hours ago by John Doe
  [vs Current] [vs Prev] [Restore]
```

### Comparing Revisions

- Side-by-side diff for text fields
- Visual content comparison for blocks
- Older in red, newer in green
- Restore button available

### Restoring Revisions

When restoring:
1. Content fields are restored
2. Status is NOT changed
3. New revision captures restored state
4. Redirects to edit page

### Tracked Fields

- `title`
- `excerpt`
- `content`
- `meta_title`
- `meta_description`
- `featured_image`

### Revision Pruning

| Type | Default Limit | Config Key |
|------|---------------|------------|
| Automatic | 100 | `CMS_REVISION_LIMIT` |
| Manual | 50 | `CMS_REVISION_MANUAL_LIMIT` |

---

## Preview System

### Live Preview

- Opens content in new tab with theme layout
- Works for all statuses
- Reflects current form state after save

### Shareable Preview Links

1. Click "Share Preview Link"
2. Select expiry (1 hour, 24 hours, 7 days, 30 days)
3. Copy generated link
4. Anyone with link can view without login

### Token Features

- Cryptographically secure
- Configurable expiration
- Can be revoked anytime
- Multiple tokens per content item

### Revoking Links

Click "Revoke All Preview Links" to invalidate all active tokens.

---

## Permissions

### Shield Integration

#### Post Permissions

| Permission | Description |
|------------|-------------|
| `SubmitForReview:CmsPost` | Submit for review |
| `Approve:CmsPost` | Approve/reject |
| `ViewRevisions:CmsPost` | View history |
| `RestoreRevision:CmsPost` | Restore versions |
| `GeneratePreviewLink:CmsPost` | Create preview links |

#### Page Permissions

Same pattern with `CmsPage` suffix.

### Suggested Roles

| Role | Permissions |
|------|-------------|
| Author | SubmitForReview |
| Editor | All except admin-only |
| Admin | All permissions |

---

## Configuration

### Environment Variables

```env
CMS_REVISION_LIMIT=100
CMS_REVISION_MANUAL_LIMIT=50
CMS_NOTIFICATION_CHANNELS=mail,database
```

### Config File

```php
'publishing' => [
    'revision_limit' => env('CMS_REVISION_LIMIT', 100),
    'revision_manual_limit' => env('CMS_REVISION_MANUAL_LIMIT', 50),
    'notification_channels' => ['mail', 'database'],
    'default_preview_expiry_hours' => 24,
],
```

---

## Usage Examples

### Programmatic Workflow

```php
use App\Services\PublishingWorkflowService;

$workflow = app(PublishingWorkflowService::class);

$workflow->submitForReview($post);
$workflow->approve($post);
$workflow->reject($post, 'Please add more details.');
```

### Manual Snapshots

```php
$post->createManualSnapshot('Ready for client review');
```

### Working with Revisions

```php
$revisions = $post->revisions;
$latest = $post->getLatestRevision();
$revision = $post->getRevision(5);
$post->restoreRevision($revision);

// Filtered queries
$pinned = $post->revisions()->manual()->get();
$auto = $post->revisions()->automatic()->get();
```

### Preview Tokens

```php
use Carbon\Carbon;

$token = $post->createPreviewToken(Carbon::now()->addHours(24));
$url = $token->getPreviewUrl();

if ($post->hasActivePreviewTokens()) {
    $count = $post->getActivePreviewTokenCount();
}

$post->revokeAllPreviewTokens();
```

---

## Next Steps

- [Page settings](page-settings)
- [Managing content](pages-posts)
- [Architecture](architecture)
