<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;

class MergeTagService
{
    /**
     * Replace merge tags in content
     */
    public static function replaceTags(string $content, $record = null): string
    {
        $tags = self::getTagValues($record);
        
        foreach ($tags as $tag => $value) {
            $content = str_replace('{{' . $tag . '}}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Get all available merge tag values
     */
    protected static function getTagValues($record = null): array
    {
        $tags = [
            // Site-wide tags
            'site_name' => config('app.name', 'TallCMS'),
            'site_url' => config('app.url', url('/')),
            'current_year' => date('Y'),
            'current_date' => now()->format('F j, Y'),
            
            // Contact & Company info (these would typically come from settings)
            'contact_email' => 'info@' . parse_url(config('app.url'), PHP_URL_HOST),
            'contact_phone' => '+1 (555) 123-4567',
            'company_name' => config('app.name', 'TallCMS'),
            'company_address' => '123 Main St, City, State 12345',
            
            // Social media (these would typically come from settings)
            'social_facebook' => 'https://facebook.com/yourcompany',
            'social_twitter' => 'https://twitter.com/yourcompany',
            'social_linkedin' => 'https://linkedin.com/company/yourcompany',
            'social_instagram' => 'https://instagram.com/yourcompany',
            'newsletter_signup' => '#newsletter',
        ];
        
        // Add record-specific tags if record is provided
        if ($record instanceof CmsPage) {
            $tags = array_merge($tags, self::getPageTags($record));
        } elseif ($record instanceof CmsPost) {
            $tags = array_merge($tags, self::getPostTags($record));
        }
        
        return $tags;
    }
    
    /**
     * Get page-specific merge tags
     */
    protected static function getPageTags(CmsPage $page): array
    {
        return [
            'page_title' => $page->title ?? '',
            'page_url' => url('/page/' . $page->slug),
            'page_author' => 'Admin', // Pages don't have authors, could be site admin
        ];
    }
    
    /**
     * Get post-specific merge tags
     */
    protected static function getPostTags(CmsPost $post): array
    {
        return [
            'post_title' => $post->title ?? '',
            'post_url' => url('/blog/' . $post->slug),
            'post_excerpt' => $post->excerpt ?? '',
            'post_author' => $post->author->name ?? 'Unknown Author',
            'post_author_email' => $post->author->email ?? '',
            'post_categories' => $post->categories->pluck('name')->implode(', ') ?: 'Uncategorized',
            'post_published_date' => $post->published_at ? $post->published_at->format('F j, Y') : 'Not Published',
            'post_reading_time' => $post->reading_time . ' min read',
            'related_posts' => '', // This could be implemented to show related posts HTML
        ];
    }
    
    /**
     * Get all available merge tags for documentation
     */
    public static function getAvailableTags($type = 'all'): array
    {
        $siteTags = [
            'site_name' => 'Site Name',
            'site_url' => 'Site URL',
            'current_year' => 'Current Year',
            'current_date' => 'Current Date',
            'contact_email' => 'Contact Email',
            'contact_phone' => 'Contact Phone',
            'company_name' => 'Company Name',
            'company_address' => 'Company Address',
            'social_facebook' => 'Facebook URL',
            'social_twitter' => 'Twitter URL',
            'social_linkedin' => 'LinkedIn URL',
            'social_instagram' => 'Instagram URL',
        ];
        
        $pageTags = [
            'page_title' => 'Page Title',
            'page_url' => 'Page URL',
            'page_author' => 'Page Author',
        ];
        
        $postTags = [
            'post_title' => 'Post Title',
            'post_url' => 'Post URL',
            'post_excerpt' => 'Post Excerpt',
            'post_author' => 'Post Author',
            'post_author_email' => 'Author Email',
            'post_categories' => 'Post Categories',
            'post_published_date' => 'Published Date',
            'post_reading_time' => 'Reading Time',
            'newsletter_signup' => 'Newsletter Signup URL',
            'related_posts' => 'Related Posts',
        ];
        
        switch ($type) {
            case 'pages':
                return array_merge($siteTags, $pageTags);
            case 'posts':
                return array_merge($siteTags, $postTags);
            default:
                return array_merge($siteTags, $pageTags, $postTags);
        }
    }
}