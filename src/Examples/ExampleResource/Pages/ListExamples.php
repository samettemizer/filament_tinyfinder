<?php

namespace Stemizer\FilamentTinyFinder\Examples\ExampleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Stemizer\FilamentTinyFinder\Examples\ExampleResource;

class ListExamples extends ListRecords
{
    protected static string $resource = ExampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
