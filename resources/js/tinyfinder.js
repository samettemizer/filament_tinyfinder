/**
 * TinyFinder Filament Plugin
 *
 * This file is published directly by Filament assets, so it must remain a
 * browser-ready classic script. Filament provides window.Alpine.
 */

(() => {
    const fallbackWriteText = (value) => new Promise((resolve, reject) => {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
            resolve();
        } catch (error) {
            reject(error);
        }
    });

    if (! window.navigator.clipboard?.writeText) {
        try {
            Object.defineProperty(window.navigator, 'clipboard', {
                configurable: true,
                value: {
                    writeText: fallbackWriteText,
                },
            });
        } catch (error) {
            window.tinyFinderWriteText = fallbackWriteText;
        }
    }

    const syncTinyFinderDisplayNames = (root = document) => {
        root.querySelectorAll?.('.tinyfinder-display-name').forEach((marker) => {
            const field = marker.closest('.fi-fo-field-wrp') || marker.closest('[wire\\:key]') || marker.parentElement;
            const input = field?.querySelector('input');
            const displayName = marker.dataset.displayName;

            if (! input || ! displayName || input.value === displayName) {
                return;
            }

            input.value = displayName;
            input.title = marker.dataset.storedValue || displayName;
        });
    };

    const hideTinyFinderArchivePreview = () => {
        const preview = document.querySelector('.tinyfinder-archive-hover-preview');

        if (preview) {
            preview.style.display = 'none';
        }
    };

    const focusTinyFinderArchiveSearch = (root = document) => {
        const archiveSelect = root.querySelector?.('.tinyfinder-archive-select') || root.closest?.('.tinyfinder-archive-select');

        if (! archiveSelect || archiveSelect.dataset.tinyfinderFocused === '1') {
            return;
        }

        archiveSelect.dataset.tinyfinderFocused = '1';

        setTimeout(() => {
            const button = archiveSelect.querySelector('button');

            button?.focus();
            button?.click();

            setTimeout(() => {
                const searchInput = document.querySelector('.fi-select-input-search-ctn input, .fi-select-input-search, .fi-select-input input[type="search"], .fi-select-input input:not([type]), .fi-select-input input[type="text"]');

                searchInput?.focus();
            }, 80);
        }, 180);
    };

    const getTinyFinderField = (element) => element?.closest?.('.fi-fo-field-wrp') || element?.closest?.('[wire\\:key]') || element?.parentElement;

    const normalizeTinyFinderPath = (value = '') => {
        try {
            return new URL(value, window.location.origin).pathname;
        } catch (error) {
            return value;
        }
    };

    const withTinyFinderRevision = (value = '', revision = Date.now()) => {
        if (! value) {
            return value;
        }

        try {
            const url = new URL(value, window.location.origin);

            url.searchParams.delete('t');
            url.searchParams.set('revision', revision);

            return url.href;
        } catch (error) {
            const [path, query = ''] = value.split('?');
            const params = new URLSearchParams(query);

            params.delete('t');
            params.set('revision', revision);

            return `${path}?${params.toString()}`;
        }
    };

    const withoutTinyFinderRevision = (value = '') => {
        if (! value) {
            return value;
        }

        try {
            const url = new URL(value, window.location.origin);

            url.searchParams.delete('revision');
            url.searchParams.delete('t');

            return url.href;
        } catch (error) {
            const [path, query = ''] = value.split('?');
            const params = new URLSearchParams(query);

            params.delete('revision');
            params.delete('t');

            const queryString = params.toString();

            return queryString ? `${path}?${queryString}` : path;
        }
    };

    const refreshTinyFinderPreviewImages = (option, previousUrls = []) => {
        const filePath = normalizeTinyFinderPath(option?.dataset.filePath || option?.dataset.fileUrl || '');
        const paths = [
            filePath,
            ...previousUrls.map((url) => normalizeTinyFinderPath(url)),
            normalizeTinyFinderPath(option?.dataset.fileUrl || ''),
            normalizeTinyFinderPath(option?.dataset.previewUrl || ''),
        ].filter(Boolean);

        document.querySelectorAll('.tinyfinder-image-preview img, .tinyfinder-archive-option img, .tinyfinder-archive-hover-preview img, .tinyfinder-crop-stage img').forEach((image) => {
            if (! paths.includes(normalizeTinyFinderPath(image.getAttribute('src') || image.src || ''))) {
                return;
            }

            const needsFullImage = image.closest('.tinyfinder-image-preview') || image.closest('.tinyfinder-crop-stage');

            image.setAttribute('src', needsFullImage ? option.dataset.fileUrl : option.dataset.previewUrl);
        });

        const activeInput = window.tinyFinderActiveField?.querySelector?.('input.tinyfinder-input-trigger');
        const activePath = normalizeTinyFinderPath(activeInput?.dataset.tinyfinderStoredValue || activeInput?.title || '');

        if (activeInput && activePath && activePath === filePath) {
            window.tinyFinderActiveField
                ?.querySelectorAll?.('.tinyfinder-image-preview img')
                ?.forEach((image) => image.setAttribute('src', option.dataset.fileUrl));
        }
    };

    const reviseTinyFinderOptionUrls = (option, data = {}) => {
        const revision = Date.now();
        const previousUrls = [option.dataset.fileUrl, option.dataset.previewUrl].filter(Boolean);
        const fileUrl = data.url || option.dataset.fileUrl || option.dataset.previewUrl || '';
        const previewUrl = data.thumbnail_url || data.url || option.dataset.previewUrl || option.dataset.fileUrl || '';

        option.dataset.fileUrl = withTinyFinderRevision(fileUrl, revision);
        option.dataset.previewUrl = withTinyFinderRevision(previewUrl, revision);

        refreshTinyFinderPreviewImages(option, previousUrls);
    };

    const setTinyFinderActiveField = (element) => {
        const field = getTinyFinderField(element);

        if (field) {
            window.tinyFinderActiveField = field;
        }
    };

    const clearTinyFinderFieldIfSelected = (option) => {
        const filePath = normalizeTinyFinderPath(option?.dataset.filePath || option?.dataset.fileUrl || '');
        const fileName = option?.dataset.fileName || '';
        const field = window.tinyFinderActiveField;
        const input = field?.querySelector?.('input.tinyfinder-input-trigger');

        if (! input || (! filePath && ! fileName)) {
            return;
        }

        const currentPath = normalizeTinyFinderPath(input.dataset.tinyfinderStoredValue || input.title || input.value || '');
        const currentName = input.value || '';

        if (currentPath !== filePath && currentName !== fileName) {
            return;
        }

        input.value = '';
        input.title = '';
        delete input.dataset.tinyfinderStoredValue;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const submitTinyFinderArchiveModal = (option) => {
        const modal = option.closest?.('.fi-modal') || document.querySelector('.fi-modal');

        setTimeout(() => {
            const submitButton = Array.from(modal?.querySelectorAll('button') || []).find((button) => {
                const text = button.textContent?.trim() || '';

                return ! button.disabled && text === 'Use selected file';
            });

            submitButton?.click();
        }, 120);
    };

    const clickTinyFinderAction = (input, actionName) => {
        const field = getTinyFinderField(input);
        const actionClass = actionName === 'tinyfinder_archive' ? '.tinyfinder-archive-action' : '.tinyfinder-upload-action';
        const actionButton = field?.querySelector(`${actionClass}, [wire\\:click*="${actionName}"], [x-on\\:click*="${actionName}"], button[title*="${actionName}"], button[aria-label*="${actionName}"]`);

        if (actionName === 'tinyfinder_upload' || actionName === 'tinyfinder_archive') {
            window.tinyFinderActiveField = field;
        }

        actionButton?.click();
    };

    window.tinyFinderOpenActiveArchive = () => {
        const modal = document.querySelector('.fi-modal');
        const field = window.tinyFinderActiveField || document.querySelector('.fi-fo-field-wrp:has(.tinyfinder-archive-action), [wire\\:key]:has(.tinyfinder-archive-action)');

        modal?.querySelector('[aria-label="Close"], button[title="Close"]')?.click();

        setTimeout(() => {
            field?.querySelector('.tinyfinder-archive-action')?.click();
        }, 120);
    };

    window.tinyFinderOpenActiveUpload = () => {
        const modal = document.querySelector('.fi-modal');
        const field = window.tinyFinderActiveField || document.querySelector('.fi-fo-field-wrp:has(.tinyfinder-upload-action), [wire\\:key]:has(.tinyfinder-upload-action)');

        modal?.querySelector('[aria-label="Close"], button[title="Close"]')?.click();

        setTimeout(() => {
            field?.querySelector('.tinyfinder-upload-action')?.click();
        }, 120);
    };

    const openTinyFinderCropModal = (fileUrl) => new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'tinyfinder-crop-modal';
        modal.innerHTML = `
            <div class="tinyfinder-crop-panel">
                <div class="tinyfinder-crop-stage">
                    <img src="${fileUrl}" alt="">
                    <div class="tinyfinder-crop-selection"></div>
                </div>
                <div class="tinyfinder-crop-actions">
                    <button type="button" data-crop-cancel>Cancel</button>
                    <button type="button" data-crop-save>Crop</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const stage = modal.querySelector('.tinyfinder-crop-stage');
        const image = modal.querySelector('img');
        const selection = modal.querySelector('.tinyfinder-crop-selection');
        let startX = 0;
        let startY = 0;
        let box = null;
        let isDragging = false;

        const handleMouseMove = (event) => {
            if (! isDragging || ! box) {
                return;
            }

            const rect = image.getBoundingClientRect();
            const currentX = Math.max(0, Math.min(rect.width, event.clientX - rect.left));
            const currentY = Math.max(0, Math.min(rect.height, event.clientY - rect.top));
            const left = Math.min(startX, currentX);
            const top = Math.min(startY, currentY);
            const width = Math.abs(currentX - startX);
            const height = Math.abs(currentY - startY);

            Object.assign(box, { left, top, width, height });
            Object.assign(selection.style, {
                left: `${left}px`,
                top: `${top}px`,
                width: `${width}px`,
                height: `${height}px`,
            });
        };

        const handleMouseUp = () => {
            if (! isDragging) {
                return;
            }

            isDragging = false;

            if (! box || box.width <= 2 || box.height <= 2) {
                box = null;
                selection.style.display = 'none';
            }
        };

        const close = (value = null) => {
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseup', handleMouseUp);
            modal.remove();
            resolve(value);
        };

        image.addEventListener('load', () => {
            selection.style.display = 'none';
        });

        stage.addEventListener('mousedown', (event) => {
            event.preventDefault();

            const rect = image.getBoundingClientRect();
            startX = Math.max(0, Math.min(rect.width, event.clientX - rect.left));
            startY = Math.max(0, Math.min(rect.height, event.clientY - rect.top));
            box = { left: startX, top: startY, width: 0, height: 0 };
            isDragging = true;

            Object.assign(selection.style, {
                display: 'block',
                left: `${box.left}px`,
                top: `${box.top}px`,
                width: '0px',
                height: '0px',
            });
        });

        window.addEventListener('mousemove', handleMouseMove);
        window.addEventListener('mouseup', handleMouseUp);

        modal.querySelector('[data-crop-cancel]').addEventListener('click', () => close());
        modal.querySelector('[data-crop-save]').addEventListener('click', () => {
            if (! box) {
                close();

                return;
            }

            const scaleX = image.naturalWidth / image.clientWidth;
            const scaleY = image.naturalHeight / image.clientHeight;

            close({
                x: Math.round(box.left * scaleX),
                y: Math.round(box.top * scaleY),
                width: Math.round(box.width * scaleX),
                height: Math.round(box.height * scaleY),
            });
        });
    });

    document.addEventListener('DOMContentLoaded', () => syncTinyFinderDisplayNames());

    document.addEventListener('click', (event) => {
        const input = event.target?.closest?.('input.tinyfinder-input-trigger');

        if (! input) {
            return;
        }

        clearTimeout(input._tinyFinderClickTimer);
        input._tinyFinderClickTimer = setTimeout(() => {
            clickTinyFinderAction(input, 'tinyfinder_upload');
        }, 220);
    });

    document.addEventListener('dblclick', (event) => {
        const input = event.target?.closest?.('input.tinyfinder-input-trigger');

        if (! input) {
            return;
        }

        clearTimeout(input._tinyFinderClickTimer);
        clickTinyFinderAction(input, 'tinyfinder_archive');
    });

    document.addEventListener('click', (event) => {
        const archiveAction = event.target?.closest?.('.tinyfinder-archive-action');

        if (archiveAction) {
            setTinyFinderActiveField(archiveAction);
        }
    }, true);

    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    syncTinyFinderDisplayNames(node);
                    focusTinyFinderArchiveSearch(node);
                }
            });

            mutation.removedNodes.forEach((node) => {
                if (
                    node.nodeType === Node.ELEMENT_NODE &&
                    (
                        node.querySelector?.('.tinyfinder-archive-option') ||
                        node.classList?.contains('tinyfinder-archive-option')
                    )
                ) {
                    hideTinyFinderArchivePreview();
                }
            });
        });

        syncTinyFinderDisplayNames();
    }).observe(document.documentElement, {
        childList: true,
        subtree: true,
    });

    const registerTinyFinder = (Alpine) => {
        if (! Alpine || Alpine.__tinyFinderRegistered) {
            return;
        }

        Alpine.__tinyFinderRegistered = true;

// File Manager Component
Alpine.data('tinyFinderManager', (config = {}) => ({
    files: [],
    selectedFile: null,
    view: config.defaultView || 'grid',
    filter: {
        search: '',
        type: 'all', // all, image, file
        sort: 'created_at',
        order: 'desc',
    },
    uploading: false,
    uploadProgress: 0,

    init() {
        this.loadFiles();
    },

    async loadFiles() {
        try {
            const params = new URLSearchParams({
                search: this.filter.search,
                type: this.filter.type,
                sort: this.filter.sort,
                order: this.filter.order,
            });

            const response = await fetch(`/tinyfinder/api/files?${params}`);
            const data = await response.json();
            this.files = data.files;
        } catch (error) {
            console.error('Failed to load files:', error);
            this.$dispatch('notify', {
                type: 'error',
                message: 'Failed to load files',
            });
        }
    },

    selectFile(file) {
        this.selectedFile = file;
        this.$dispatch('file-selected', file);
    },

    async deleteFile(fileId) {
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }

        try {
            await fetch(`/tinyfinder/api/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            this.files = this.files.filter(f => f.id !== fileId);
            if (this.selectedFile?.id === fileId) {
                this.selectedFile = null;
            }

            this.$dispatch('notify', {
                type: 'success',
                message: 'File deleted successfully',
            });
        } catch (error) {
            console.error('Failed to delete file:', error);
            this.$dispatch('notify', {
                type: 'error',
                message: 'Failed to delete file',
            });
        }
    },

    async handleFileUpload(event) {
        const files = Array.from(event.target.files || event.dataTransfer?.files || []);

        if (!files.length) return;

        this.uploading = true;
        this.uploadProgress = 0;

        const formData = new FormData();
        files.forEach(file => formData.append('files[]', file));

        try {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    this.files = [...response.files, ...this.files];
                    this.$dispatch('notify', {
                        type: 'success',
                        message: `${files.length} file(s) uploaded successfully`,
                    });
                }
                this.uploading = false;
                this.uploadProgress = 0;
            });

            xhr.addEventListener('error', () => {
                this.$dispatch('notify', {
                    type: 'error',
                    message: 'Upload failed',
                });
                this.uploading = false;
                this.uploadProgress = 0;
            });

            xhr.open('POST', '/tinyfinder/api/upload');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
            xhr.send(formData);
        } catch (error) {
            console.error('Upload error:', error);
            this.uploading = false;
            this.uploadProgress = 0;
        }
    },

    /**
     * MODERN CLIPBOARD API - NO FLASH!
     * Copy file URL to clipboard using native browser API
     */
    async copyToClipboard(url) {
        try {
            // Use modern Clipboard API
            await navigator.clipboard.writeText(url);

            this.$dispatch('notify', {
                type: 'success',
                message: 'URL copied to clipboard!',
            });

            // Visual feedback
            this.$dispatch('clipboard-success');
        } catch (error) {
            // Fallback for older browsers
            this.copyToClipboardFallback(url);
        }
    },

    /**
     * Fallback clipboard method for older browsers
     */
    copyToClipboardFallback(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            this.$dispatch('notify', {
                type: 'success',
                message: 'URL copied to clipboard!',
            });
        } catch (error) {
            this.$dispatch('notify', {
                type: 'error',
                message: 'Failed to copy URL',
            });
        }

        document.body.removeChild(textArea);
    },

    toggleView() {
        this.view = this.view === 'grid' ? 'list' : 'grid';
    },

    get filteredFiles() {
        let filtered = this.files;

        if (this.filter.search) {
            const search = this.filter.search.toLowerCase();
            filtered = filtered.filter(file =>
                file.name.toLowerCase().includes(search)
            );
        }

        if (this.filter.type !== 'all') {
            filtered = filtered.filter(file => file.type === this.filter.type);
        }

        return filtered;
    },
}));

// Image Cropper Component
Alpine.data('tinyFinderCropper', (fileUrl, fileId) => ({
    cropper: null,
    cropData: null,
    saving: false,

    init() {
        this.$nextTick(() => {
            if (typeof window.Cropper !== 'function') {
                this.$dispatch('notify', {
                    type: 'error',
                    message: 'Image cropper library is not loaded',
                });

                return;
            }

            const image = this.$refs.cropImage;
            this.cropper = new window.Cropper(image, {
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                modal: true,
                guides: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        });
    },

    async saveCrop() {
        if (!this.cropper) return;

        this.saving = true;
        const cropData = this.cropper.getData(true);

        try {
            const response = await fetch(`/tinyfinder/api/files/${fileId}/crop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    x: Math.round(cropData.x),
                    y: Math.round(cropData.y),
                    width: Math.round(cropData.width),
                    height: Math.round(cropData.height),
                }),
            });

            if (response.ok) {
                this.$dispatch('notify', {
                    type: 'success',
                    message: 'Image cropped successfully',
                });
                this.$dispatch('file-updated');
                this.$dispatch('close');
            }
        } catch (error) {
            console.error('Crop error:', error);
            this.$dispatch('notify', {
                type: 'error',
                message: 'Failed to crop image',
            });
        } finally {
            this.saving = false;
        }
    },

    destroy() {
        if (this.cropper) {
            this.cropper.destroy();
        }
    },
}));

// File Preview Component
Alpine.data('tinyFinderPreview', (file) => ({
    file: file,
    loading: true,

    init() {
        setTimeout(() => {
            this.loading = false;
        }, 500);
    },

    get isImage() {
        return this.file.type === 'image';
    },

    get previewUrl() {
        return this.isImage && this.file.thumbnail_url
            ? this.file.thumbnail_url
            : this.file.url;
    },
}));

// Drag & Drop Upload
Alpine.directive('dropzone', (el, { expression }, { evaluate }) => {
    const handler = evaluate(expression);

    el.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        el.classList.add('dragover');
    });

    el.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        el.classList.remove('dragover');
    });

    el.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        el.classList.remove('dragover');
        handler(e);
    });
});

document.addEventListener('mouseover', (event) => {
    const input = event.target?.matches?.('input') ? event.target : null;

    if (! input) {
        return;
    }

    const field = input.closest('.fi-fo-field-wrp') || input.closest('[wire\\:key]') || input.parentElement;
    const preview = field?.querySelector('.tinyfinder-image-preview');

    if (! preview) {
        return;
    }

    const rect = input.getBoundingClientRect();

    preview.style.left = `${Math.max(8, rect.left)}px`;
    preview.style.top = `${rect.bottom + 8}px`;
    preview.style.position = 'fixed';
    preview.style.zIndex = '9999';
    preview.style.pointerEvents = 'none';
    preview.style.display = 'block';
});

    document.addEventListener('mouseout', (event) => {
        const input = event.target?.matches?.('input') ? event.target : null;

        if (! input) {
        return;
    }

    const field = input.closest('.fi-fo-field-wrp') || input.closest('[wire\\:key]') || input.parentElement;
    const preview = field?.querySelector('.tinyfinder-image-preview');

    if (preview) {
            preview.style.display = 'none';
        }
    });

document.addEventListener('mouseover', (event) => {
    const option = event.target?.closest?.('.tinyfinder-archive-option[data-preview-type="image"][data-preview-url]');

    if (! option) {
        return;
    }

    let preview = document.querySelector('.tinyfinder-archive-hover-preview');

    if (! preview) {
        preview = document.createElement('div');
        preview.className = 'tinyfinder-archive-hover-preview';
        preview.innerHTML = '<img alt="">';
        document.body.appendChild(preview);
    }

    const image = preview.querySelector('img');
    const previewUrl = option.dataset.previewUrl;

    if (image.getAttribute('src') !== previewUrl) {
        image.setAttribute('src', previewUrl);
    }

    const rect = option.getBoundingClientRect();
    const left = Math.min(window.innerWidth - 208, rect.right + 12);
    const top = Math.min(window.innerHeight - 208, Math.max(8, rect.top));

    preview.style.left = `${Math.max(8, left)}px`;
    preview.style.top = `${top}px`;
    preview.style.display = 'block';
});

document.addEventListener('mousedown', (event) => {
    if (event.target?.closest?.('[data-tinyfinder-archive-action]')) {
        event.preventDefault();
        event.stopPropagation();
    }
}, true);

document.addEventListener('click', (event) => {
    if (event.target?.closest?.('[data-tinyfinder-archive-action]')) {
        return;
    }

    const option = event.target?.closest?.('.tinyfinder-archive-option');

    if (! option) {
        return;
    }

    submitTinyFinderArchiveModal(option);
}, true);

document.addEventListener('click', async (event) => {
    const button = event.target?.closest?.('[data-tinyfinder-archive-action]');

    if (! button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const option = button.closest('.tinyfinder-archive-option');
    const fileId = option?.dataset.fileId;
    const action = button.dataset.tinyfinderArchiveAction;

    if (! fileId) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const request = async (url, payload = {}, method = 'POST') => {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: method === 'GET' ? undefined : JSON.stringify(payload),
        });

        if (! response.ok) {
            throw new Error('TinyFinder request failed');
        }

        return response.json();
    };

    try {
        if (action === 'copy') {
            await window.navigator.clipboard.writeText(withoutTinyFinderRevision(option.dataset.fileUrl || ''));
        }

        if (action === 'rename') {
            const name = window.prompt('Rename', option.dataset.fileName || '');

            if (! name) {
                return;
            }

            const data = await request(`/tinyfinder/archive/files/${fileId}/rename`, { name });
            option.dataset.fileName = data.name;
            option.querySelector('.tinyfinder-archive-option-name').textContent = data.name;
        }

        if (action === 'resize') {
            const currentDimensions = option.querySelector('.tinyfinder-archive-option-meta')?.textContent?.match(/(\d+)\s*x\s*(\d+)/i);
            const currentWidth = option.dataset.width || currentDimensions?.[1] || '';
            const currentHeight = option.dataset.height || currentDimensions?.[2] || '';
            const width = window.prompt('Width', currentWidth);

            if (width === null) {
                return;
            }

            const height = window.prompt('Height', currentHeight);

            if (height === null) {
                return;
            }

            if (! width && ! height) {
                return;
            }

            const data = await request(`/tinyfinder/archive/files/${fileId}/resize`, { width, height });

            reviseTinyFinderOptionUrls(option, data);

            if (data.width && data.height) {
                option.dataset.width = data.width;
                option.dataset.height = data.height;

                const meta = option.querySelector('.tinyfinder-archive-option-meta');

                if (meta) {
                    meta.textContent = meta.textContent.match(/^\d+x\d+/)
                        ? meta.textContent.replace(/^\d+x\d+/, `${data.width}x${data.height}`)
                        : `${data.width}x${data.height} - ${meta.textContent}`;
                }
            }
        }

        if (action === 'crop') {
            const cropData = await openTinyFinderCropModal(option.dataset.fileUrl || option.dataset.previewUrl || '');

            if (! cropData) {
                return;
            }

            const data = await request(`/tinyfinder/archive/files/${fileId}/crop`, cropData);
            reviseTinyFinderOptionUrls(option, data);

            if (data.width && data.height) {
                option.dataset.width = data.width;
                option.dataset.height = data.height;

                const meta = option.querySelector('.tinyfinder-archive-option-meta');

                if (meta) {
                    meta.textContent = meta.textContent.replace(/^\d+x\d+/, `${data.width}x${data.height}`);
                }
            }
        }

        if (action === 'delete') {
            if (! window.confirm('Delete this file?')) {
                return;
            }

            await request(`/tinyfinder/archive/files/${fileId}`, {}, 'DELETE');
            clearTinyFinderFieldIfSelected(option);
            option.closest('[data-choice]')?.remove();
            option.remove();
        }
    } catch (error) {
        console.error(error);
        window.alert('TinyFinder action failed.');
    }
}, true);

document.addEventListener('mouseout', (event) => {
    const option = event.target?.closest?.('.tinyfinder-archive-option[data-preview-type="image"]');

    if (! option || option.contains(event.relatedTarget)) {
        return;
    }

    const preview = document.querySelector('.tinyfinder-archive-hover-preview');

    if (preview) {
        preview.style.display = 'none';
    }
});

document.addEventListener('click', (event) => {
    if (! event.target?.closest?.('.tinyfinder-archive-option')) {
        hideTinyFinderArchivePreview();

        return;
    }

    setTimeout(hideTinyFinderArchivePreview, 0);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' || event.key === 'Enter') {
        hideTinyFinderArchivePreview();
    }
});

window.addEventListener('scroll', hideTinyFinderArchivePreview, true);

    };

    document.addEventListener('alpine:init', () => {
        registerTinyFinder(window.Alpine);
    });

    registerTinyFinder(window.Alpine);
})();
