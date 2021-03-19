

Route::resource('some', 'SomeController')->only('index', 'show');
Route::get('some/whatever', 'SomeController@whatever');
Route::get('some/slug-name', 'SomeController@slugName');

Route::get('subscriptions/resume', 'SubscriptionsController@resume');

Route::get('report', 'ReportController');
