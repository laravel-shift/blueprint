

Route::resource('crud', 'CrudController');

Route::resource('users', 'UsersController')->except('create', 'store');
