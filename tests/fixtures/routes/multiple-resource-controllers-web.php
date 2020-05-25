

Route::resource('page', 'PageController');

Route::resource('category', 'CategoryController')->only('index', 'destroy');
