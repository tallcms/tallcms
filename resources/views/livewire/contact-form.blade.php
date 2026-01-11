<div>
    {{-- Form-level error (rate limiting) --}}
    @error('form')
        <div class="alert alert-error mb-6" role="alert">
            <x-heroicon-o-exclamation-circle class="w-6 h-6" />
            <span>{{ $message }}</span>
        </div>
    @enderror

    @if($submitted)
        {{-- Success message --}}
        <div class="alert alert-success">
            <x-heroicon-o-check-circle class="w-12 h-12" />
            <span class="text-lg font-medium">
                {{ $config['success_message'] ?? 'Thank you for your message!' }}
            </span>
        </div>
    @endif

    @if(!$submitted)
        <form wire:submit="submit" class="space-y-6">
            @foreach($config['fields'] ?? [] as $field)
                <div class="form-control w-full">
                    <label for="{{ $field['name'] }}" class="label">
                        <span class="label-text">
                            {{ $field['label'] }}
                            @if($field['required'] ?? false)
                                <span class="text-error">*</span>
                            @endif
                        </span>
                    </label>

                    @if($field['type'] === 'textarea')
                        <textarea
                            id="{{ $field['name'] }}"
                            wire:model="formData.{{ $field['name'] }}"
                            rows="5"
                            class="textarea textarea-bordered w-full @error('formData.'.$field['name']) textarea-error @enderror"
                            @if($field['required'] ?? false) required @endif
                        ></textarea>
                    @elseif($field['type'] === 'select')
                        <select
                            id="{{ $field['name'] }}"
                            wire:model="formData.{{ $field['name'] }}"
                            class="select select-bordered w-full @error('formData.'.$field['name']) select-error @enderror"
                            @if($field['required'] ?? false) required @endif
                        >
                            <option value="">Select...</option>
                            @foreach($field['options'] ?? [] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="{{ $field['type'] }}"
                            id="{{ $field['name'] }}"
                            wire:model="formData.{{ $field['name'] }}"
                            class="input input-bordered w-full @error('formData.'.$field['name']) input-error @enderror"
                            @if($field['required'] ?? false) required @endif
                        >
                    @endif

                    @error('formData.'.$field['name'])
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>
            @endforeach

            {{-- Honeypot field (hidden from users, visible to bots) --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" wire:model="honeypot" tabindex="-1" autocomplete="off">
            </div>

            {{-- Submit button --}}
            <div>
                <button
                    type="submit"
                    class="btn btn-primary w-full sm:w-auto"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        {{ $config['submit_button_text'] ?? 'Send Message' }}
                    </span>
                    <span wire:loading class="inline-flex items-center">
                        <span class="loading loading-spinner loading-sm mr-2"></span>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
