@props([
    'legend',
    'legendAccent' => null,
    'model' => null,
    'content' => null
])

<fieldset class="fieldset relative">
    <legend class="fieldset-legend text-legend">{{ $legend }}
        <span class="text-imjuve">{{ $legendAccent }}</span>
    </legend>
    <textarea {{ $attributes }}
                wire:model="{{ $model }}"
                class="textarea w-full @error($model) border-red-500 border-3 @enderror">
    </textarea>

    @error($model) 
        <p class="-bottom-4 font-bold text-red-500 {{ strlen($message) > 80 ? 'text-center' : 'absolute right-0' }}">{{ $message }}</p>
    @enderror
    
</fieldset>