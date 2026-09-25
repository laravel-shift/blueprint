

Route::get('posts/{post}/publish', [App\Http\Controllers\PostController::class, 'publish'])->name('posts.publish');
Route::get('posts/{post}/archive', [App\Http\Controllers\PostController::class, 'archive'])->name('posts.archive');
Route::resource('posts', App\Http\Controllers\PostController::class)->only('show');
