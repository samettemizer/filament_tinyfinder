<?php

namespace Stemizer\FilamentTinyFinder\Forms\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;
use Stemizer\FilamentTinyFinder\Models\TinyFinderFile;
use Stemizer\FilamentTinyFinder\Services\FileUploadService;

abstract class BaseTinyFinderInput extends TextInput
{
    protected string $tinyFinderType = 'file';

    protected bool $shouldCreateThumbnails = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->readOnly()
            ->extraInputAttributes([
                'class' => 'tinyfinder-input-trigger',
            ], merge: true)
            ->placeholder('Upload a new file or choose from archive')
            ->afterStateHydrated(function (TextInput $component, ?string $state): void {
                if (blank($state)) {
                    return;
                }

                $storedValue = $this->getPublicPath($state);

                $component->state($this->getDisplayName($storedValue));
                $component->extraInputAttributes([
                    'class' => 'tinyfinder-input-trigger',
                    'data-tinyfinder-stored-value' => $storedValue,
                    'title' => $storedValue,
                ], merge: true);
            })
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $this->resolveStoredValue($state) : $state)
            ->suffixActions([
                $this->makeCopyAction(),
                $this->makeUploadAction(),
                $this->makeArchiveAction(),
                $this->makeOpenAction(),
            ], isInline: true);
    }

    public function createThumbnails(bool $condition = true): static
    {
        $this->shouldCreateThumbnails = $condition;

        return $this;
    }

    protected function makeCopyAction(): Action
    {
        return Action::make('tinyfinder_copy')
            ->label('Copy')
            ->icon('heroicon-o-clipboard-document')
            ->visible(fn (?string $state): bool => filled($state))
            ->alpineClickHandler(function (?string $state): string {
                $copyableState = Js::from(filled($state) ? $this->getPreviewUrl($this->resolveStoredValue($state)) : '');
                $copyMessage = Js::from('Copied');

                return <<<JS
                    (async () => {
                        const value = {$copyableState};
                        const showTooltip = () => {
                            if (typeof \$tooltip === 'function') {
                                \$tooltip({$copyMessage}, {
                                    theme: \$store.theme,
                                    timeout: 2000,
                                });
                            }
                        };
                        const fallbackCopy = () => {
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
                        };

                        try {
                            if (window.navigator?.clipboard?.writeText) {
                                await window.navigator.clipboard.writeText(value);
                            } else {
                                fallbackCopy();
                            }

                            showTooltip();
                        } catch (error) {
                            fallbackCopy();
                            showTooltip();
                        }
                    })()
                    JS;
            });
    }

    protected function makeUploadAction(): Action
    {
        return Action::make('tinyfinder_upload')
            ->label('Upload')
            ->icon('heroicon-o-arrow-up-tray')
            ->extraAttributes(['class' => 'tinyfinder-upload-action'])
            ->schema([
                $this->configureUploadField(
                    FileUpload::make('file')
                        ->label($this->tinyFinderType === 'image' ? 'Image' : 'File')
                        ->required()
                        ->storeFiles(false)
                        ->maxSize($this->getMaxUploadSizeInKilobytes())
                ),
            ])
            ->modalHeading($this->tinyFinderType === 'image' ? 'Upload Image' : 'Upload File')
            ->modalSubmitActionLabel('Use uploaded file')
            ->modalCancelAction(false)
            ->extraModalFooterActions([
                Action::make('tinyfinder_search_archive')
                    ->label('Search in archive')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray')
                    ->alpineClickHandler(<<<'JS'
                        window.tinyFinderOpenActiveArchive?.()
                        JS),
            ])
            ->action(function (array $data, Set $set, FileUploadService $uploadService): void {
                $file = Arr::first(Arr::wrap($data['file'] ?? null));

                if (! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        'file' => 'Please choose a file to upload.',
                    ]);
                }

                $record = $uploadService->upload($file, [
                    'create_thumbnails' => $this->tinyFinderType === 'image' && $this->shouldCreateThumbnails,
                ]);

                if ($record->type !== $this->tinyFinderType) {
                    $record->delete();

                    throw ValidationException::withMessages([
                        'file' => $this->tinyFinderType === 'image'
                            ? 'Please upload an image file.'
                            : 'Please upload a non-image file.',
                    ]);
                }

                $set($this->getName(), $record->name);

                Notification::make()
                    ->success()
                    ->title('File selected')
                    ->body($record->name)
                    ->send();
            });
    }

    protected function makeArchiveAction(): Action
    {
        return Action::make('tinyfinder_archive')
            ->label('Archive')
            ->icon('heroicon-o-archive-box')
            ->extraAttributes(['class' => 'tinyfinder-archive-action'])
            ->schema([
                Select::make('file_id')
                    ->label($this->tinyFinderType === 'image' ? 'Choose Image' : 'Choose File')
                    ->required()
                    ->placeholder('Select an item')
                    ->autofocus()
                    ->searchable()
                    ->allowHtml()
                    ->optionsLimit(5)
                    ->extraAttributes(['class' => 'tinyfinder-archive-select'], merge: true)
                    ->options(fn (): array => $this->getArchiveOptions())
                    ->getSearchResultsUsing(fn (string $search): array => $this->getArchiveOptions($search))
                    ->getOptionLabelUsing(fn ($value): ?string => ($file = TinyFinderFile::find($value)) ? $this->getArchiveOptionLabel($file) : null),
            ])
            ->modalHeading('TinyFinder')
            ->extraModalWindowAttributes(['class' => 'tinyfinder-archive-modal'], merge: true)
            ->modalCloseButton(false)
            ->modalSubmitActionLabel('Use selected file')
            ->modalSubmitAction(fn (Action $action): Action => $action->extraAttributes([
                'class' => 'tinyfinder-archive-submit-hidden',
            ])->color('gray'))
            ->extraModalFooterActions([
                Action::make('tinyfinder_new_upload')
                    ->label('New Upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->extraAttributes(['class' => 'tinyfinder-new-upload-action'], merge: true)
                    ->alpineClickHandler(<<<'JS'
                        window.tinyFinderOpenActiveUpload?.()
                        JS),
            ])
            ->action(function (array $data, Set $set): void {
                $record = TinyFinderFile::query()
                    ->whereKey($data['file_id'] ?? null)
                    ->where('type', $this->tinyFinderType)
                    ->first();

                if (! $record) {
                    throw ValidationException::withMessages([
                        'file_id' => 'Please choose a file from the archive.',
                    ]);
                }

                $set($this->getName(), $record->name);
            });
    }

    protected function makeOpenAction(): Action
    {
        return Action::make('tinyfinder_open')
            ->label('Open')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->url(fn (?string $state): ?string => filled($state) ? $this->getPreviewUrl($this->resolveStoredValue($state)) : null, shouldOpenInNewTab: true)
            ->visible(fn (?string $state): bool => filled($state));
    }

    protected function configureUploadField(FileUpload $field): FileUpload
    {
        if ($this->tinyFinderType === 'image') {
            return $field
                ->image()
                ->imagePreviewHeight('180');
        }

        return $field;
    }

    protected function getArchiveOptions(?string $search = null): array
    {
        return TinyFinderFile::query()
            ->where('type', $this->tinyFinderType)
            ->when(filled($search), fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->limit(5)
            ->get()
            ->mapWithKeys(fn (TinyFinderFile $file): array => [
                $file->getKey() => $this->getArchiveOptionLabel($file),
            ])
            ->all();
    }

    protected function getMaxUploadSizeInKilobytes(): int
    {
        $key = $this->tinyFinderType === 'image' ? 'max_image_size' : 'max_file_size';

        return (int) ceil(config("filament-tinyfinder.uploads.{$key}", 10 * 1024 * 1024) / 1024);
    }

    protected function previewHelper(?string $state): ?HtmlString
    {
        if (blank($state)) {
            return null;
        }

        $storedValue = $this->resolveStoredValue($state);
        $url = e($this->getPreviewUrl($storedValue));
        $displayMarker = $this->displayNameMarker($state);

        if ($this->tinyFinderType !== 'image') {
            return new HtmlString("{$displayMarker}<a href=\"{$url}\" target=\"_blank\" class=\"text-sm underline\">Open selected file</a>");
        }

        $previewId = 'tinyfinder-image-preview-' . md5($this->getName() . '|' . $state);

        return new HtmlString(
            <<<HTML
            {$displayMarker}
            <div id="{$previewId}" class="tinyfinder-image-preview rounded-lg border border-gray-200 bg-white p-1 shadow-xl ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10" style="display: none; position: fixed; z-index: 9999; pointer-events: none;">
                <img src="{$url}" alt="Preview" class="block max-w-xs rounded-md object-contain" style="max-height: 300px;" />
            </div>
            <script>
                (() => {
                    const preview = document.getElementById('{$previewId}');

                    if (! preview || preview.dataset.tinyfinderBound === '1') {
                        return;
                    }

                    const field = preview.closest('.fi-fo-field-wrp') || preview.closest('[wire\\\\:key]') || preview.parentElement;
                    const input = field?.querySelector('input');

                    if (! input) {
                        return;
                    }

                    preview.dataset.tinyfinderBound = '1';

                    const showPreview = () => {
                        const rect = input.getBoundingClientRect();

                        preview.style.left = Math.max(8, rect.left) + 'px';
                        preview.style.top = (rect.bottom + 8) + 'px';
                        preview.style.display = 'block';
                    };

                    const hidePreview = () => {
                        preview.style.display = 'none';
                    };

                    input.addEventListener('mouseenter', showPreview);
                    input.addEventListener('mousemove', showPreview);
                    input.addEventListener('mouseleave', hidePreview);
                    input.addEventListener('blur', hidePreview);
                    window.addEventListener('scroll', hidePreview, true);
                })();
            </script>
            HTML
        );
    }

    protected function displayNameMarker(string $state): string
    {
        $storedValue = $this->resolveStoredValue($state);
        $displayName = e($this->getDisplayName($storedValue));
        $storedValue = e($storedValue);

        return "<span class=\"tinyfinder-display-name\" data-display-name=\"{$displayName}\" data-stored-value=\"{$storedValue}\" hidden></span>";
    }

    protected function getDisplayName(string $state): string
    {
        return $this->getFileRecordForState($state)?->name ?: basename($this->getPublicPath($state));
    }

    protected function resolveStoredValue(string $state): string
    {
        $file = TinyFinderFile::query()
            ->where('name', $state)
            ->orWhere('basename', basename(parse_url($state, PHP_URL_PATH) ?: $state))
            ->first();

        return $file ? $this->getPublicPath($file->url) : $this->getPublicPath($state);
    }

    protected function getArchiveOptionLabel(TinyFinderFile $file): string
    {
        $name = e($file->name);
        $meta = e($file->formatted_size);
        $extension = e($file->extension);
        $publicPath = e($this->getPublicPath($file->url));
        $fileUrl = e($this->getPreviewUrl($this->getPublicPath($file->url)));
        $previewUrl = e($this->getPreviewUrl($this->getPublicPath($file->thumbnail_url ?? $file->url)));

        if ($file->type === 'image') {
            $dimensions = ($file->width && $file->height) ? e("{$file->width}x{$file->height}") : null;
            $meta = $dimensions ? "{$dimensions} - {$meta}" : $meta;
            $width = e((string) ($file->width ?? ''));
            $height = e((string) ($file->height ?? ''));

            return <<<HTML
                <div class="tinyfinder-archive-option" data-file-id="{$file->getKey()}" data-file-name="{$name}" data-file-path="{$publicPath}" data-file-url="{$fileUrl}" data-preview-type="image" data-preview-url="{$previewUrl}" data-width="{$width}" data-height="{$height}">
                    <div class="tinyfinder-archive-option-icon">IMG</div>
                    <div class="tinyfinder-archive-option-body">
                        <div class="tinyfinder-archive-option-name">{$name}</div>
                        <div class="tinyfinder-archive-option-meta">{$meta}</div>
                    </div>
                    <div class="tinyfinder-archive-option-actions">
                        <button type="button" data-tinyfinder-archive-action="copy" title="Copy URL">☍</button>
                        <button type="button" data-tinyfinder-archive-action="rename" title="Rename">✎</button>
                        <button type="button" data-tinyfinder-archive-action="resize" title="Resize">⚙</button>
                        <button type="button" data-tinyfinder-archive-action="crop" title="Crop">✂</button>
                        <button type="button" data-tinyfinder-archive-action="delete" title="Delete">🗑</button>
                    </div>
                </div>
                HTML;
        }

        return <<<HTML
            <div class="tinyfinder-archive-option" data-file-id="{$file->getKey()}" data-file-name="{$name}" data-file-path="{$publicPath}" data-file-url="{$fileUrl}" data-preview-type="file" data-preview-url="{$fileUrl}">
                <div class="tinyfinder-archive-option-icon">{$extension}</div>
                <div class="tinyfinder-archive-option-body">
                    <div class="tinyfinder-archive-option-name">{$name}</div>
                    <div class="tinyfinder-archive-option-meta">{$meta}</div>
                </div>
                <div class="tinyfinder-archive-option-actions">
                    <button type="button" data-tinyfinder-archive-action="copy" title="Copy URL">☍</button>
                    <button type="button" data-tinyfinder-archive-action="rename" title="Rename">✎;</button>
                    <button type="button" data-tinyfinder-archive-action="delete" title="Delete">🗑</button>
                </div>
            </div>
            HTML;
    }

    protected function getFileRecordForState(string $state): ?TinyFinderFile
    {
        $basename = basename(parse_url($state, PHP_URL_PATH) ?: $state);

        if ($basename === '' || $basename === '.' || $basename === '/') {
            return null;
        }

        return TinyFinderFile::query()
            ->where('basename', $basename)
            ->first();
    }

    protected function getPublicPath(string $url): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '' && ($url === $appUrl || str_starts_with($url, "{$appUrl}/"))) {
            return '/' . ltrim(substr($url, strlen($appUrl)), '/');
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return '/' . ltrim($url, '/');
    }

    protected function getPreviewUrl(string $state): string
    {
        if (filter_var($state, FILTER_VALIDATE_URL)) {
            return $state;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($state, '/');
    }
}
