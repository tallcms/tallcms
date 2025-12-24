@props([
    'field',
    'formId' => 'form',
    'preview' => false,
])

@php
    $fieldId = $formId . '-' . $field['name'];
    $fieldName = $field['name'];
    $fieldType = $field['type'] ?? 'text';
    $fieldLabel = $field['label'] ?? $field['name'];
    $required = $field['required'] ?? false;
    $options = $field['options'] ?? [];

    // Build common attributes
    $baseClass = 'border-gray-300' . ($preview ? ' bg-gray-50' : '');
@endphp

<div>
    <x-form.label
        :for="$fieldId"
        :required="$required"
        :style="$preview ? '' : 'color: var(--block-text-color, #374151);'"
    >
        {{ $fieldLabel }}
    </x-form.label>

    @if($fieldType === 'textarea')
        @if($preview)
            <x-form.textarea
                :id="$fieldId"
                disabled
                placeholder="Text area input..."
                :class="$baseClass"
            />
        @else
            <x-form.textarea
                :id="$fieldId"
                :required="$required"
                x-model="formData.{{ $fieldName }}"
                x-bind:class="errors.{{ $fieldName }} ? 'border-red-500' : 'border-gray-300'"
            />
        @endif
    @elseif($fieldType === 'select')
        @if($preview)
            <x-form.select
                :id="$fieldId"
                :options="$options"
                disabled
                :class="$baseClass"
            />
        @else
            <x-form.select
                :id="$fieldId"
                :options="$options"
                :required="$required"
                x-model="formData.{{ $fieldName }}"
                x-bind:class="errors.{{ $fieldName }} ? 'border-red-500' : 'border-gray-300'"
            />
        @endif
    @else
        @if($preview)
            <x-form.input
                :type="$fieldType"
                :id="$fieldId"
                disabled
                :placeholder="$fieldType === 'email' ? 'email@example.com' : ($fieldType === 'tel' ? '(555) 123-4567' : 'Enter ' . strtolower($fieldLabel) . '...')"
                :class="$baseClass"
            />
        @else
            <x-form.input
                :type="$fieldType"
                :id="$fieldId"
                :required="$required"
                x-model="formData.{{ $fieldName }}"
                x-bind:class="errors.{{ $fieldName }} ? 'border-red-500' : 'border-gray-300'"
            />
        @endif
    @endif

    @if(!$preview)
        <x-form.error :field="$fieldName" />
    @endif
</div>
