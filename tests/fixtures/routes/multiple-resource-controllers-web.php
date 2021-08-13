

Route::resource('page', App\Http\Controllers\PageController::class);

Route::resource('category', App\Http\Controllers\CategoryController::class)->only('index', 'destroy');
