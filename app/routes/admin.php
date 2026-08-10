<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/duplicates/{duplicate}/merge', [AdminController::class, 'mergeDuplicate'])->name('duplicates.merge');
    Route::post('/duplicates/{duplicate}/dismiss', [AdminController::class, 'dismissDuplicate'])->name('duplicates.dismiss');
});
