

Route::get('somes/whatever', [App\Http\Controllers\SomeController::class, 'whatever'])->name('somes.whatever');
Route::get('somes/slug-name', [App\Http\Controllers\SomeController::class, 'slugName'])->name('somes.slugName');
Route::resource('somes', App\Http\Controllers\SomeController::class)->only('index', 'show');

Route::get('subscriptions/resume', [App\Http\Controllers\SubscriptionsController::class, 'resume'])->name('subscriptions.resume');

Route::get('reports', App\Http\Controllers\ReportController::class)->name('reports');
