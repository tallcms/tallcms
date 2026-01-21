<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Mail\ContactFormAdminNotification;
use App\Mail\ContactFormAutoReply;
use App\Models\TallcmsContactSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;
use TallCms\Cms\Models\SiteSetting;

class ContactForm extends Component
{
    public array $config = [];

    public array $formData = [];

    public string $honeypot = '';

    public bool $submitted = false;

    public function mount(array $config = []): void
    {
        $this->config = $config;

        // Initialize formData with empty values for each field
        foreach ($this->config['fields'] ?? [] as $field) {
            $this->formData[$field['name']] = '';
        }
    }

    public function submit(): void
    {
        // Rate limiting check
        $key = 'contact-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('form', 'Too many submissions. Please try again later.');

            return;
        }

        // Honeypot check - silently reject spam
        if (! empty($this->honeypot)) {
            $this->submitted = true;

            return;
        }

        // Build validation rules from config
        $rules = [];
        $attributes = [];

        foreach ($this->config['fields'] ?? [] as $field) {
            $fieldRules = ($field['required'] ?? false) ? ['required'] : ['nullable'];

            match ($field['type']) {
                'email' => $fieldRules = array_merge($fieldRules, ['email', 'max:255']),
                'tel' => $fieldRules[] = 'max:50',
                'select' => $fieldRules[] = Rule::in($field['options'] ?? []),
                'textarea' => $fieldRules[] = 'max:5000',
                default => $fieldRules[] = 'max:255',
            };

            $rules['formData.'.$field['name']] = $fieldRules;
            $attributes['formData.'.$field['name']] = strtolower($field['label'] ?? $field['name']);
        }

        $this->validate($rules, [], $attributes);

        // Hit the rate limiter after successful validation
        RateLimiter::hit($key, 600);

        // Build form_data array with field metadata
        $formDataWithMeta = [];
        $submitterName = null;
        $submitterEmail = null;
        $hasAnyValue = false;

        foreach ($this->config['fields'] ?? [] as $field) {
            $value = $this->formData[$field['name']] ?? '';
            $trimmedValue = is_string($value) ? trim($value) : $value;

            $formDataWithMeta[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'value' => $trimmedValue,
            ];

            // Track if any field has a non-empty value
            if (! empty($trimmedValue)) {
                $hasAnyValue = true;
            }

            // Extract name from field explicitly named 'name'
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
            $this->addError('form', 'Please fill in at least one field.');

            return;
        }

        // Store submission
        $submission = TallcmsContactSubmission::create([
            'name' => $submitterName,
            'email' => $submitterEmail,
            'form_data' => $formDataWithMeta,
            'page_url' => request()->header('Referer', request()->url()),
        ]);

        // Queue admin notification (Site Settings with config fallback)
        $adminEmail = SiteSetting::get('contact_email') ?: config('tallcms.contact_email');
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new ContactFormAdminNotification($submission));
        }

        // Queue auto-reply if submitter provided email
        if ($submitterEmail) {
            Mail::to($submitterEmail)->queue(new ContactFormAutoReply($submission));
        }

        // Reset form and show success
        $this->submitted = true;
        foreach ($this->config['fields'] ?? [] as $field) {
            $this->formData[$field['name']] = '';
        }
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
