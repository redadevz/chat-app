<?phpuse Illuminate\Support\Facades\Route;



Route::middleware('auth:craftable-pro-api')->name('craftable-pro-api.messages.')->group(function () {
    Route::get('message', [App\Http\Controllers\Api\MessageController::class, 'index'])->name('index');
    Route::post('message', [App\Http\Controllers\Api\MessageController::class, 'store'])->name('store');
    Route::get('message/{message}', [App\Http\Controllers\Api\MessageController::class, 'show'])->name('show');
    Route::put('message/{message}', [App\Http\Controllers\Api\MessageController::class, 'update'])->name('update');
    Route::delete('message/{message}', [App\Http\Controllers\Api\MessageController::class, 'destroy'])->name('destroy');
});
use Illuminate\Support\Facades\Route;



Route::middleware('auth:craftable-pro-api')->name('craftable-pro-api.messages.')->group(function () {
    Route::get('message', [App\Http\Controllers\Api\MessageController::class, 'index'])->name('index');
    Route::post('message', [App\Http\Controllers\Api\MessageController::class, 'store'])->name('store');
    Route::get('message/{message}', [App\Http\Controllers\Api\MessageController::class, 'show'])->name('show');
    Route::put('message/{message}', [App\Http\Controllers\Api\MessageController::class, 'update'])->name('update');
    Route::delete('message/{message}', [App\Http\Controllers\Api\MessageController::class, 'destroy'])->name('destroy');
});
