

Route::resource('posts', App\Http\Controllers\PostController::class)->only('index', 'store');
