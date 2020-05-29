

Route::apiResource('file', 'FileController')->except('index', 'destroy');

Route::apiResource('gallery', 'GalleryController');
