{{-- Global Media Picker Modal — rendered at layout level, outside all forms --}}
<div x-data="{
    open: false,
    loading: false,
    uploading: false,
    items: [],
    page: 1,
    hasMore: false,
    target: '',
    apiUrl: '',
    uploadUrl: '',
    activeTab: 'gallery',
    pickedId: null,
    pickedName: '',
    pickedPath: '',
    pickedUrl: '',
    uploadError: '',

    openPicker(detail) {
        if (document.querySelector('form[data-submitting]')) return;
        this.target = detail.target;
        this.apiUrl = detail.apiUrl;
        this.uploadUrl = detail.uploadUrl || '';
        this.pickedId = null;
        this.pickedName = '';
        this.pickedUrl = '';
        this.pickedPath = '';
        this.uploadError = '';
        this.activeTab = 'gallery';
        this.open = true;
        this.items = [];
        this.page = 1;
        this.loadItems();
    },

    async loadItems() {
        this.loading = true;
        try {
            const res = await fetch(this.apiUrl + '?page=' + this.page, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            this.items = this.page === 1 ? data.data : [...this.items, ...data.data];
            this.hasMore = data.current_page < data.last_page;
        } catch (e) {
            console.error('Media picker error:', e);
        }
        this.loading = false;
    },

    loadMore() {
        this.page++;
        this.loadItems();
    },

    pick(item) {
        this.pickedId = item.id;
        this.pickedName = item.original_name;
        this.pickedPath = item.path;
        this.pickedUrl = '/storage/' + item.path;
    },

    async uploadFiles(event) {
        const files = event.target.files;
        if (!files.length || !this.uploadUrl) return;

        this.uploading = true;
        this.uploadError = '';

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        try {
            const res = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: formData
            });

            if (!res.ok) {
                const err = await res.json();
                this.uploadError = err.message || Object.values(err.errors || {}).flat().join(', ') || 'Upload gagal';
                this.uploading = false;
                return;
            }

            const data = await res.json();
            const uploaded = data.media || [];

            // Auto-select the first uploaded image
            if (uploaded.length > 0) {
                this.pick(uploaded[0]);
            }

            // Refresh gallery to show new uploads
            this.page = 1;
            await this.loadItems();
            this.activeTab = 'gallery';

        } catch (e) {
            this.uploadError = 'Upload gagal: ' + e.message;
        }

        this.uploading = false;
        event.target.value = '';
    },

    confirm() {
        if (!this.pickedId) return;
        window.dispatchEvent(new CustomEvent('media-picked-' + this.target, {
            detail: { url: this.pickedUrl, path: this.pickedPath }
        }));
        this.open = false;
    },

    close() {
        this.open = false;
    }
}" x-on:open-media-modal.window="openPicker($event.detail)" x-on:keydown.escape.window="close()" x-show="open" x-cloak
   class="fixed inset-0 overflow-y-auto" style="z-index: 9999;">

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-gray-900/70"></div>

    {{-- Dialog --}}
    <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
        <div x-show="open"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col border border-gray-200 dark:border-gray-700"
             style="max-height: 85vh;">

            {{-- Header with Tabs --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Pilih Gambar') }}</h3>
                    </div>

                    {{-- Tab Buttons --}}
                    <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5 ml-2">
                        <button type="button" @click="activeTab = 'gallery'"
                                :class="activeTab === 'gallery' ? 'bg-white dark:bg-gray-600 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            {{ __('Gallery') }}
                        </button>
                        <button type="button" @click="activeTab = 'upload'" x-show="uploadUrl"
                                :class="activeTab === 'upload' ? 'bg-white dark:bg-gray-600 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            {{ __('Upload') }}
                        </button>
                    </div>
                </div>
                <button type="button" @click="close()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto p-4" style="min-height: 200px;">

                {{-- ===== TAB: GALLERY ===== --}}
                <div x-show="activeTab === 'gallery'">
                    {{-- Loading --}}
                    <div x-show="loading && items.length === 0" class="flex items-center justify-center py-12">
                        <svg class="animate-spin h-6 w-6 text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="ml-2 text-sm text-gray-500">{{ __('Memuat...') }}</span>
                    </div>

                    {{-- Empty --}}
                    <div x-show="!loading && items.length === 0" class="text-center py-12">
                        <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm text-gray-500">{{ __('Belum ada media.') }}</p>
                        <button type="button" x-show="uploadUrl" @click="activeTab = 'upload'" class="mt-2 text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">{{ __('Upload gambar pertama') }}</button>
                    </div>

                    {{-- Grid --}}
                    <div x-show="items.length > 0" class="flex flex-wrap gap-1.5">
                        <template x-for="item in items" :key="item.id">
                            <div @click="pick(item)"
                                 :class="pickedId === item.id
                                     ? 'ring-2 ring-primary-500 ring-offset-1 dark:ring-offset-gray-800 border-primary-500'
                                     : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500'"
                                 class="cursor-pointer rounded border overflow-hidden transition-all w-20 h-20 flex-shrink-0">
                                <div class="w-full h-full relative bg-gray-100 dark:bg-gray-900">
                                    <img :src="'/storage/' + item.path" :alt="item.original_name" class="w-full h-full object-cover" loading="lazy">
                                    <div x-show="pickedId === item.id" x-cloak class="absolute inset-0 bg-primary-500/20 flex items-center justify-center">
                                        <div class="w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="hasMore && !loading" class="text-center mt-4">
                        <button type="button" @click="loadMore()" class="text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">{{ __('Muat Lebih Banyak') }}</button>
                    </div>
                </div>

                {{-- ===== TAB: UPLOAD ===== --}}
                <div x-show="activeTab === 'upload'" class="py-6">
                    <div class="max-w-md mx-auto text-center">
                        {{-- Drop zone --}}
                        <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-2xl cursor-pointer transition-colors"
                               :class="uploading ? 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-900/10'">
                            <div x-show="!uploading" class="flex flex-col items-center">
                                <svg class="w-10 h-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Klik untuk pilih gambar') }}</p>
                                <p class="text-xs text-gray-400 mt-1">JPEG, PNG, GIF, WebP — maks 5 MB per file</p>
                            </div>
                            <div x-show="uploading" class="flex flex-col items-center">
                                <svg class="animate-spin h-8 w-8 text-primary-500 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Mengupload...') }}</p>
                            </div>
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" multiple @change="uploadFiles($event)" :disabled="uploading">
                        </label>

                        {{-- Upload error --}}
                        <p x-show="uploadError" x-text="uploadError" class="mt-3 text-sm text-red-500"></p>

                        <p class="mt-4 text-xs text-gray-400">{{ __('Gambar yang diupload otomatis masuk ke Media Library Anda.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
                <p class="text-xs text-gray-500 truncate max-w-[200px]" x-text="pickedName || ''"></p>
                <div class="flex gap-2">
                    <button type="button" @click="close()" class="px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">{{ __('Batal') }}</button>
                    <button type="button" @click="confirm()" :disabled="!pickedId"
                            class="px-4 py-1.5 text-sm font-medium text-white bg-primary-600 rounded-lg"
                            :class="pickedId ? 'hover:bg-primary-700' : 'opacity-40 cursor-not-allowed'">
                        {{ __('Pilih Gambar') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
