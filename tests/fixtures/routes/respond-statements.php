

Route::get('posts/error', [App\Http\Controllers\Api\PostController::class, 'error']);
Route::resource('posts', App\Http\Controllers\Api\PostController::class)->only('index', 'store');
