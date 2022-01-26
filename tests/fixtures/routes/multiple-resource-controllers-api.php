

Route::apiResource('file', App\Http\Controllers\FileController::class)->except('index', 'destroy');

Route::apiResource('gallery', App\Http\Controllers\GalleryController::class);
