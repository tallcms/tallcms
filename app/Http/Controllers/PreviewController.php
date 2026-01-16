<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use TallCms\Cms\Http\Controllers\PreviewController as BasePreviewController;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\CmsPreviewToken;

/**
 * PreviewController - extends the package's controller for backwards compatibility.
 *
 * In standalone mode, this class overrides the view references to use
 * app-level views (preview.page, preview.post) for full customization.
 */
class PreviewController extends BasePreviewController
{
    /**
     * Preview a page (authenticated users only).
     *
     * Override to use app views for standalone customization.
     */
    public function page(Request $request, CmsPage $page): View
    {
        $device = $request->get('device', 'desktop');
        $renderedContent = $this->renderContentForView($page->content, $page);

        return view('preview.page', [
            'page' => $page,
            'renderedContent' => $renderedContent,
            'device' => $device,
            'type' => 'page',
        ]);
    }

    /**
     * Preview a post (authenticated users only).
     *
     * Override to use app views for standalone customization.
     */
    public function post(Request $request, CmsPost $post): View
    {
        $device = $request->get('device', 'desktop');
        $renderedContent = $this->renderContentForView($post->content, $post);

        return view('preview.post', [
            'post' => $post,
            'renderedContent' => $renderedContent,
            'device' => $device,
            'type' => 'post',
        ]);
    }

    /**
     * Token-based preview for sharing with external users (no auth required).
     *
     * Override to use app views for standalone customization.
     */
    public function tokenPreview(Request $request, string $token): View|Response
    {
        $previewToken = CmsPreviewToken::findByToken($token);

        if (! $previewToken) {
            abort(404, 'Preview link not found.');
        }

        if (! $previewToken->consumeView()) {
            if ($previewToken->isExpired()) {
                abort(410, 'This preview link has expired.');
            }

            if ($previewToken->isOverViewLimit()) {
                abort(410, 'This preview link has reached its maximum number of views.');
            }

            abort(410, 'This preview link is no longer valid.');
        }

        $content = $previewToken->tokenable;

        if (! $content) {
            abort(404, 'Content not found.');
        }

        $device = $request->get('device', 'desktop');
        $renderedContent = $this->renderContentForView($content->content, $content);

        $tokenInfo = [
            'remaining_views' => $previewToken->getRemainingViews(),
            'expires_at' => $previewToken->expires_at,
            'time_until_expiry' => $previewToken->getTimeUntilExpiry(),
        ];

        if ($content instanceof CmsPost) {
            return view('preview.post', [
                'post' => $content,
                'renderedContent' => $renderedContent,
                'device' => $device,
                'type' => 'post',
                'isTokenPreview' => true,
                'tokenInfo' => $tokenInfo,
            ]);
        }

        if ($content instanceof CmsPage) {
            return view('preview.page', [
                'page' => $content,
                'renderedContent' => $renderedContent,
                'device' => $device,
                'type' => 'page',
                'isTokenPreview' => true,
                'tokenInfo' => $tokenInfo,
            ]);
        }

        abort(404, 'Content type not supported.');
    }

    /**
     * Render content for view (wrapper around parent's private method).
     */
    private function renderContentForView($content, $model): string
    {
        if ($content === null) {
            return '';
        }

        if (is_array($content)) {
            $content = json_encode($content);
        }

        $renderedContent = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($content)
            ->customBlocks(\TallCms\Cms\Services\CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();

        return \TallCms\Cms\Services\MergeTagService::replaceTags($renderedContent, $model);
    }
}
