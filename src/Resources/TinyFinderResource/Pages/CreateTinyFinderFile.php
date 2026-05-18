<?php

namespace Stemizer\FilamentTinyFinder\Resources\TinyFinderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Stemizer\FilamentTinyFinder\Resources\TinyFinderResource;
use Stemizer\FilamentTinyFinder\Services\FileUploadService;

class CreateTinyFinderFile extends CreateRecord
{
    protected static string $resource = TinyFinderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $file = Arr::first(Arr::wrap($data['upload'] ?? null));

        if (! $file instanceof UploadedFile) {
            throw new \InvalidArgumentException('A file must be uploaded.');
        }

        return app(FileUploadService::class)->upload($file, [
            'is_private' => (bool) ($data['is_private'] ?? false),
        ]);
    }
}
