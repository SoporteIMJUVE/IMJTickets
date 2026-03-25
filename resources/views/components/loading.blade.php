@props([
    'target',
    'message'
])

<div wire:loading.delay.class="opacity-100 visible" 
     wire:target="{{ $target }}" 
     class="opacity-0 invisible transition-all duration-500 fixed inset-0 bg-black/30 backdrop-blur-sm z-[100] pointer-events-auto"></div>

<div wire:loading.delay.class="opacity-100 visible" 
     wire:target="{{ $target }}" 
     class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[110] bg-white border border-gray-100 shadow-2xl rounded-2xl p-7 flex flex-col items-center gap-3">
    <span class="loading loading-dots loading-lg text-imjuve"></span>
    <span class="font-semibold text-black animate-pulse">{{ $message }}</span>
</div>