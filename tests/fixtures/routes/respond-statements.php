

Route::resource('post', 'Api\PostController')->only('index', 'store');
Route::get('post/error', 'Api\PostController@error');
