

Route::get('post/error', 'Api\PostController@error');
Route::resource('post', 'Api\PostController')->only('index', 'store');
