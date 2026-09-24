

Route::get('posts/error', [App\Http\Controllers\Api\PostController::class, 'error'])->name('posts.error');
Route::resource('posts', App\Http\Controllers\Api\PostController::class)->only('index', 'store');
