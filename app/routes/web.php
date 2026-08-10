<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\ShoppingListController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(Auth::check() ? route('chat.index') : route('login'));
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/new', [ChatController::class, 'newConversation'])->name('chat.new');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/send', [ChatController::class, 'send'])->name('chat.send');

    Route::get('/shopping-list', [ShoppingListController::class, 'index'])->name('shopping-list.index');
    Route::post('/shopping-list/items', [ShoppingListController::class, 'store'])->name('shopping-list.items.store');
    Route::patch('/shopping-list/items/{item}', [ShoppingListController::class, 'update'])->name('shopping-list.items.update');
    Route::delete('/shopping-list/items/{item}', [ShoppingListController::class, 'destroy'])->name('shopping-list.items.destroy');

    Route::get('/alerts', [PriceAlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts', [PriceAlertController::class, 'store'])->name('alerts.store');
    Route::delete('/alerts/{alert}', [PriceAlertController::class, 'destroy'])->name('alerts.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

require __DIR__.'/admin.php';
