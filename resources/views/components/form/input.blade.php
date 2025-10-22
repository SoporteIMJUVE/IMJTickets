@props([
    'legend',
    'model',
    'type',
])

<fieldset class="fieldset relative">
    <legend class="fieldset-legend text-legend">{{ $legend }}</legend>
    <input {{ $attributes }}
            wire:model="{{ $model }}" type="{{ $type }}"
            class="input w-full @error($model) border-red-500 border-3 @enderror"/>
    @error($model) 
        <p class="absolute -bottom-4 right-0 font-bold text-red-500">{{ $message }}</p>
    @enderror
</fieldset>