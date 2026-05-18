<?php

namespace Stemizer\FilamentTinyFinder\Examples\ExampleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Stemizer\FilamentTinyFinder\Examples\ExampleResource;

class EditExample extends EditRecord
{
    protected static string $resource = ExampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
