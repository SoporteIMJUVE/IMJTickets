@props([
    'title',
    'field',
    'filters',
    'filterType' => 'name', // setFilter() by tickets field (id or name)
])

<div class="dropdown dropdown-center w-full h-full">
    <div tabindex="0" role="button" class="btn btn-ghost h-full w-full hover:bg-[#561227] transition font-bold">{{ $title }}</div>
    <ul tabindex="0" class="dropdown-content menu w-max bg-white text-gray-700 rounded-box z-1 p-2 shadow-sm">
        @foreach ($filters as $id => $name)
            <li>
                <label class="flex text-xs">
                    @if ($filterType === 'name')
                        <input type="checkbox" checked="checked" class="checkbox checkbox-neutral checkbox-xs" wire:click="setFilter('{{ $field }}', '{{ $name }}')" />
                    @elseif ($filterType === 'id')
                        <input type="checkbox" checked="checked" class="checkbox checkbox-neutral checkbox-xs" wire:click="setFilter('{{ $field }}', {{ $id }})" />
                    @endif
                    {{ $name }}
                </label>
            </li>
        @endforeach
    </ul>
</div>