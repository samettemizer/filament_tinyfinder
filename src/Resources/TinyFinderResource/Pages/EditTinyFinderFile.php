<?php

namespace Stemizer\FilamentTinyFinder\Resources\TinyFinderResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Stemizer\FilamentTinyFinder\Resources\TinyFinderResource;

class EditTinyFinderFile extends EditRecord
{
    protected static string $resource = TinyFinderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
