@php
    use Illuminate\Support\Collection;
    
    $images = collect($getImages());
    $columns = $getGridColumns();
    $height = $getHeight();
    $emptyStateMessage = $getEmptyStateMessage();
@endphp

@if ($images->isNotEmpty())
    <div {{ $attributes }} class="p-4">
        <div class="grid gap-4" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
            @foreach ($images as $image)
                <div class="relative overflow-hidden rounded-lg shadow-sm p-2">
                    <img 
                        src="{{ $image['thumbnail'] ?? $image['url'] }}" 
                        alt="{{ $image['name'] ?? 'Image' }}"
                        class="w-full h-full object-cover"
                        style="max-height: {{ $height }}; min-height: 200px;"
                        loading="lazy"
                    >
                </div>
            @endforeach
        </div>
        
        @if($images->count() > 0)
            <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('Total :count images', ['count' => $images->count()]) }}
            </div>
        @endif
    </div>
@else
    <div {{ $attributes }} class="text-center py-12 text-gray-500 dark:text-gray-400">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <p class="text-lg font-medium mb-1">{{ $emptyStateMessage }}</p>
        <p class="text-sm">{{ __('No images found in this collection') }}</p>
    </div>
@endif
