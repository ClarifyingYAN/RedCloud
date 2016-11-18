<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

// api route group
Route::group(['middleware' => ['jwt.auth', 'jwt.refresh'], 'prefix' => 'api'], function () {
    Route::get('/', 'ApiController@index');
    Route::get('/file', 'ApiController@getFiles');
    Route::get('/allFiles', 'ApiController@getAllFiles');
    Route::match(['put', 'patch'], '/file/move', 'ApiController@move');
    Route::match(['put', 'patch'], '/file/rename', 'ApiController@rename');
    Route::get('/file/create/{directory}', 'ApiController@create');
    Route::delete('/file/delete', 'ApiController@delete');
    Route::delete('/file/destroy', 'ApiController@destroy');
});


