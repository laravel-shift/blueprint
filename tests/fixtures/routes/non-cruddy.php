

Route::get('somes/whatever', [App\Http\Controllers\SomeController::class, 'whatever']);
Route::get('somes/slug-name', [App\Http\Controllers\SomeController::class, 'slugName']);
Route::resource('somes', App\Http\Controllers\SomeController::class)->only('index', 'show');

Route::get('subscriptions/resume', [App\Http\Controllers\SubscriptionsController::class, 'resume']);

Route::get('reports', App\Http\Controllers\ReportController::class);
