@props([
    'title',
    'field',
])

<div class="dropdown dropdown-center w-full h-full">
    <div tabindex="0" role="button" class="btn btn-ghost h-full w-full hover:bg-[#561227] transition text-white font-bold">{{ $title }}</div>
    <ul tabindex="0" class="dropdown-content menu w-max bg-white text-gray-700 rounded-box z-1 p-2 shadow-sm">
        <li>
            <label class="flex text-xs">
                <input type="radio" name="orden" class="radio radio-xs" {{ $attributes }}
                        wire:click="setOrder('{{ $field }}', 'desc')" />Más recientes
            </label>
        </li>
        <li>
            <label class="flex text-xs">
                <input type="radio" name="orden" class="radio radio-xs"
                        wire:click="setOrder('{{ $field }}', 'asc')" />Más antiguos
            </label>
        </li>

        <div class="divider my-0.5"></div>

        <div class="flex flex-col gap-2 text-xs">
            <label class="flex flex-col">
                <span class="text-xs text-left text-gray-500 pb-1">Desde:</span>
                <input type="date" class="input input-xs" wire:model.lazy="period.{{ $field }}.from"/>
            </label>
            <label class="flex flex-col">
                <span class="text-xs text-left text-gray-500 pb-1">Hasta:</span>
                <input type="date" class="input input-xs" wire:model.lazy="period.{{ $field }}.to"/>
            </label>
        </div>
    </ul>
</div>