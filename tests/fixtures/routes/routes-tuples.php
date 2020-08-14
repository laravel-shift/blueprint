

Route::resource('foo', App\Http\Controllers\FooController::class);

Route::get('some/whatever', [App\Http\Controllers\SomeController::class, 'whatever']);
