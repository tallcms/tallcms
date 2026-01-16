@php
    $fields = $config['fields'] ?? [];
    $submitButtonText = $config['submit_button_text'] ?? 'Send Message';
    $buttonStyle = $config['button_style'] ?? 'btn-primary';
    $formId = 'contact-form-preview-' . uniqid();
    $sectionPadding = ($config['first_section'] ?? false) ? 'pb-16' : ($config['padding'] ?? 'py-16');
@endphp

<section class="contact-form-preview-block {{ $sectionPadding }} {{ $config['background'] ?? 'bg-base-100' }}">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($config['title'] ?? false)
            <h2 class="text-3xl font-bold text-base-content mb-4">
                {{ $config['title'] }}
            </h2>
        @endif

        @if($config['description'] ?? false)
            <p class="text-lg text-base-content/70 mb-8">
                {{ $config['description'] }}
            </p>
        @endif

        <div class="flex flex-col gap-4">
            @foreach($fields as $field)
                <x-tallcms::form.dynamic-field :field="$field" :form-id="$formId" :preview="true" />
            @endforeach

            <div class="pt-2">
                <span class="btn {{ $buttonStyle }}">
                    {{ $submitButtonText }}
                </span>
            </div>
        </div>
    </div>
</section>
