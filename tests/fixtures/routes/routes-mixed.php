

Route::resource('foos', App\Http\Controllers\FooController::class);

Route::get('somes/whatever', [App\Http\Controllers\SomeController::class, 'whatever']);

Route::get('reports', App\Http\Controllers\ReportController::class);
