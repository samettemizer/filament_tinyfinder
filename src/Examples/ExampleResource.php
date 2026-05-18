<?php

namespace Stemizer\FilamentTinyFinder\Examples;

use App\Models\Product;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Stemizer\FilamentTinyFinder\Examples\ExampleResource\Pages;
use Stemizer\FilamentTinyFinder\Forms\Components\TinyFinderFileInput;
use Stemizer\FilamentTinyFinder\Forms\Components\TinyFinderImageInput;
use Stemizer\FilamentTinyFinder\Models\TinyFinderFile;
use Stemizer\FilamentTinyFinder\Services\FileUploadService;

class ExampleResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | \UnitEnum | null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Products';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Product')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255),

                        TinyFinderImageInput::make('image')
                            ->readOnly()
                            ->label('Product Image'),

                        TinyFinderFileInput::make('attachment')
                            ->readOnly()
                            ->label('Product Attachment'),

                        RichEditor::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->extraInputAttributes([
                                'style' => 'min-height: 24rem;',
                            ])
                            ->toolbarButtons([
                                ['tinyfinderInsertImage', 'attachFiles', 'table'],
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                ['h2', 'h3'],
                                ['alignStart', 'alignCenter', 'alignEnd'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->tools([
                                RichEditorTool::make('attachFiles')
                                    ->label('File Archive')
                                    ->action()
                                    ->activeJsExpression('false')
                                    ->icon(Heroicon::PaperClip)
                                    ->iconAlias('forms:components.rich-editor.toolbar.attach-files'),
                                RichEditorTool::make('tinyfinderInsertImage')
                                    ->label('Image Archive')
                                    ->action()
                                    ->activeJsExpression('false')
                                    ->icon(Heroicon::Photo),
                            ])
                            ->registerActions([
                                self::makeTinyFinderFileArchiveAction(),
                                self::makeTinyFinderImageArchiveAction(),
                            ]),
                    ]),
            ]);
    }

    protected static function makeTinyFinderFileArchiveAction(): Action
    {
        return self::makeTinyFinderArchiveAction(
            name: 'attachFiles',
            type: 'file',
            label: 'Choose from archive',
            onSelected: function (RichEditor $component, TinyFinderFile $file, ?array $editorSelection): void {
                $component->runCommands(
                    [
                        EditorCommand::make('insertContent', arguments: [[
                            'type' => 'text',
                            'text' => $file->name,
                            'marks' => [[
                                'type' => 'link',
                                'attrs' => [
                                    'href' => $file->url,
                                    'target' => '_blank',
                                ],
                            ]],
                        ]]),
                        EditorCommand::make('insertContent', arguments: [[
                            'type' => 'text',
                            'text' => ' ',
                        ]]),
                    ],
                    editorSelection: $editorSelection,
                );
            },
        );
    }

    protected static function makeTinyFinderImageArchiveAction(): Action
    {
        return self::makeTinyFinderArchiveAction(
            name: 'tinyfinderInsertImage',
            type: 'image',
            label: 'Choose from archive',
            onSelected: function (RichEditor $component, TinyFinderFile $file, ?array $editorSelection): void {
                $component->runCommands(
                    [
                        EditorCommand::make('insertContent', arguments: [[
                            'type' => 'image',
                            'attrs' => [
                                'alt' => $file->name,
                                'id' => self::toTinyFinderPublicPath($file->url),
                                'src' => $file->url,
                            ],
                        ]]),
                    ],
                    editorSelection: $editorSelection,
                );
            },
        );
    }

    protected static function makeTinyFinderArchiveAction(
        string $name,
        string $type,
        string $label,
        \Closure $onSelected,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->modalHeading('TinyFinder')
            ->modalWidth(Width::FourExtraLarge)
            ->extraModalWindowAttributes(['class' => 'tinyfinder-archive-modal'], merge: true)
            ->modalCloseButton(false)
            ->modalSubmitActionLabel('Use selected file')
            ->modalSubmitAction(fn (Action $action): Action => $action->color('gray'))
            ->extraModalFooterActions([
                self::makeTinyFinderUploadFromArchiveAction(
                    parentActionName: $name,
                    type: $type,
                    onUploaded: $onSelected,
                ),
            ])
            ->schema([
                \Filament\Forms\Components\Select::make('file_id')
                    ->label($type === 'image' ? 'Choose Image' : 'Choose File')
                    ->required()
                    ->placeholder('Select an item')
                    ->searchable()
                    ->allowHtml()
                    ->optionsLimit(5)
                    ->extraAttributes(['class' => 'tinyfinder-archive-select'], merge: true)
                    ->options(fn (): array => self::getTinyFinderArchiveOptions($type))
                    ->getSearchResultsUsing(fn (string $search): array => self::getTinyFinderArchiveOptions($type, $search))
                    ->getOptionLabelUsing(fn ($value): ?string => self::getTinyFinderArchiveOptionLabelById($value, $type)),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component) use ($onSelected, $type): void {
                $record = TinyFinderFile::query()
                    ->whereKey($data['file_id'] ?? null)
                    ->where('type', $type)
                    ->first();

                if (! $record) {
                    throw ValidationException::withMessages([
                        'file_id' => 'Please choose a file from the archive.',
                    ]);
                }

                $onSelected($component, $record, $arguments['editorSelection'] ?? null);
            });
    }

    protected static function makeTinyFinderUploadFromArchiveAction(
        string $parentActionName,
        string $type,
        \Closure $onUploaded,
    ): Action {
        return Action::make("tinyfinderNewUploadFromArchive{$parentActionName}")
            ->label('New Upload')
            ->icon(Heroicon::ArrowUpTray)
            ->color('gray')
            ->extraAttributes(['class' => 'tinyfinder-new-upload-action'], merge: true)
            ->modalHeading($type === 'image' ? 'Upload Image' : 'Upload File')
            ->modalWidth(Width::FourExtraLarge)
            ->schema([
                self::configureTinyFinderUploadField(
                    type: $type,
                    field: FileUpload::make('file')
                        ->label($type === 'image' ? 'Image' : 'File')
                        ->required()
                        ->storeFiles(false)
                        ->maxSize(self::getTinyFinderMaxUploadSizeInKilobytes($type)),
                ),
            ])
            ->cancelParentActions($parentActionName)
            ->action(function (
                array $data,
                array $mountedActions,
                RichEditor $component,
                FileUploadService $uploadService,
            ) use ($type, $onUploaded): void {
                $file = Arr::first(Arr::wrap($data['file'] ?? null));

                if (! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        'file' => 'Please choose a file to upload.',
                    ]);
                }

                $record = $uploadService->upload($file, [
                    'create_thumbnails' => $type === 'image',
                ]);

                if ($record->type !== $type) {
                    $record->delete();

                    throw ValidationException::withMessages([
                        'file' => $type === 'image'
                            ? 'Please upload an image file.'
                            : 'Please upload a non-image file.',
                    ]);
                }

                $parentArguments = [];

                if (isset($mountedActions[0]) && method_exists($mountedActions[0], 'getArguments')) {
                    $parentArguments = $mountedActions[0]->getArguments();
                }

                $onUploaded($component, $record, $parentArguments['editorSelection'] ?? null);
            });
    }

    protected static function configureTinyFinderUploadField(string $type, FileUpload $field): FileUpload
    {
        if ($type === 'image') {
            return $field
                ->image()
                ->imagePreviewHeight('180');
        }

        return $field;
    }

    protected static function getTinyFinderMaxUploadSizeInKilobytes(string $type): int
    {
        $key = $type === 'image' ? 'max_image_size' : 'max_file_size';

        return (int) ceil(config("filament-tinyfinder.uploads.{$key}", 10 * 1024 * 1024) / 1024);
    }

    /**
     * @return array<int, string>
     */
    protected static function getTinyFinderArchiveOptions(string $type, ?string $search = null): array
    {
        return TinyFinderFile::query()
            ->where('type', $type)
            ->when(filled($search), fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->limit(5)
            ->get()
            ->mapWithKeys(fn (TinyFinderFile $file): array => [
                $file->getKey() => self::buildTinyFinderArchiveOptionLabel($file),
            ])
            ->all();
    }

    protected static function getTinyFinderArchiveOptionLabelById(int | string | null $value, string $type): ?string
    {
        if (blank($value)) {
            return null;
        }

        $file = TinyFinderFile::query()
            ->whereKey($value)
            ->where('type', $type)
            ->first();

        if (! $file) {
            return null;
        }

        return self::buildTinyFinderArchiveOptionLabel($file);
    }

    protected static function buildTinyFinderArchiveOptionLabel(TinyFinderFile $file): string
    {
        $name = e($file->name);
        $meta = e($file->formatted_size);
        $extension = e($file->extension);
        $publicPath = e(self::toTinyFinderPublicPath($file->url));
        $fileUrl = e($file->url);
        $previewUrl = e($file->thumbnail_url ?? $file->url);

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
                        <button type="button" data-tinyfinder-archive-action="copy" title="Copy URL">Copy</button>
                        <button type="button" data-tinyfinder-archive-action="rename" title="Rename">Rename</button>
                        <button type="button" data-tinyfinder-archive-action="resize" title="Resize">Resize</button>
                        <button type="button" data-tinyfinder-archive-action="crop" title="Crop">Crop</button>
                        <button type="button" data-tinyfinder-archive-action="delete" title="Delete">Delete</button>
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
                    <button type="button" data-tinyfinder-archive-action="copy" title="Copy URL">Copy</button>
                    <button type="button" data-tinyfinder-archive-action="rename" title="Rename">Rename</button>
                    <button type="button" data-tinyfinder-archive-action="delete" title="Delete">Delete</button>
                </div>
            </div>
            HTML;
    }

    protected static function toTinyFinderPublicPath(string $url): string
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->checkFileExistence(false)
                    ->getStateUsing(fn (?Product $record): ?string => filled($record?->image) ? url($record->image) : null)
                    ->size(56),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attachment')
                    ->label('Attachment')
                    ->limit(48)
                    ->url(fn (?string $state): ?string => filled($state) ? url($state) : null, shouldOpenInNewTab: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamples::route('/'),
            'create' => Pages\CreateExample::route('/create'),
            'edit' => Pages\EditExample::route('/{record}/edit'),
        ];
    }
}
