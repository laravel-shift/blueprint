

Route::get('post/error', [App\Http\Controllers\Api\PostController::class, 'error']);
Route::resource('post', App\Http\Controllers\Api\PostController::class)->only('index', 'store');
