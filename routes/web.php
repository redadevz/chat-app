<?php

use App\Http\Controllers\Chat\ChatPermissionController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['craftable-pro-base-middlewares', 'craftable-pro-auth-middleware'])
    ->prefix('admin')
    ->name('chats.')
    ->group(function () {
        Route::get('chats', [ChatController::class, 'index'])->name('index');
        Route::post('chats', [ChatController::class, 'store'])->name('store');
        Route::get('chats/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('chats/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('messages.store');
        Route::delete('chats/{conversation}/membership', [ChatController::class, 'leave'])->name('leave');
        Route::get('chats-support', [ChatController::class, 'support'])->name('support');

        Route::get('chat-permissions', [ChatPermissionController::class, 'index'])->name('permissions.index');
        Route::put('chat-permissions', [ChatPermissionController::class, 'update'])->name('permissions.update');
    });

Route::craftablePro('admin');




/* Auto-generated admin routes */
Route::middleware('craftable-pro-middlewares')->prefix('admin')->name('craftable-pro.')->group(function () {
    Route::get('conversations', [App\Http\Controllers\CraftablePro\ConversationController::class, 'index'])->name('conversations.index');
    Route::get('conversations/create', [App\Http\Controllers\CraftablePro\ConversationController::class, 'create'])->name('conversations.create');
    Route::post('conversations', [App\Http\Controllers\CraftablePro\ConversationController::class, 'store'])->name('conversations.store');
    Route::get('conversations/edit/{conversation}', [App\Http\Controllers\CraftablePro\ConversationController::class, 'edit'])->name('conversations.edit');
    Route::match(['put', 'patch'], 'conversations/{conversation}', [App\Http\Controllers\CraftablePro\ConversationController::class, 'update'])->name('conversations.update');
    Route::delete('conversations/{conversation}', [App\Http\Controllers\CraftablePro\ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::post('conversations/bulk-destroy', [App\Http\Controllers\CraftablePro\ConversationController::class, 'bulkDestroy'])->name('conversations.bulk-destroy');
    Route::get('conversations/export', [App\Http\Controllers\CraftablePro\ConversationController::class, 'export'])->name('conversations.export');

});




/* Auto-generated admin routes */
Route::middleware('craftable-pro-middlewares')->prefix('admin')->name('craftable-pro.')->group(function () {
    Route::get('messages', [App\Http\Controllers\CraftablePro\MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/create', [App\Http\Controllers\CraftablePro\MessageController::class, 'create'])->name('messages.create');
    Route::post('messages', [App\Http\Controllers\CraftablePro\MessageController::class, 'store'])->name('messages.store');
    Route::get('messages/edit/{message}', [App\Http\Controllers\CraftablePro\MessageController::class, 'edit'])->name('messages.edit');
    Route::match(['put', 'patch'], 'messages/{message}', [App\Http\Controllers\CraftablePro\MessageController::class, 'update'])->name('messages.update');
    Route::delete('messages/{message}', [App\Http\Controllers\CraftablePro\MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('messages/bulk-destroy', [App\Http\Controllers\CraftablePro\MessageController::class, 'bulkDestroy'])->name('messages.bulk-destroy');
    
});
