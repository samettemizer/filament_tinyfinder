<?php

use Illuminate\Support\Facades\Route;
use Stemizer\FilamentTinyFinder\Http\Controllers\ArchiveFileActionController;

Route::middleware(['web', 'auth'])
    ->prefix('tinyfinder/archive/files')
    ->name('tinyfinder.archive.files.')
    ->group(function (): void {
        Route::post('{file}/rename', [ArchiveFileActionController::class, 'rename'])->name('rename');
        Route::post('{file}/resize', [ArchiveFileActionController::class, 'resize'])->name('resize');
        Route::post('{file}/crop', [ArchiveFileActionController::class, 'crop'])->name('crop');
        Route::delete('{file}', [ArchiveFileActionController::class, 'destroy'])->name('destroy');
    });
