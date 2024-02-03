

Route::apiResource('files', App\Http\Controllers\FileController::class)->except('index', 'destroy');

Route::apiResource('galleries', App\Http\Controllers\GalleryController::class);
