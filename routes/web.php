<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', function (Request $request) {
    return Inertia::render('Dashboard', [
        'currentMeasurement' => $request->user()->currentMeasurement(),
        'wardrobeItems' => $request->user()->items()->count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/wardrobe', [ItemController::class, 'index'])->name('wardrobe.index');
    Route::get('/wardrobe/create', [ItemController::class, 'create'])->name('wardrobe.create');
    Route::post('/wardrobe', [ItemController::class, 'store'])->name('wardrobe.store');
    Route::get('/wardrobe/{item}/edit', [ItemController::class, 'edit'])->name('wardrobe.edit');
    Route::patch('/wardrobe/{item}', [ItemController::class, 'update'])->name('wardrobe.update');
    Route::delete('/wardrobe/{item}', [ItemController::class, 'destroy'])->name('wardrobe.destroy');

    Route::get('/measurements', [MeasurementController::class, 'index'])->name('measurements.index');
    Route::post('/measurements', [MeasurementController::class, 'store'])->name('measurements.store');
    Route::patch('/measurements/{measurement}', [MeasurementController::class, 'update'])->name('measurements.update');

    Route::get('/outfits', fn () => Inertia::render('Outfits/Index'))->name('outfits.index');
    Route::get('/wear-log', fn () => Inertia::render('WearLog/Index'))->name('wear-log.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
