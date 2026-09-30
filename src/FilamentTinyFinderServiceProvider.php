<?php

namespace Stemizer\FilamentTinyFinder;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Stemizer\FilamentTinyFinder\Commands\TinyFinderInstallCommand;
use Stemizer\FilamentTinyFinder\Livewire\FileManager;
use Stemizer\FilamentTinyFinder\Livewire\ImageCropper;

class FilamentTinyFinderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-tinyfinder';

    public static string $viewNamespace = 'filament-tinyfinder';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('samettemizer/filament_tinyfinder');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }

        if (file_exists($package->basePath('/../routes/web.php'))) {
            $package->hasRoute('web');
        }
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        // Livewire Components
        Livewire::component('tinyfinder.file-manager', FileManager::class);
        Livewire::component('tinyfinder.image-cropper', ImageCropper::class);

        // Storage disk configuration
        $this->configureTinyFinderDisk();
    }

    protected function getAssetPackageName(): ?string
    {
        return 'stemizer/filament_tinyfinder';
    }

    /**
     * @return array<Css>
     */
    protected function getStyles(): array
    {
        return [
            Css::make('tinyfinder-styles', __DIR__ . '/../resources/css/tinyfinder.css'),
        ];
    }

    /**
     * @return array<Js>
     */
    protected function getScripts(): array
    {
        return [
            Js::make('tinyfinder', __DIR__ . '/../resources/js/tinyfinder.js'),
        ];
    }

    /**
     * @return array<Css|Js>
     */
    protected function getAssets(): array
    {
        return [
            ...$this->getStyles(),
            ...$this->getScripts(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            TinyFinderInstallCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_tinyfinder_files_table',
        ];
    }

    protected function configureTinyFinderDisk(): void
    {
        $filesystem = app(Filesystem::class);

        if (! $filesystem->exists(storage_path('app/public/tinyfinder'))) {
            $filesystem->makeDirectory(storage_path('app/public/tinyfinder'), 0755, true);
            $filesystem->makeDirectory(storage_path('app/public/tinyfinder/images'), 0755, true);
            $filesystem->makeDirectory(storage_path('app/public/tinyfinder/images/thumbs'), 0755, true);
            $filesystem->makeDirectory(storage_path('app/public/tinyfinder/files'), 0755, true);
        }
    }
}
