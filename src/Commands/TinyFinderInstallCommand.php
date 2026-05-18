<?php

namespace Stemizer\FilamentTinyFinder\Commands;

use Illuminate\Console\Command;

class TinyFinderInstallCommand extends Command
{
    protected $signature = 'tinyfinder:install {--force : Overwrite published files}';

    protected $description = 'Publish Filament TinyFinder config and migrations.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->call('vendor:publish', [
            '--tag' => 'filament-tinyfinder-config',
            '--force' => $force,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'filament-tinyfinder-migrations',
            '--force' => $force,
        ]);

        $this->info('Filament TinyFinder assets were installed.');

        return self::SUCCESS;
    }
}
