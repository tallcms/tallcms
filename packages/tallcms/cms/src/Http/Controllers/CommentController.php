<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use TallCms\Cms\Models\CmsComment;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Notifications\NewCommentNotification;
use TallCms\Cms\Support\NotificationDispatcher;

class CommentController extends Controller
{
    public function submit(Request $request): JsonResponse
    {
        // Check if comments are enabled
        if (! config('tallcms.comments.enabled', true)) {
            return response()->json(['message' => 'Comments are disabled.'], 404);
        }

        // Rate limiting
        $key = 'comments:'.$request->ip();
        $maxAttempts = config('tallcms.comments.rate_limit', 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many comments. Please try again later.',
            ], 429);
        }

        // Honeypot check - silently reject spam
        if (! empty($request->input('_honeypot'))) {
            return response()->json(['success' => true]);
        }

        // Guest comments gate
        if (! auth()->check() && ! config('tallcms.comments.guest_comments', true)) {
            return response()->json([
                'message' => 'You must be logged in to comment.',
            ], 403);
        }

        // Build validation rules
        $maxLength = config('tallcms.comments.max_length', 5000);
        $rules = [
            'post_id' => ['required', 'integer'],
            'parent_id' => ['nullable', 'integer'],
            'content' => ['required', 'string', "max:{$maxLength}"],
        ];

        if (! auth()->check()) {
            $rules['author_name'] = ['required', 'string', 'max:255'];
            $rules['author_email'] = ['required', 'email', 'max:255'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        // Verify post exists and is published
        $post = CmsPost::published()->find($request->input('post_id'));
        if (! $post) {
            return response()->json([
                'errors' => ['post_id' => ['The selected post is not available for comments.']],
            ], 422);
        }

        // Validate parent_id if provided
        if ($request->filled('parent_id')) {
            $parent = CmsComment::where('id', $request->input('parent_id'))
                ->where('status', 'approved')
                ->whereNull('deleted_at')
                ->where('post_id', $request->input('post_id'))
                ->first();

            if (! $parent) {
                return response()->json([
                    'errors' => ['parent_id' => ['The parent comment is not valid.']],
                ], 422);
            }

            // Enforce nesting depth
            $maxDepth = max(1, (int) config('tallcms.comments.max_depth', 2));
            $depth = $this->getCommentDepth($parent);

            if ($depth >= $maxDepth - 1) {
                return response()->json([
                    'errors' => ['parent_id' => ['Maximum reply depth reached.']],
                ], 422);
            }
        }

        // Sanitize content - plain text only
        $content = trim(strip_tags($request->input('content')));

        if (blank($content)) {
            return response()->json([
                'errors' => ['content' => ['The comment content cannot be empty.']],
            ], 422);
        }

        // Hit rate limiter after validation
        $decay = config('tallcms.comments.rate_limit_decay', 600);
        RateLimiter::hit($key, $decay);

        // Create comment
        $autoApprove = config('tallcms.comments.moderation') === 'auto';
        $comment = CmsComment::create([
            'post_id' => $post->id,
            'parent_id' => $request->input('parent_id'),
            'user_id' => auth()->id(),
            'author_name' => auth()->check() ? null : $request->input('author_name'),
            'author_email' => auth()->check() ? null : $request->input('author_email'),
            'content' => $content,
            'status' => $autoApprove ? 'approved' : 'pending',
            'approved_at' => $autoApprove ? now() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($autoApprove) {
            return response()->json([
                'success' => true,
                'message' => 'Your comment has been posted.',
            ]);
        }

        // Notify approvers
        $this->notifyApprovers($comment);

        return response()->json([
            'success' => true,
            'message' => 'Your comment has been submitted and is awaiting moderation.',
        ]);
    }

    protected function getCommentDepth(CmsComment $comment): int
    {
        $depth = 0;
        $current = $comment;

        while ($current->parent_id !== null) {
            $depth++;
            // Use withTrashed to avoid null when an ancestor is soft-deleted
            $current = CmsComment::withTrashed()->find($current->parent_id);
            if ($current === null) {
                break;
            }
        }

        return $depth;
    }

    protected function notifyApprovers(CmsComment $comment): void
    {
        try {
            $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);
            $approvers = $userModel::permission('Approve:CmsComment')->get();

            foreach ($approvers as $approver) {
                // Skip self-notification
                if ($comment->user_id && $approver->id === $comment->user_id) {
                    continue;
                }

                NotificationDispatcher::send($approver, new NewCommentNotification($comment));
            }
        } catch (\Throwable $e) {
            Log::warning('TallCMS: Could not resolve comment approvers for notification.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
