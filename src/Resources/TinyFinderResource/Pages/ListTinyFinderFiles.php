<?php

namespace Stemizer\FilamentTinyFinder\Resources\TinyFinderResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Stemizer\FilamentTinyFinder\Resources\TinyFinderResource;
use Stemizer\FilamentTinyFinder\Services\FileUploadService;

class ListTinyFinderFiles extends ListRecords
{
    protected static string $resource = TinyFinderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('upload')
                ->label(__('filament-tinyfinder::tinyfinder.button_upload_files'))
                ->icon('heroicon-o-cloud-arrow-up')
                ->form([
                    Forms\Components\FileUpload::make('files')
                        ->label(__('filament-tinyfinder::tinyfinder.type_files'))
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(config('filament-tinyfinder.uploads.max_file_size') / 1024)
                        ->acceptedFileTypes(array_merge(
                            array_map(fn ($ext) => "image/{$ext}", config('filament-tinyfinder.uploads.allowed_image_extensions')),
                            array_map(fn ($ext) => "application/{$ext}", config('filament-tinyfinder.uploads.allowed_file_extensions'))
                        ))
                        ->directory('temp')
                        ->visibility('private')
                        ->required(),

                    Forms\Components\Toggle::make('is_private')
                        ->label(__('filament-tinyfinder::tinyfinder.text_private_upload'))
                        ->default(false),

                    Forms\Components\Toggle::make('create_thumbnails')
                        ->label(__('filament-tinyfinder::tinyfinder.text_create_thumbs'))
                        ->default(true)
                        ->helperText(__('filament-tinyfinder::tinyfinder.text_create_thumbs_help')),
                ])
                ->action(function (array $data, FileUploadService $uploadService) {
                    $files = [];

                    foreach ($data['files'] ?? [] as $tempPath) {
                        $fullPath = Storage::disk('local')->path($tempPath);

                        if (file_exists($fullPath)) {
                            $uploadedFile = new \Illuminate\Http\UploadedFile(
                                $fullPath,
                                basename($tempPath),
                                mime_content_type($fullPath),
                                null,
                                true
                            );

                            try {
                                $file = $uploadService->upload($uploadedFile, [
                                    'is_private' => $data['is_private'] ?? false,
                                    'create_thumbnails' => $data['create_thumbnails'] ?? true,
                                ]);

                                $files[] = $file;

                                // Clean up temp file
                                Storage::disk('local')->delete($tempPath);
                            } catch (\Exception $e) {
                                // Log error but continue with other files
                                logger()->error('File upload error: ' . $e->getMessage());
                            }
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title(__('filament-tinyfinder::tinyfinder.alert_upload_success', ['count' => count($files)]))
                        ->send();
                })
                ->slideOver(),

            Actions\CreateAction::make(),
        ];
    }
}
