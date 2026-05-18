@props(['name' => 'featured_image_media_id', 'currentImage' => null, 'apiUrl', 'uploadUrl' => ''])

<div x-data="{
    selectedUrl: '{{ $currentImage ?? '' }}',
    selectedPath: '',
    clearSelection() {
        this.selectedUrl = '';
        this.selectedPath = '';
    }
}" x-on:media-picked-{{ $name }}.window="selectedUrl = $event.detail.url; selectedPath = $event.detail.path" class="space-y-3">
    <div x-show="selectedUrl" x-cloak class="relative mb-1">
        <img :src="selectedUrl" alt="" class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
        <button type="button" @click="clearSelection()" class="absolute -top-2 -right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700 shadow-lg border-2 border-white dark:border-gray-800">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <input type="hidden" name="{{ $name }}" :value="selectedPath">

    <button type="button"
            @click="$dispatch('open-media-modal', { target: '{{ $name }}', apiUrl: '{{ $apiUrl }}', uploadUrl: '{{ $uploadUrl }}' })"
            class="w-full inline-flex items-center justify-center px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        {{ __('Pilih dari Media') }}
    </button>
</div>
