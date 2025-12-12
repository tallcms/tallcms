<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizerService
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * Get or create the HTML Purifier instance
     */
    private static function getPurifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            
            // Allow common HTML elements for content blocks
            $config->set('HTML.Allowed', 
                'p,br,strong,em,b,i,u,span,h1,h2,h3,h4,h5,h6,ul,ol,li,' .
                'blockquote,a[href],img[src|alt|width|height],div[class],' .
                'table,thead,tbody,tr,td,th,hr'
            );
            
            // Allow common attributes
            $config->set('HTML.AllowedAttributes', 
                'href,src,alt,width,height,class,title'
            );
            
            // Only allow http/https for links and images
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
            ]);
            
            // Disable external entities (prevent XXE attacks)
            $config->set('Core.RemoveInvalidImg', true);
            
            // Create cache directory if it doesn't exist
            $cacheDir = storage_path('app/htmlpurifier');
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cacheDir);
            
            self::$purifier = new HTMLPurifier($config);
        }
        
        return self::$purifier;
    }

    /**
     * Sanitize HTML content to prevent XSS attacks
     */
    public static function sanitize(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        return self::getPurifier()->purify($html);
    }

    /**
     * Clean HTML content specifically for TipTap rich editor output
     */
    public static function sanitizeTipTapContent(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        // TipTap generates specific structures we want to preserve
        $config = HTMLPurifier_Config::createDefault();
        
        // Allow TipTap-specific elements and attributes
        $config->set('HTML.Allowed', 
            'p,br,strong,em,b,i,u,s,span,h1,h2,h3,h4,h5,h6,ul,ol,li,' .
            'blockquote,a[href|target],img[src|alt|width|height],' .
            'div[class|data-type],table,thead,tbody,tr,td,th,hr,' .
            'code,pre'
        );
        
        // Allow data attributes for TipTap functionality
        $config->set('HTML.AllowedAttributes', 
            'href,target,src,alt,width,height,class,data-type,title'
        );
        
        // Only allow http/https for links and images
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
        ]);
        
        $config->set('Core.RemoveInvalidImg', true);
        
        $cacheDir = storage_path('app/htmlpurifier');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}