{{--
Tujuan: Inisialisasi TinyMCE WYSIWYG editor untuk semua textarea#editor di form post.
Caller: Di-@include oleh layouts/admin.blade.php dan layouts/author.blade.php.
Dependensi: public/vendor/tinymce/tinymce.min.js (self-hosted), media-picker-modal component.
Main Functions: tinymce.init() targeting textarea#editor.
Side Effects: Transform textarea jadi rich text editor. Output HTML string ke textarea.

Integrasi media:
  - Tombol Image di toolbar → buka media-picker-modal (Gallery + Upload tabs)
  - Upload di modal otomatis masuk Media Library
  - Pilih gambar → insert <img> ke editor dengan URL /storage/...

Toolbar/plugin diselaraskan dengan config/purifier.php preset 'blog':
  Allowed: h2,h3,h4,p,br,strong,em,b,i,u,s,a,ul,ol,li,blockquote,code,pre,hr,img,figure,figcaption
--}}
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editorEl = document.getElementById('editor');
    if (!editorEl) return;

    const isDark = document.documentElement.classList.contains('dark');

    // Detect media API URLs from the media-picker component on the same page (if present)
    // Fallback: use admin routes by default
    const mediaApiUrl = '{{ request()->is("author/*") ? route("author.media.json") : route("admin.media.json") }}';
    const mediaUploadUrl = '{{ request()->is("author/*") ? route("author.media.store") : route("admin.media.store") }}';

    // Resolve a pending file_picker_callback — set by TinyMCE, fulfilled by media modal event
    let pendingPickerResolve = null;

    tinymce.init({
        target: editorEl,
        license_key: 'gpl',

        // Self-hosted paths
        base_url: '{{ asset("vendor/tinymce") }}',
        suffix: '.min',

        // Appearance
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        height: 500,
        menubar: false,
        branding: false,
        promotion: false,
        statusbar: true,
        elementpath: false,
        resize: 'both',

        // Plugins
        plugins: 'lists link image code blockquote hr codesample preview wordcount autolink autoresize',

        // Toolbar
        toolbar: [
            'blocks | bold italic underline strikethrough | forecolor backcolor removeformat',
            'bullist numlist | blockquote codesample hr | link image | code preview'
        ].join(' | '),

        // Block formats
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',

        // Link settings
        default_link_target: '_blank',
        link_default_protocol: 'https',

        // Image — use file_picker_callback to open media modal instead of default URL dialog
        image_advtab: false,
        image_description: true,
        automatic_uploads: false,

        // File picker callback — opens our media-picker-modal
        file_picker_types: 'image',
        file_picker_callback: function (callback, value, meta) {
            if (meta.filetype === 'image') {
                // Store callback for when user picks/uploads in modal
                pendingPickerResolve = callback;

                // Dispatch event to open media-picker-modal with a special target
                window.dispatchEvent(new CustomEvent('open-media-modal', {
                    detail: {
                        target: '_tinymce_image',
                        apiUrl: mediaApiUrl,
                        uploadUrl: mediaUploadUrl
                    }
                }));
            }
        },

        // Paste cleanup
        paste_as_text: false,
        paste_block_drop: false,
        paste_data_images: false,
        paste_remove_styles_if_webkit: true,

        // Valid elements — mirror Purifier 'blog' preset
        valid_elements: 'h2,h3,h4,p,br,strong/b,em/i,u,s,a[href|target|rel],ul,ol,li,blockquote,code,pre,hr,img[src|alt|title|width|height],figure,figcaption',
        valid_children: '+body[style]',
        extended_valid_elements: 'a[href|target|rel]',

        // Content style inside editor iframe
        content_style: `
            body {
                font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                font-size: 16px;
                line-height: 1.75;
                color: ${isDark ? '#d1d5db' : '#374151'};
                max-width: 100%;
                padding: 1rem;
            }
            h2 { font-size: 1.5em; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; }
            h3 { font-size: 1.25em; font-weight: 600; margin-top: 1.25em; margin-bottom: 0.4em; }
            h4 { font-size: 1.1em; font-weight: 600; margin-top: 1em; margin-bottom: 0.3em; }
            blockquote {
                border-left: 4px solid ${isDark ? '#6366f1' : '#4f46e5'};
                padding-left: 1rem;
                margin: 1em 0;
                font-style: italic;
                color: ${isDark ? '#9ca3af' : '#6b7280'};
            }
            pre {
                background: ${isDark ? '#1f2937' : '#111827'};
                color: #e5e7eb;
                padding: 1rem;
                border-radius: 0.5rem;
                overflow-x: auto;
            }
            code {
                background: ${isDark ? '#374151' : '#f3f4f6'};
                padding: 0.15em 0.4em;
                border-radius: 0.25rem;
                font-size: 0.9em;
            }
            pre code { background: transparent; padding: 0; }
            img { max-width: 100%; height: auto; border-radius: 0.5rem; }
            a { color: ${isDark ? '#818cf8' : '#4f46e5'}; }
        `,

        // Sync + media pick listener
        setup: function (editor) {
            editor.on('change keyup', function () {
                editor.save();
            });

            const form = editorEl.closest('form');
            if (form) {
                form.addEventListener('submit', function () {
                    editor.save();
                });
            }
        }
    });

    // Listen for media pick event from modal — resolve the pending TinyMCE callback
    window.addEventListener('media-picked-_tinymce_image', function (e) {
        if (pendingPickerResolve) {
            pendingPickerResolve(e.detail.url, { alt: '' });
            pendingPickerResolve = null;
        }
    });
});
</script>
