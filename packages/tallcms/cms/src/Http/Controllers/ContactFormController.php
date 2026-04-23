<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use TallCms\Cms\Mail\ContactFormAdminNotification;
use TallCms\Cms\Mail\ContactFormAutoReply;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\TallcmsContactSubmission;
use TallCms\Cms\Services\BlockLinkResolver;

class ContactFormController extends Controller
{
    /**
     * Schema version for signature validation - increment when config structure changes
     */
    private const SCHEMA_VERSION = 1;

    /**
     * Maximum allowed fields per form to prevent abuse
     */
    private const MAX_FIELDS = 20;

    /**
     * Maximum options per select field
     */
    private const MAX_SELECT_OPTIONS = 50;

    /**
     * Maximum length per select option
     */
    private const MAX_OPTION_LENGTH = 100;

    /**
     * Allowed field types (whitelist)
     */
    private const ALLOWED_TYPES = ['text', 'email', 'tel', 'textarea', 'select'];

    public function submit(Request $request): JsonResponse
    {
        // Rate limiting check
        $key = 'contact-form:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many submissions. Please try again in a few minutes.',
            ], 429);
        }

        // Honeypot check - silently reject spam
        if (! empty($request->input('_honeypot'))) {
            // Pretend success to confuse bots
            return response()->json(['success' => true]);
        }

        // Get and validate config
        $config = $request->input('_config', []);
        $signature = $request->input('_signature', '');
        $pageUrl = $request->input('_pageUrl', '');

        // Verify config signature to prevent tampering and replay attacks
        if (! $this->verifyConfigSignature($config, $signature, $pageUrl)) {
            // TEMP DEBUG: capture both signatures and the signed payload so we
            // can diff render-time vs verify-time when investigating reports.
            $verifyPayload = json_encode([
                'v' => self::SCHEMA_VERSION,
                'url' => $pageUrl,
                'config' => $config,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            Log::warning('Contact form submission with invalid signature', [
                'ip' => $request->ip(),
                'referer' => $request->header('Referer'),
                'claimed_page_url' => $pageUrl,
                'submitted_signature' => $signature,
                'computed_signature' => self::signConfig($config, $pageUrl),
                'signed_payload' => $verifyPayload,
                'config_keys_in_order' => array_keys($config),
            ]);

            return response()->json([
                'message' => 'Invalid form configuration. Please refresh the page and try again.',
            ], 400);
        }

        // Validate and sanitize the config structure
        $validationResult = $this->validateConfig($config);
        if ($validationResult !== true) {
            return response()->json([
                'message' => $validationResult,
            ], 400);
        }

        $fields = $config['fields'];

        // Build validation rules from sanitized config
        $rules = [];
        foreach ($fields as $field) {
            $fieldRules = ($field['required'] ?? false) ? ['required'] : ['nullable'];

            match ($field['type']) {
                'email' => $fieldRules = array_merge($fieldRules, ['email', 'max:255']),
                'tel' => $fieldRules[] = 'max:50',
                'select' => $fieldRules[] = Rule::in($field['options'] ?? []),
                'textarea' => $fieldRules[] = 'max:5000',
                default => $fieldRules[] = 'max:255',
            };

            $rules[$field['name']] = $fieldRules;
        }

        // Build validation attributes for friendly error messages
        $attributes = [];
        foreach ($fields as $field) {
            $attributes[$field['name']] = strtolower($field['label'] ?? $field['name']);
        }

        // Validate form data
        $validator = Validator::make($request->all(), $rules, [], $attributes);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        // Hit the rate limiter after successful validation
        RateLimiter::hit($key, 600); // 10 minute decay

        // Build form_data array with field metadata
        $formDataWithMeta = [];
        $submitterName = null;
        $submitterEmail = null;
        $hasAnyValue = false;

        foreach ($fields as $field) {
            $value = $request->input($field['name'], '');
            $trimmedValue = is_string($value) ? trim($value) : $value;

            $formDataWithMeta[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'value' => $value,
            ];

            // Track if any field has a non-empty value
            if (! empty($trimmedValue)) {
                $hasAnyValue = true;
            }

            // Extract name from field explicitly named 'name' (not label heuristics)
            if ($submitterName === null && $field['name'] === 'name' && ! empty($trimmedValue)) {
                $submitterName = $trimmedValue;
            }

            // Extract email from first email-type field with a value
            if ($submitterEmail === null && $field['type'] === 'email' && ! empty($trimmedValue)) {
                $submitterEmail = $trimmedValue;
            }
        }

        // Reject completely empty submissions
        if (! $hasAnyValue) {
            return response()->json([
                'message' => 'Please fill in at least one field.',
            ], 422);
        }

        // Store submission
        $submission = TallcmsContactSubmission::create([
            'name' => $submitterName,
            'email' => $submitterEmail,
            'form_data' => $formDataWithMeta,
            'page_url' => $request->header('Referer', $request->url()),
        ]);

        // Queue admin notification using the SaaS-friendly resolution chain.
        $adminEmail = static::resolveAdminRecipient();
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new ContactFormAdminNotification($submission));
        } else {
            Log::warning('Contact form submission received but no admin email could be resolved', [
                'submission_id' => $submission->id,
            ]);
        }

        // Queue auto-reply only if submitter provided a valid email.
        // Pass the per-block custom message so each contact form can carry
        // its own response copy (release-window language, compliance text,
        // listing-specific wording, etc.). Falls back to the default when
        // the block config didn't set one.
        if ($submitterEmail) {
            $customMessage = ! empty($config['auto_reply_message'])
                ? (string) $config['auto_reply_message']
                : null;

            Mail::to($submitterEmail)->queue(
                new ContactFormAutoReply($submission, $customMessage),
            );
        }

        // Resolve redirect URL if configured
        $response = ['success' => true];

        if (! empty($config['redirect_page_id'])) {
            $redirectUrl = BlockLinkResolver::resolveButtonUrl([
                'redirect_link_type' => 'page',
                'redirect_page_id' => $config['redirect_page_id'],
            ], 'redirect');

            if ($redirectUrl !== '#') {
                $response['redirect_url'] = $redirectUrl;
            }
        }

        return response()->json($response);
    }

    /**
     * Generate HMAC signature for config to prevent tampering
     *
     * Includes schema version for forward compatibility and page URL to prevent replay attacks
     */
    public static function signConfig(array $config, string $pageUrl = ''): string
    {
        $payload = json_encode([
            'v' => self::SCHEMA_VERSION,
            'url' => $pageUrl,
            'config' => $config,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /**
     * Verify the config signature matches
     */
    private function verifyConfigSignature(array $config, string $signature, string $pageUrl): bool
    {
        if (empty($signature)) {
            return false;
        }

        $expectedSignature = self::signConfig($config, $pageUrl);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate and sanitize the config structure
     *
     * @return true|string True if valid, error message if invalid
     */
    private function validateConfig(array $config): bool|string
    {
        // Check fields exist and is array
        if (! isset($config['fields']) || ! is_array($config['fields'])) {
            return 'Invalid form configuration: missing fields.';
        }

        $fields = $config['fields'];

        // Check field count limit
        if (count($fields) > self::MAX_FIELDS) {
            return 'Invalid form configuration: too many fields.';
        }

        if (count($fields) === 0) {
            return 'Invalid form configuration: no fields defined.';
        }

        // Track field names for uniqueness check
        $fieldNames = [];

        foreach ($fields as $index => $field) {
            // Validate field structure
            if (! is_array($field)) {
                return "Invalid form configuration: field at index {$index} is not valid.";
            }

            // Validate required field properties
            if (! isset($field['name']) || ! is_string($field['name'])) {
                return "Invalid form configuration: field at index {$index} missing name.";
            }

            if (! isset($field['type']) || ! is_string($field['type'])) {
                return "Invalid form configuration: field at index {$index} missing type.";
            }

            if (! isset($field['label']) || ! is_string($field['label'])) {
                return "Invalid form configuration: field at index {$index} missing label.";
            }

            $name = $field['name'];
            $type = $field['type'];

            // Validate field name format (alphanumeric only, matching editor's alphaNum rule)
            if (! preg_match('/^[a-zA-Z][a-zA-Z0-9]{0,49}$/', $name)) {
                return "Invalid form configuration: field name '{$name}' contains invalid characters.";
            }

            // Check for duplicate field names
            if (in_array($name, $fieldNames, true)) {
                return "Invalid form configuration: duplicate field name '{$name}'.";
            }
            $fieldNames[] = $name;

            // Validate field type against whitelist
            if (! in_array($type, self::ALLOWED_TYPES, true)) {
                return "Invalid form configuration: field type '{$type}' is not allowed.";
            }

            // Validate select options
            if ($type === 'select') {
                if (! isset($field['options']) || ! is_array($field['options'])) {
                    return "Invalid form configuration: select field '{$name}' missing options.";
                }

                if (count($field['options']) > self::MAX_SELECT_OPTIONS) {
                    return "Invalid form configuration: select field '{$name}' has too many options.";
                }

                // Validate each option is a non-empty trimmed string within length limit
                foreach ($field['options'] as $option) {
                    if (! is_string($option)) {
                        return "Invalid form configuration: select field '{$name}' has invalid options.";
                    }

                    $trimmed = trim($option);
                    if (strlen($trimmed) === 0 || strlen($trimmed) > self::MAX_OPTION_LENGTH) {
                        return "Invalid form configuration: select field '{$name}' has invalid option length.";
                    }
                }
            }

            // Validate label length
            if (strlen($field['label']) > 255) {
                return 'Invalid form configuration: field label too long.';
            }
        }

        return true;
    }

    /**
     * Resolve where a contact-form admin notification should go, for the
     * site that owns the current request.
     *
     * Chain, most-specific first:
     *   1. Site-specific override (the site_owner set contact_email in Site Settings).
     *   2. Site owner's user email (SaaS default: submissions route to the person
     *      who owns the site, even before they've configured anything).
     *   3. Global contact_email setting (the installation-wide fallback).
     *   4. null — caller should skip the send.
     *
     * The previous implementation used SiteSetting::get('contact_email'),
     * which only checks the override→global pair. On SaaS installs that had
     * a "dummy" global contact_email (e.g., noreply@the-install.test), every
     * cloned site inherited that dummy as its admin recipient — so the site
     * owner never saw their own form submissions. Inserting the site owner's
     * user email between the override and the global closes that gap.
     */
    public static function resolveAdminRecipient(): ?string
    {
        $siteId = null;

        if (app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    $siteId = (int) $resolver->id();
                }
            } catch (\Throwable) {
                // Resolver not functional — fall through
            }
        }

        // 1. Site-specific override
        if ($siteId && Schema::hasTable('tallcms_site_setting_overrides')) {
            try {
                $override = DB::table('tallcms_site_setting_overrides')
                    ->where('site_id', $siteId)
                    ->where('key', 'contact_email')
                    ->value('value');
                if ($override) {
                    return $override;
                }
            } catch (\Throwable) {
                // Ignore and fall through
            }
        }

        // 2. Site owner's user email (SaaS-friendly default)
        if ($siteId && Schema::hasTable('tallcms_sites') && Schema::hasTable('users')) {
            try {
                $ownerEmail = DB::table('tallcms_sites')
                    ->join('users', 'users.id', '=', 'tallcms_sites.user_id')
                    ->where('tallcms_sites.id', $siteId)
                    ->value('users.email');
                if ($ownerEmail) {
                    return $ownerEmail;
                }
            } catch (\Throwable) {
                // Ignore and fall through
            }
        }

        // 3. Global fallback
        $global = SiteSetting::getGlobal('contact_email');
        if ($global) {
            return $global;
        }

        return null;
    }
}
