@props([
    'title',
    'field',
    'filters',
    'filterType' => 'name', // Hacemos setFilter() revisando ya sea el id o el name del field que nos pasan
])

<div class="dropdown dropdown-center w-full h-full">
    <div tabindex="0" role="button" class="btn btn-ghost h-full w-full hover:text-white hover:bg-[#561227] transition font-bold">{{ $title }}</div>
    <ul tabindex="0" class="dropdown-content menu max-w-[max-content] max-h-[75vh] overflow-y-auto bg-white text-gray-700 rounded-box shadow-sm">
        @foreach ($filters as $id => $name)
            <li>
                <label class="flex text-xs whitespace-nowrap">
                    <input type="checkbox" checked="checked" class="checkbox checkbox-neutral checkbox-xs "
                            wire:click="setFilter('{{ $field }}', {{ $filterType === 'id' ? $id : '\'' . $name . '\'' }})" />
                    {{ $name }}
                </label>
            </li>
        @endforeach
    </ul>
</div>