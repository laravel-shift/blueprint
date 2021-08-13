

Route::resource('post', App\Http\Controllers\PostController::class)->only('index', 'store');
