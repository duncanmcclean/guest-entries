<?php

use DuncanMcClean\GuestEntries\Http\Controllers\GuestEntryController;
use Illuminate\Support\Facades\Route;

Route::name('guest-entries.')->middleware('throttle:guest-entries')->group(function () {
    Route::post('/{handle}', [GuestEntryController::class, 'store'])->name('store');
    Route::patch('/{handle}/{id}', [GuestEntryController::class, 'update'])->name('update');
    Route::delete('/{handle}/{id}', [GuestEntryController::class, 'destroy'])->name('destroy');
});
