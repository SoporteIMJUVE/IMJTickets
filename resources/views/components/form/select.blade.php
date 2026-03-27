@props([
    'legend',
    'model',
    'placeholder',
    'options'
])

<fieldset class="fieldset relative">
    <legend class="fieldset-legend text-legend">{{ $legend }}</legend>
    <select wire:model="{{ $model }}" 
            class="select w-full @error($model) border-red-500 border-3 @enderror">

        <option disabled selected value="error">{{ $placeholder }}</option>
            @foreach ($options as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
    </select>

    @error($model)
        <p class="absolute -bottom-4 right-0 font-bold text-red-500 ">{{ $message }}</p>
    @enderror

</fieldset>