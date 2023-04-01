<?php

use DuncanMcClean\GuestEntries\Http\Controllers\GuestEntryController;
use Illuminate\Support\Facades\Route;

Route::name('guest-entries.')->group(function () {
    Route::post('/create', [GuestEntryController::class, 'store'])->name('store');
    Route::post('/update', [GuestEntryController::class, 'update'])->name('update');
    Route::delete('/delete', [GuestEntryController::class, 'destroy'])->name('destroy');
});
