@props([
    'content'
])

<td {{ $attributes }}
    class="border-r border-gray-300 transition-all hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm cursor-pointer group relative">

    @if(empty($content))
        {{-- Celdas vacías --}}
        <div class="flex items-center justify-center h-full">
            <svg class="w-0 h-0 opacity-0 group-hover:w-5 group-hover:h-5 group-hover:opacity-100 transition-all duration-200" 
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </div>
    @else
        {{-- Celdas con contenido --}}
        <span class="inline-flex items-center gap-0 group-hover:gap-2 overflow-hidden w-full">
            <svg class="w-0 h-4 flex-shrink-0 opacity-0 group-hover:w-4 group-hover:opacity-100 transition-all duration-200 overflow-hidden"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            <span class="truncate">{{ $content }}</span>
        </span>
    @endif
    
</td>
