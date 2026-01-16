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
@endphp

<div class="form-control w-full">
    <label for="{{ $fieldId }}" class="label">
        <span class="label-text">
            {{ $fieldLabel }}
            @if($required)
                <span class="text-error">*</span>
            @endif
        </span>
    </label>

    @if($fieldType === 'textarea')
        @if($preview)
            <textarea
                id="{{ $fieldId }}"
                class="textarea textarea-bordered w-full bg-base-200"
                disabled
                placeholder="Text area input..."
            ></textarea>
        @else
            <textarea
                id="{{ $fieldId }}"
                class="textarea textarea-bordered w-full"
                x-model="formData.{{ $fieldName }}"
                x-bind:class="errors.{{ $fieldName }} ? 'textarea-error' : ''"
                @if($required) required @endif
            ></textarea>
        @endif
    @elseif($fieldType === 'select')
        @if($preview)
            <select
                id="{{ $fieldId }}"
                class="select select-bordered w-full bg-base-200"
                disabled
            >
                <option value="">Select...</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        @else
            <select
                id="{{ $fieldId }}"
                class="select select-bordered w-full"
                x-model="formData.{{ $fieldName }}"
                x-bind:class="errors.{{ $fieldName }} ? 'select-error' : ''"
                @if($required) required @endif
            >
                <option value="">Select...</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        @endif
    @else
        @if($preview)
            <input
                type="{{ $fieldType }}"
                id="{{ $fieldId }}"
                class="input input-bordered w-full bg-base-200"
                disabled
                placeholder="{{ $fieldType === 'email' ? 'email@example.com' : ($fieldType === 'tel' ? '(555) 123-4567' : 'Enter ' . strtolower($fieldLabel) . '...') }}"
            >
        @else
            <input
                type="{{ $fieldType }}"
                id="{{ $fieldId }}"
                class="input input-bordered w-full"
                x-model="formData.{{ $fieldName }}"
                x-bind:class="errors.{{ $fieldName }} ? 'input-error' : ''"
                @if($required) required @endif
            >
        @endif
    @endif

    @if(!$preview)
        <template x-if="errors.{{ $fieldName }}">
            <label class="label">
                <span class="label-text-alt text-error" x-text="errors.{{ $fieldName }}"></span>
            </label>
        </template>
    @endif
</div>
