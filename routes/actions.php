<?php

use DuncanMcClean\GuestEntries\Http\Controllers\GuestEntryController;
use DuncanMcClean\GuestEntries\Http\Middleware\EnsureFormParametersArriveIntact;
use Illuminate\Support\Facades\Route;

Route::name('guest-entries.')->middleware([EnsureFormParametersArriveIntact::class])->group(function () {
    Route::post('/create', [GuestEntryController::class, 'store'])->name('store');
    Route::post('/update', [GuestEntryController::class, 'update'])->name('update');
    Route::delete('/delete', [GuestEntryController::class, 'destroy'])->name('destroy');
});
