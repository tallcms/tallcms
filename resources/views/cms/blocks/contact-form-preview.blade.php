@php
    $fields = $config['fields'] ?? [];
    $submitButtonText = $config['submit_button_text'] ?? 'Send Message';
    $formId = 'contact-form-preview-' . uniqid();
@endphp

<section class="py-12 sm:py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        @if($config['title'] ?? false)
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                {{ $config['title'] }}
            </h2>
        @endif

        @if($config['description'] ?? false)
            <p class="text-lg text-gray-600 mb-8">
                {{ $config['description'] }}
            </p>
        @endif

        <div class="flex flex-col gap-4">
            @foreach($fields as $field)
                <x-form.dynamic-field :field="$field" :form-id="$formId" :preview="true" />
            @endforeach

            <div class="pt-2">
                <span class="inline-block px-6 py-2.5 rounded-lg font-medium text-sm bg-primary-600 text-white">
                    {{ $submitButtonText }}
                </span>
            </div>
        </div>
    </div>
</section>
