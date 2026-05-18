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

    protected static ?string $navigationLabel = 'File Manager';

    protected static ?string $modelLabel = 'File';

    protected static ?string $pluralModelLabel = 'Files';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('File Information')
                    ->schema([
                        Forms\Components\FileUpload::make('upload')
                            ->label('File')
                            ->required()
                            ->storeFiles(false)
                            ->maxSize(fn () => (int) ceil(max(
                                config('filament-tinyfinder.uploads.max_file_size', 128 * 1024 * 1024),
                                config('filament-tinyfinder.uploads.max_image_size', 10 * 1024 * 1024),
                            ) / 1024))
                            ->visibleOn('create'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->hiddenOn('create'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'image' => 'Image',
                                'file' => 'File',
                            ])
                            ->required()
                            ->disabled()
                            ->hiddenOn('create'),

                        Forms\Components\TextInput::make('extension')
                            ->disabled()
                            ->hiddenOn('create'),

                        Forms\Components\TextInput::make('mime_type')
                            ->label('MIME Type')
                            ->disabled()
                            ->hiddenOn('create'),

                        Forms\Components\TextInput::make('size')
                            ->formatStateUsing(fn ($state) => static::formatFileSize($state))
                            ->disabled()
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Section::make('Image Dimensions')
                    ->schema([
                        Forms\Components\TextInput::make('width')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('height')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->type === 'image')
                    ->hiddenOn('create'),

                Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_private')
                            ->label('Private File')
                            ->helperText('Only you can see this file'),

                        Forms\Components\Toggle::make('has_thumbnails')
                            ->label('Has Thumbnails')
                            ->disabled()
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Section::make('Preview')
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
                    ->label('Preview')
                    ->circular()
                    ->defaultImageUrl(url('/images/file-placeholder.png'))
                    ->size(60),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->basename),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'image',
                        'gray' => 'file',
                    ])
                    ->icons([
                        'heroicon-o-photo' => 'image',
                        'heroicon-o-document' => 'file',
                    ]),

                Tables\Columns\TextColumn::make('extension')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Size')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('size', $direction)),

                Tables\Columns\IconColumn::make('has_thumbnails')
                    ->boolean()
                    ->label('Thumbs')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_private')
                    ->boolean()
                    ->label('Private')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'image' => 'Images',
                        'file' => 'Files',
                    ]),

                Tables\Filters\SelectFilter::make('extension')
                    ->options(fn () => TinyFinderFile::pluck('extension', 'extension')->unique()->toArray()),

                Tables\Filters\TernaryFilter::make('is_private')
                    ->label('Privacy')
                    ->placeholder('All files')
                    ->trueLabel('Private only')
                    ->falseLabel('Public only'),

                Tables\Filters\Filter::make('my_files')
                    ->label('My Files')
                    ->query(fn (Builder $query) => $query->where('user_id', auth()->id())),
            ])
            ->actions([
                Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),

                Actions\Action::make('copy_url')
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
                        ->label('Make Private')
                        ->icon('heroicon-o-lock-closed')
                        ->action(fn ($records) => $records->each->update(['is_private' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('make_public')
                        ->label('Make Public')
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
