@props([
    'target',
    'message'
])

<div wire:loading.delay.class="opacity-100 visible" 
     wire:target="{{ $target }}" 
     class="opacity-0 invisible transition-all duration-500 fixed inset-[-1px] w-screen h-screen bg-black/50 backdrop-blur-md z-[9999] pointer-events-auto flex items-center justify-center overflow-hidden">
    
    <div class="bg-white border-2 border-gray-200 shadow-2xl rounded-2xl p-8 flex flex-col items-center gap-3">
        <span class="loading loading-dots loading-lg text-imjuve"></span>
        <span class="font-bold text-black animate-pulse">{{ $message }}</span>
    </div>
</div>