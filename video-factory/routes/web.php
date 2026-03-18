<?php

use App\Http\Controllers\CaptionStyleController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\RenderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('videos.index');
});

Route::middleware(['demo.auth'])->group(function () {
    // Videos
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/create', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{video}/rerun', [VideoController::class, 'rerun'])->name('videos.rerun');

    // Publishing
    Route::post('/videos/{video}/publish/youtube', [PublishController::class, 'youtube'])->name('videos.publish.youtube');
    Route::post('/videos/{video}/publish/tiktok', [PublishController::class, 'tiktok'])->name('videos.publish.tiktok');

    // Renders
    Route::get('/renders', [RenderController::class, 'index'])->name('renders.index');
    Route::get('/renders/{renderTask}/preview', [RenderController::class, 'preview'])->name('renders.preview');
    Route::get('/renders/{renderTask}/download', [RenderController::class, 'download'])->name('renders.download');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Caption Styles
    Route::resource('caption-styles', CaptionStyleController::class)->except(['show']);
    Route::post('/caption-styles/{captionStyle}/duplicate', [CaptionStyleController::class, 'duplicate'])->name('caption-styles.duplicate');
});
