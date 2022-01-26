

Route::get('some/whatever', [App\Http\Controllers\SomeController::class, 'whatever']);
Route::get('some/slug-name', [App\Http\Controllers\SomeController::class, 'slugName']);
Route::resource('some', App\Http\Controllers\SomeController::class)->only('index', 'show');

Route::get('subscriptions/resume', [App\Http\Controllers\SubscriptionsController::class, 'resume']);

Route::get('report', App\Http\Controllers\ReportController::class);
