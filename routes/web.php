<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\OutfitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WearLogController;
use App\Services\Silhouette\SilhouetteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', function (Request $request, SilhouetteService $silhouette) {
    $measurement = $request->user()->currentMeasurement();

    return Inertia::render('Dashboard', [
        'currentMeasurement' => $measurement,
        'wardrobeItems' => $request->user()->items()->count(),
        'bodyShape' => $silhouette->classify($measurement)->toArray(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/wardrobe', [ItemController::class, 'index'])->name('wardrobe.index');
    Route::get('/wardrobe/create', [ItemController::class, 'create'])->name('wardrobe.create');
    Route::post('/wardrobe/analyze', [ItemController::class, 'analyze'])
        ->middleware('throttle:10,1')
        ->name('wardrobe.analyze');
    Route::post('/wardrobe', [ItemController::class, 'store'])->name('wardrobe.store');
    Route::get('/wardrobe/{item}/edit', [ItemController::class, 'edit'])->name('wardrobe.edit');
    Route::patch('/wardrobe/{item}', [ItemController::class, 'update'])->name('wardrobe.update');
    Route::delete('/wardrobe/{item}', [ItemController::class, 'destroy'])->name('wardrobe.destroy');

    Route::get('/measurements', [MeasurementController::class, 'index'])->name('measurements.index');
    Route::post('/measurements', [MeasurementController::class, 'store'])->name('measurements.store');
    Route::patch('/measurements/{measurement}', [MeasurementController::class, 'update'])->name('measurements.update');

    Route::get('/outfits', [OutfitController::class, 'index'])->name('outfits.index');
    Route::get('/outfits/generate', [OutfitController::class, 'generate'])->name('outfits.generate');
    Route::post('/outfits/generate', [OutfitController::class, 'generateResults'])
        ->middleware('throttle:20,1')
        ->name('outfits.generate.results');
    Route::post('/outfits', [OutfitController::class, 'store'])->name('outfits.store');
    Route::patch('/outfits/{outfit}', [OutfitController::class, 'update'])->name('outfits.update');
    Route::delete('/outfits/{outfit}', [OutfitController::class, 'destroy'])->name('outfits.destroy');
    Route::post('/outfits/{outfit}/favorite', [OutfitController::class, 'favorite'])->name('outfits.favorite');

    Route::get('/wear-log', [WearLogController::class, 'index'])->name('wear-log.index');
    Route::post('/wear-log', [WearLogController::class, 'store'])->name('wear-log.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
