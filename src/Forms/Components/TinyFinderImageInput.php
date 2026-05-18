<?php

namespace Stemizer\FilamentTinyFinder\Forms\Components;

class TinyFinderImageInput extends BaseTinyFinderInput
{
    protected string $tinyFinderType = 'image';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->helperText(fn (?string $state) => $this->previewHelper($state));
    }
}
