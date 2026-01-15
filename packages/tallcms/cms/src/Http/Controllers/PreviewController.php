<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\CmsPreviewToken;
use TallCms\Cms\Services\CustomBlockDiscoveryService;
use TallCms\Cms\Services\MergeTagService;

class PreviewController extends Controller
{
    public function page(Request $request, CmsPage $page): View
    {
        $device = $request->get('device', 'desktop');

        // Render the page content exactly like CmsPageRenderer does
        $renderedContent = $this->renderContent($page->content, $page);

        return view('tallcms::preview.page', [
            'page' => $page,
            'renderedContent' => $renderedContent,
            'device' => $device,
            'type' => 'page',
        ]);
    }

    public function post(Request $request, CmsPost $post): View
    {
        $device = $request->get('device', 'desktop');

        // Render the post content exactly like CmsPageRenderer does
        $renderedContent = $this->renderContent($post->content, $post);

        return view('tallcms::preview.post', [
            'post' => $post,
            'renderedContent' => $renderedContent,
            'device' => $device,
            'type' => 'post',
        ]);
    }

    /**
     * Token-based preview for sharing with external users (no auth required)
     */
    public function tokenPreview(Request $request, string $token): View|Response
    {
        $previewToken = CmsPreviewToken::findByToken($token);

        // Token not found
        if (! $previewToken) {
            abort(404, 'Preview link not found.');
        }

        // Atomically check validity and consume a view
        // This prevents race conditions where multiple requests could view past the limit
        if (! $previewToken->consumeView()) {
            if ($previewToken->isExpired()) {
                abort(410, 'This preview link has expired.');
            }

            if ($previewToken->isOverViewLimit()) {
                abort(410, 'This preview link has reached its maximum number of views.');
            }

            abort(410, 'This preview link is no longer valid.');
        }

        // Get the content being previewed
        $content = $previewToken->tokenable;

        if (! $content) {
            abort(404, 'Content not found.');
        }

        $device = $request->get('device', 'desktop');
        $renderedContent = $this->renderContent($content->content, $content);

        // Determine the type and view based on the content model
        if ($content instanceof CmsPost) {
            return view('tallcms::preview.post', [
                'post' => $content,
                'renderedContent' => $renderedContent,
                'device' => $device,
                'type' => 'post',
                'isTokenPreview' => true,
                'tokenInfo' => [
                    'remaining_views' => $previewToken->getRemainingViews(),
                    'expires_at' => $previewToken->expires_at,
                    'time_until_expiry' => $previewToken->getTimeUntilExpiry(),
                ],
            ]);
        }

        if ($content instanceof CmsPage) {
            return view('tallcms::preview.page', [
                'page' => $content,
                'renderedContent' => $renderedContent,
                'device' => $device,
                'type' => 'page',
                'isTokenPreview' => true,
                'tokenInfo' => [
                    'remaining_views' => $previewToken->getRemainingViews(),
                    'expires_at' => $previewToken->expires_at,
                    'time_until_expiry' => $previewToken->getTimeUntilExpiry(),
                ],
            ]);
        }

        abort(404, 'Content type not supported.');
    }

    private function renderContent($content, $model): string
    {
        // Content is now stored as JSON string directly (not cast to array)
        // RichContentRenderer expects a JSON string, so pass it directly
        // Handle edge cases: null, array (legacy), or string
        if ($content === null) {
            return '';
        }

        if (is_array($content)) {
            $content = json_encode($content);
        }

        // At this point content should be a string (JSON or HTML)
        // Render rich content with auto-discovered custom blocks (same as CmsPageRenderer)
        // Use toUnsafeHtml() to preserve Alpine.js attributes (x-data, x-model, etc.)
        $renderedContent = RichContentRenderer::make($content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();

        // Process merge tags in the rendered content
        return MergeTagService::replaceTags($renderedContent, $model);
    }
}
