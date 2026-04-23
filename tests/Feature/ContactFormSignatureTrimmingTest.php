<?php

namespace Tests\Feature;

use TallCms\Cms\Http\Controllers\ContactFormController;
use Tests\TestCase;

/**
 * Regression test for the TrimStrings-versus-signature mismatch.
 *
 * Laravel's TrimStrings middleware trims leading/trailing whitespace on every
 * string in the request body, including nested values inside `_config`. If
 * signConfig() hashes the raw (untrimmed) render-time config but the
 * controller receives a trimmed version after middleware runs, the HMAC never
 * matches and every submission fails with "Invalid form configuration."
 *
 * Fix: both render-time signing and verify-time signing trim strings before
 * hashing, so the two sides agree on the canonical form.
 */
class ContactFormSignatureTrimmingTest extends TestCase
{
    public function test_trailing_whitespace_produces_same_signature_as_trimmed_equivalent(): void
    {
        $untrimmed = [
            'auto_reply_message' => 'Thanks! We will be in touch. ',
            'title' => '  Get in touch  ',
            'fields' => [
                ['label' => 'Name ', 'type' => 'text'],
            ],
        ];
        $trimmed = [
            'auto_reply_message' => 'Thanks! We will be in touch.',
            'title' => 'Get in touch',
            'fields' => [
                ['label' => 'Name', 'type' => 'text'],
            ],
        ];

        $pageUrl = 'https://portal.test/contact';

        $this->assertSame(
            ContactFormController::signConfig($untrimmed, $pageUrl),
            ContactFormController::signConfig($trimmed, $pageUrl),
            'signConfig must produce the same HMAC whether strings carry leading/trailing '.
            'whitespace or not. TrimStrings middleware removes that whitespace from the '.
            'request body; if render-time signing does not match, submissions will be rejected.',
        );
    }

    public function test_unrelated_config_difference_still_produces_different_signature(): void
    {
        $a = ['auto_reply_message' => 'A'];
        $b = ['auto_reply_message' => 'B'];

        $this->assertNotSame(
            ContactFormController::signConfig($a, 'https://x/contact'),
            ContactFormController::signConfig($b, 'https://x/contact'),
        );
    }

    public function test_internal_whitespace_is_preserved_not_stripped(): void
    {
        $withInternalSpaces = ['message' => 'Line one\n\nLine two'];
        $collapsed = ['message' => 'Line oneLine two'];

        $this->assertNotSame(
            ContactFormController::signConfig($withInternalSpaces, 'https://x'),
            ContactFormController::signConfig($collapsed, 'https://x'),
            'Only leading/trailing whitespace should be trimmed — internal whitespace is '.
            'semantically significant and must not change the signature.',
        );
    }

    public function test_empty_string_matches_null_to_mirror_convert_empty_strings_to_null_middleware(): void
    {
        // Block defaults often carry empty strings ("description" => "") that
        // Laravel's ConvertEmptyStringsToNull middleware rewrites to null on
        // the request body. Sign-time normalization must match that behavior
        // so render-time and verify-time hashes agree.
        $withEmptyString = ['description' => '', 'title' => 'Form'];
        $withNull = ['description' => null, 'title' => 'Form'];

        $this->assertSame(
            ContactFormController::signConfig($withEmptyString, 'https://x'),
            ContactFormController::signConfig($withNull, 'https://x'),
        );
    }

    public function test_whitespace_only_string_matches_null(): void
    {
        // TrimStrings then ConvertEmptyStringsToNull composed: "   " -> "" -> null.
        $withWhitespace = ['description' => '   ', 'title' => 'Form'];
        $withNull = ['description' => null, 'title' => 'Form'];

        $this->assertSame(
            ContactFormController::signConfig($withWhitespace, 'https://x'),
            ContactFormController::signConfig($withNull, 'https://x'),
        );
    }
}
