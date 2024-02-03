

Route::resource('pages', App\Http\Controllers\PageController::class);

Route::resource('categories', App\Http\Controllers\CategoryController::class)->only('index', 'destroy');
