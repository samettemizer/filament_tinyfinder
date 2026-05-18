<?php

namespace Stemizer\FilamentTinyFinder\Forms\Components;

class TinyFinderFileInput extends BaseTinyFinderInput
{
    protected string $tinyFinderType = 'file';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->createThumbnails(false)
            ->helperText(fn (?string $state) => $this->previewHelper($state));
    }
}
