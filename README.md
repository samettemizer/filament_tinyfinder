# TinyFinder v2 - Filament Plugin

TinyFinder is a compact file manager plugin for Filament.

It provides image and file uploads, an archive browser, image crop/resize actions, reusable form inputs, and a RichEditor archive integration example.

## Requirements

- PHP 8.2+
- Laravel 12 or 13
- Filament 5.6+
- GD or Imagick

## Installation

```bash
composer require stemizer/filament_tinyfinder

php artisan vendor:publish --tag="filament-tinyfinder-config"
php artisan vendor:publish --tag="filament-tinyfinder-migrations"
php artisan migrate
php artisan storage:link
php artisan filament:assets
```

Add the plugin to your Filament panel provider:

```php
use Stemizer\FilamentTinyFinder\FilamentTinyFinderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentTinyFinderPlugin::make()
                ->navigationGroup('Media')
                ->navigationSort(10),
        ]);
}
```

Optional `.env` values:

```env
TINYFINDER_DISK=public
TINYFINDER_PATH=tinyfinder
TINYFINDER_MAX_FILE_SIZE=134217728
TINYFINDER_MAX_IMAGE_SIZE=10485760
TINYFINDER_IMAGE_DRIVER=gd
TINYFINDER_IMAGE_QUALITY=90
```

## Form Inputs

```php
use Stemizer\FilamentTinyFinder\Forms\Components\TinyFinderFileInput;
use Stemizer\FilamentTinyFinder\Forms\Components\TinyFinderImageInput;

TinyFinderImageInput::make('image')
    ->label('Product Image');

TinyFinderFileInput::make('attachment')
    ->label('Product Attachment');
```

## RichEditor

The package includes an example resource:

```php
Stemizer\FilamentTinyFinder\Examples\ExampleResource
```

It shows how to add TinyFinder image and file archive buttons to Filament's `RichEditor`.

## Translations

Every TinyFinder text goes through Laravel's translator. English (`en`) and Turkish (`tr`) are complete; `de`, `el`, `es`, `fr`, `it`, `ja` and `ru` are partial and fall back to your app's `fallback_locale` for missing keys.

Publish the language files to change texts or add a new locale:

```bash
php artisan vendor:publish --tag="filament-tinyfinder-translations"
```

Edit `lang/vendor/filament-tinyfinder/{locale}/tinyfinder.php`. Keys you remove keep using the package's defaults.

`navigationGroup()` takes a string or an enum. Pass an enum implementing `Filament\Support\Contracts\HasLabel` to translate the group label:

```php
FilamentTinyFinderPlugin::make()
    ->navigationGroup(NavigationGroup::Media);
```
