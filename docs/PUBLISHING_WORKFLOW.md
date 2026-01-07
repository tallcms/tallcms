# Publishing Workflow & Revision System

This document covers the content publishing workflow, revision history, and preview system for TallCMS Pages and Posts.

## Table of Contents

- [Publishing Workflow](#publishing-workflow)
- [Revision History](#revision-history)
- [Preview System](#preview-system)
- [Permissions](#permissions)
- [Configuration](#configuration)

---

## Publishing Workflow

### Content Status Lifecycle

Content (Pages and Posts) follows a defined status lifecycle:

```
┌─────────┐     Submit for      ┌─────────────────┐     Approve      ┌───────────┐
│  Draft  │ ──────────────────► │ Pending Review  │ ───────────────► │ Published │
└─────────┘       Review        └─────────────────┘                   └───────────┘
     ▲                                  │
     │                                  │ Reject
     │                                  ▼
     │                           ┌──────────┐
     └─────────────────────────  │ Rejected │
              (Edit & Resubmit)  └──────────┘
```

### Status Definitions

| Status | Description |
|--------|-------------|
| `draft` | Initial state. Content is being authored and is not visible publicly. |
| `pending` | Submitted for review. Awaiting editor/admin approval. |
| `published` | Approved and visible to the public (respects `published_at` date). |
| `rejected` | Returned to author with feedback. Can be edited and resubmitted. |

### Workflow Actions

#### Submit for Review
- **Available when**: Status is `draft` or `rejected`
- **Permission**: `SubmitForReview:CmsPost` / `SubmitForReview:CmsPage`
- **Effect**: Changes status to `pending`, records `submitted_at` and `submitted_by`
- **Notification**: Sends notification to users with Approve permission

#### Approve & Publish
- **Available when**: Status is `pending`
- **Permission**: `Approve:CmsPost` / `Approve:CmsPage`
- **Effect**: Changes status to `published`, records `approved_at` and `approved_by`
- **Notification**: Sends notification to the content author
- **Scheduling**: If `published_at` is in the future, content is scheduled

#### Reject
- **Available when**: Status is `pending`
- **Permission**: `Approve:CmsPost` / `Approve:CmsPage`
- **Effect**: Changes status to `rejected`, stores `rejection_reason`
- **Notification**: Sends notification to the content author with rejection reason

### Scheduled Publishing

Content can be scheduled for future publication:

1. Set `published_at` to a future date/time
2. Submit for review and get approval
3. Content remains invisible until `published_at` is reached
4. A scheduled task publishes content when the time arrives

```php
// Check if content is scheduled
$post->isScheduled(); // true if published_at is in the future

// Scope for scheduled content
CmsPost::scheduled()->get();
```

### Database Fields

| Field | Type | Description |
|-------|------|-------------|
| `status` | enum | Current workflow status |
| `published_at` | datetime | When content becomes/became visible |
| `submitted_at` | datetime | When submitted for review |
| `submitted_by` | foreignId | User who submitted |
| `approved_at` | datetime | When approved |
| `approved_by` | foreignId | User who approved |
| `rejection_reason` | text | Feedback when rejected |

---

## Revision History

### Overview

TallCMS automatically tracks content revisions to provide:
- Complete audit trail of all changes
- Ability to compare any two versions
- One-click restore to previous versions
- Manual "pinned" snapshots for milestones

### How Revisions Work

#### Automatic Snapshots

Revisions are created automatically on every save when content changes:

1. **Pre-update snapshot**: Captures state BEFORE the change (audit trail)
2. **Post-update snapshot**: Captures state AFTER the change (current state)

The system uses SHA-256 content hashing to detect actual changes. If the hash is identical to the latest revision, no new revision is created.

#### Manual Snapshots (Pinned)

Editors can create manual snapshots via the "Save Snapshot" header action:

- Saves current form changes
- Creates a pinned milestone in the revision timeline
- Optional notes to describe the milestone
- Displayed with a "Pinned" badge in the timeline

### Revision Timeline UI

The revision history panel shows:

```
┌─────────────────────────────────────────────┐
│ ● Revision #5              [Current]        │
│   2 minutes ago by John Doe                 │
├─────────────────────────────────────────────┤
│ ● Revision #4              [Pinned]         │
│   1 hour ago by Jane Smith                  │
│   — Ready for review                        │
│   [vs Current] [vs Prev] [Restore]          │
├─────────────────────────────────────────────┤
│ ● Revision #3                               │
│   3 hours ago by John Doe                   │
│   [vs Current] [vs Prev] [Restore]          │
└─────────────────────────────────────────────┘
```

### Comparing Revisions

Click any two revisions to compare them:

- Side-by-side diff for text fields (title, excerpt, meta)
- Visual content comparison for block content
- Older version shown in red, newer in green
- "Restore" button to revert to the older version

### Restoring Revisions

When restoring a revision:

1. Content fields are restored (title, excerpt, content, meta, featured image)
2. **Status is NOT changed** (workflow state preserved)
3. A new revision is created capturing the restored state
4. User is redirected to the edit page with restored content

### Tracked Fields

The following fields are tracked for revisions:

- `title`
- `excerpt`
- `content` (block editor content)
- `meta_title`
- `meta_description`
- `featured_image`

### Revision Pruning

To prevent unbounded growth, revisions are automatically pruned:

| Type | Default Limit | Config Key |
|------|---------------|------------|
| Automatic | 100 | `CMS_REVISION_LIMIT` |
| Manual (Pinned) | 50 | `CMS_REVISION_MANUAL_LIMIT` |

Oldest revisions are pruned first. Manual and automatic revisions have separate limits.

### Database Schema

**Table: `tallcms_revisions`**

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `revisionable_type` | string | Model class (CmsPost/CmsPage) |
| `revisionable_id` | bigint | Record ID |
| `user_id` | bigint | User who created revision |
| `title` | string | Snapshot of title |
| `excerpt` | text | Snapshot of excerpt |
| `content` | longtext | Snapshot of content (JSON) |
| `meta_title` | string | Snapshot of meta title |
| `meta_description` | text | Snapshot of meta description |
| `featured_image` | string | Snapshot of featured image |
| `additional_data` | json | Extra data (extensible) |
| `revision_number` | integer | Sequential number per record |
| `notes` | text | Optional revision notes |
| `is_manual` | boolean | True for pinned snapshots |
| `content_hash` | string(64) | SHA-256 hash for change detection |
| `created_at` | datetime | When revision was created |
| `updated_at` | datetime | When revision was updated |

---

## Preview System

### Live Preview

Content can be previewed before publishing:

- **Preview button**: Opens content in a new tab using the theme's layout
- **Works for all statuses**: Draft, pending, and published content
- **Shows current form state**: Preview reflects unsaved changes after save

### Shareable Preview Links

Generate time-limited preview links for stakeholders:

1. Click "Share Preview Link" in the Preview dropdown
2. Select expiry time (1 hour, 24 hours, 7 days, 30 days)
3. Copy the generated link
4. Anyone with the link can view the content without logging in

#### Preview Token Features

- Cryptographically secure tokens
- Configurable expiration
- Tokens can be revoked at any time
- Multiple active tokens per content item

### Revoking Preview Links

To invalidate all active preview links:

1. Click "Revoke All Preview Links" in the Preview dropdown
2. Confirm the action
3. All existing preview tokens are invalidated

### Database Schema

**Table: `tallcms_preview_tokens`**

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `previewable_type` | string | Model class |
| `previewable_id` | bigint | Record ID |
| `token` | string(64) | Secure random token |
| `expires_at` | datetime | When token expires |
| `created_by` | bigint | User who created token |
| `created_at` | datetime | When created |

---

## Permissions

### Shield Integration

All workflow actions are protected by BezhanSalleh/FilamentShield permissions:

#### Post Permissions

| Permission | Description |
|------------|-------------|
| `SubmitForReview:CmsPost` | Can submit posts for review |
| `Approve:CmsPost` | Can approve/reject posts |
| `ViewRevisions:CmsPost` | Can view revision history |
| `RestoreRevision:CmsPost` | Can restore previous revisions |
| `GeneratePreviewLink:CmsPost` | Can create shareable preview links |

#### Page Permissions

| Permission | Description |
|------------|-------------|
| `SubmitForReview:CmsPage` | Can submit pages for review |
| `Approve:CmsPage` | Can approve/reject pages |
| `ViewRevisions:CmsPage` | Can view revision history |
| `RestoreRevision:CmsPage` | Can restore previous revisions |
| `GeneratePreviewLink:CmsPage` | Can create shareable preview links |

### Suggested Role Configuration

| Role | Permissions |
|------|-------------|
| Author | SubmitForReview |
| Editor | SubmitForReview, Approve, ViewRevisions, RestoreRevision, GeneratePreviewLink |
| Admin | All permissions |

---

## Configuration

### Environment Variables

```env
# Revision limits
CMS_REVISION_LIMIT=100              # Max automatic revisions per content
CMS_REVISION_MANUAL_LIMIT=50        # Max manual snapshots per content

# Notifications
CMS_NOTIFICATION_CHANNELS=mail,database  # Comma-separated: mail, database
```

### Config File

**`config/tallcms.php`**

```php
'publishing' => [
    // Maximum automatic revisions per content item (null = unlimited)
    'revision_limit' => env('CMS_REVISION_LIMIT', 100),

    // Maximum manual snapshots per content item (null = unlimited)
    'revision_manual_limit' => env('CMS_REVISION_MANUAL_LIMIT', 50),

    // Notification channels for workflow events
    'notification_channels' => explode(',', env('CMS_NOTIFICATION_CHANNELS', 'mail,database')),

    // Default preview token expiry in hours
    'default_preview_expiry_hours' => 24,
],
```

---

## Key Files

### Models & Traits

| File | Purpose |
|------|---------|
| `app/Models/CmsPost.php` | Post model with HasRevisions, HasPreviewTokens traits |
| `app/Models/CmsPage.php` | Page model with HasRevisions, HasPreviewTokens traits |
| `app/Models/CmsRevision.php` | Revision model |
| `app/Models/CmsPreviewToken.php` | Preview token model |
| `app/Models/Concerns/HasRevisions.php` | Revision tracking trait |
| `app/Models/Concerns/HasPreviewTokens.php` | Preview token trait |
| `app/Models/Concerns/HasPublishingWorkflow.php` | Workflow status trait |

### Services

| File | Purpose |
|------|---------|
| `app/Services/PublishingWorkflowService.php` | Workflow state transitions & notifications |
| `app/Services/ContentDiffService.php` | Content comparison for revision diffs |

### Filament Resources

| File | Purpose |
|------|---------|
| `app/Filament/Resources/CmsPosts/Pages/EditCmsPost.php` | Post edit page with workflow actions |
| `app/Filament/Resources/CmsPages/Pages/EditCmsPage.php` | Page edit page with workflow actions |

### Livewire Components

| File | Purpose |
|------|---------|
| `app/Livewire/RevisionHistory.php` | Revision timeline & diff component |
| `resources/views/livewire/revision-history.blade.php` | Revision history template |

### Notifications

| File | Purpose |
|------|---------|
| `app/Notifications/ContentSubmittedForReview.php` | Sent to approvers |
| `app/Notifications/ContentApproved.php` | Sent to author on approval |
| `app/Notifications/ContentRejected.php` | Sent to author on rejection |

---

## Usage Examples

### Programmatic Workflow Control

```php
use App\Services\PublishingWorkflowService;

$workflow = app(PublishingWorkflowService::class);

// Submit for review
$workflow->submitForReview($post);

// Approve
$workflow->approve($post);

// Reject with reason
$workflow->reject($post, 'Please add more details to the introduction.');
```

### Creating Manual Snapshots

```php
// Create a pinned snapshot with notes
$post->createManualSnapshot('Ready for client review');
```

### Working with Revisions

```php
// Get all revisions
$revisions = $post->revisions;

// Get latest revision
$latest = $post->getLatestRevision();

// Get specific revision
$revision = $post->getRevision(5);

// Restore a revision
$post->restoreRevision($revision);

// Get only manual snapshots
$pinned = $post->revisions()->manual()->get();

// Get only automatic snapshots
$auto = $post->revisions()->automatic()->get();
```

### Preview Tokens

```php
use Carbon\Carbon;

// Create preview token (expires in 24 hours)
$token = $post->createPreviewToken(Carbon::now()->addHours(24));
$url = $token->getPreviewUrl();

// Check for active tokens
if ($post->hasActivePreviewTokens()) {
    $count = $post->getActivePreviewTokenCount();
}

// Revoke all tokens
$post->revokeAllPreviewTokens();
```
