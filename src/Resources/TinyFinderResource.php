<?php

namespace Stemizer\FilamentTinyFinder\Resources;

use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Stemizer\FilamentTinyFinder\Models\TinyFinderFile;
use Stemizer\FilamentTinyFinder\Resources\TinyFinderResource\Pages;
use Stemizer\FilamentTinyFinder\Services\FileUploadService;
use Stemizer\FilamentTinyFinder\Services\ImageProcessingService;

class TinyFinderResource extends Resource
{
    protected static ?string $model = TinyFinderFile::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('filament-tinyfinder::tinyfinder.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-tinyfinder::tinyfinder.type_file');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-tinyfinder::tinyfinder.type_files');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('filament-tinyfinder::tinyfinder.form_file_info'))
                    ->schema([
                        Forms\Components\FileUpload::make('upload')
                            ->label(__('filament-tinyfinder::tinyfinder.type_file'))
                            ->required()
                            ->storeFiles(false)
                            ->maxSize(fn () => (int) ceil(max(
                                config('filament-tinyfinder.uploads.max_file_size', 128 * 1024 * 1024),
                                config('filament-tinyfinder.uploads.max_image_size', 10 * 1024 * 1024),
                            ) / 1024))
                            ->visibleOn('create'),

                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-tinyfinder::tinyfinder.column_name'))
                            ->required()
                            ->maxLength(255)
                            ->hiddenOn('create'),

                        Forms\Components\Select::make('type')
                            ->label(__('filament-tinyfinder::tinyfinder.column_type'))
                            ->options([
                                'image' => __('filament-tinyfinder::tinyfinder.type_image'),
                                'file' => __('filament-tinyfinder::tinyfinder.type_file'),
                            ])
                            ->required()
                            ->disabled()
                            ->hiddenOn('create'),

                        Forms\Components\TextInput::make('extension')
                            ->label(__('filament-tinyfinder::tinyfinder.column_extension'))
                            ->disabled()
                            ->hiddenOn('create'),

                        Forms\Components\TextInput::make('mime_type')
                            ->label(__('filament-tinyfinder::tinyfinder.form_mime_type'))
                            ->disabled()
                            ->hiddenOn('create'),

                        Forms\Components\TextInput::make('size')
                            ->label(__('filament-tinyfinder::tinyfinder.column_size'))
                            ->formatStateUsing(fn ($state) => static::formatFileSize($state))
                            ->disabled()
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Section::make(__('filament-tinyfinder::tinyfinder.form_image_dimensions'))
                    ->schema([
                        Forms\Components\TextInput::make('width')
                            ->label(__('filament-tinyfinder::tinyfinder.text_width'))
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('height')
                            ->label(__('filament-tinyfinder::tinyfinder.text_height'))
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->type === 'image')
                    ->hiddenOn('create'),

                Section::make(__('filament-tinyfinder::tinyfinder.form_settings'))
                    ->schema([
                        Forms\Components\Toggle::make('is_private')
                            ->label(__('filament-tinyfinder::tinyfinder.form_private_file'))
                            ->helperText(__('filament-tinyfinder::tinyfinder.text_private_upload_title')),

                        Forms\Components\Toggle::make('has_thumbnails')
                            ->label(__('filament-tinyfinder::tinyfinder.form_has_thumbnails'))
                            ->disabled()
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Section::make(__('filament-tinyfinder::tinyfinder.form_preview'))
                    ->schema([
                        Forms\Components\Placeholder::make('preview')
                            ->content(fn ($record) => $record ? view('filament-tinyfinder::file-preview', ['file' => $record]) : null),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label(__('filament-tinyfinder::tinyfinder.column_preview'))
                    ->circular()
                    ->defaultImageUrl(url('/images/file-placeholder.png'))
                    ->size(60),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-tinyfinder::tinyfinder.column_name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->basename),

                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('filament-tinyfinder::tinyfinder.column_type'))
                    ->formatStateUsing(fn (string $state): string => __("filament-tinyfinder::tinyfinder.type_{$state}"))
                    ->colors([
                        'primary' => 'image',
                        'gray' => 'file',
                    ])
                    ->icons([
                        'heroicon-o-photo' => 'image',
                        'heroicon-o-document' => 'file',
                    ]),

                Tables\Columns\TextColumn::make('extension')
                    ->label(__('filament-tinyfinder::tinyfinder.column_extension'))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label(__('filament-tinyfinder::tinyfinder.column_size'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('size', $direction)),

                Tables\Columns\IconColumn::make('has_thumbnails')
                    ->boolean()
                    ->label(__('filament-tinyfinder::tinyfinder.column_thumbs'))
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_private')
                    ->boolean()
                    ->label(__('filament-tinyfinder::tinyfinder.column_private'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('filament-tinyfinder::tinyfinder.column_owner'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-tinyfinder::tinyfinder.column_created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament-tinyfinder::tinyfinder.filter_type'))
                    ->options([
                        'image' => __('filament-tinyfinder::tinyfinder.type_images'),
                        'file' => __('filament-tinyfinder::tinyfinder.type_files'),
                    ]),

                Tables\Filters\SelectFilter::make('extension')
                    ->label(__('filament-tinyfinder::tinyfinder.filter_extension'))
                    ->options(fn () => TinyFinderFile::pluck('extension', 'extension')->unique()->toArray()),

                Tables\Filters\TernaryFilter::make('is_private')
                    ->label(__('filament-tinyfinder::tinyfinder.filter_privacy'))
                    ->placeholder(__('filament-tinyfinder::tinyfinder.filter_all_files'))
                    ->trueLabel(__('filament-tinyfinder::tinyfinder.filter_private_only'))
                    ->falseLabel(__('filament-tinyfinder::tinyfinder.filter_public_only')),

                Tables\Filters\Filter::make('my_files')
                    ->label(__('filament-tinyfinder::tinyfinder.filter_my_files'))
                    ->query(fn (Builder $query) => $query->where('user_id', auth()->id())),
            ])
            ->actions([
                Actions\Action::make('download')
                    ->label(__('filament-tinyfinder::tinyfinder.action_download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),

                Actions\Action::make('copy_url')
                    ->label(__('filament-tinyfinder::tinyfinder.action_copy_url'))
                    ->icon('heroicon-o-clipboard')
                    ->action(function ($record) {
                        // This will be handled by Alpine.js Clipboard API
                        return redirect()->back();
                    })
                    ->requiresConfirmation(false),

                Actions\EditAction::make(),

                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),

                    Actions\BulkAction::make('make_private')
                        ->label(__('filament-tinyfinder::tinyfinder.action_make_private'))
                        ->icon('heroicon-o-lock-closed')
                        ->action(fn ($records) => $records->each->update(['is_private' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('make_public')
                        ->label(__('filament-tinyfinder::tinyfinder.action_make_public'))
                        ->icon('heroicon-o-lock-open')
                        ->action(fn ($records) => $records->each->update(['is_private' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTinyFinderFiles::route('/'),
            'create' => Pages\CreateTinyFinderFile::route('/create'),
            'edit' => Pages\EditTinyFinderFile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query) {
                $query->where('is_private', false)
                    ->orWhere('user_id', auth()->id());
            });
    }

    protected static function formatFileSize(?int $bytes): string
    {
        if ($bytes === null) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
