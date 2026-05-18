<?php

namespace Stemizer\FilamentTinyFinder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Stemizer\FilamentTinyFinder\Resources\TinyFinderResource;
use UnitEnum;

class FilamentTinyFinderPlugin implements Plugin
{
    protected string | UnitEnum | null $navigationGroup = null;

    protected ?int $navigationSort = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-tinyfinder';
    }

    public function register(Panel $panel): void
    {
        if ($this->navigationGroup !== null) {
            TinyFinderResource::navigationGroup($this->navigationGroup);
        }

        if ($this->navigationSort !== null) {
            TinyFinderResource::navigationSort($this->navigationSort);
        }

        $panel->resources([
            TinyFinderResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function navigationGroup(string | UnitEnum | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }
}
