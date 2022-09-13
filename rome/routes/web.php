<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/* Route::get('/', function () {
    return view('home');
}); */
Route::get('post/{slug}', function($slug){
	$post = App\Post::where('slug', '=', $slug)->firstOrFail();
	return view('post', compact('post'));
});

Route::get('/','rometours@index');
Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
Route::resource('pages', 'PageController');
Route::get('/home', 'rometours@index');

Route::get('test', function () {
  // $users = DB::table('test')->get();
   
    /* $users = DB::table('users')->get();
		

	echo '<pre>';

	print_r($users);

	exit; */ 
	
	
	   $posts = App\Post::all();
  
  echo '<pre>';

	print_r($posts);

	exit; 
	
	
});

