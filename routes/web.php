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
/********* vendor *******************/
Route::get('/rome/login', 'Auth\VendorLoginController@showLoginForm')->name('vendor.login');
Route::post('/rome/login', 'Auth\VendorLoginController@login')->name('vendor.login.post');
Route::post('/rome/logout', 'Auth\VendorLoginController@logout')->name('vendor.logout');
Route::get('/rome/home', 'Vendor\HomeController@index');
Route::group(['middleware'=>'vendor'], function() {
    //Route::get('/rome/home', 'Vendor\HomeController@index');
});

/**********************************/
Route::get('post/{slug}', function($slug){
	$post = App\Post::where('slug', '=', $slug)->firstOrFail();
	return view('post', compact('post'));
});


Route::get('/demo','Demo@index');

Route::get('/','rometours@index');
/* Route::get('/',function(){
	die("<h1>Maintenance. Back in 24 hours!</h1>");
}); */

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

Route::resource('pages', 'PageController');

Route::get('home', 'rometours@index');

//Route::any('tour/{slug}','rometours@tour'); 



Route::any('rome-tours','rometours@categories');
Route::any('tour-enquiries','rometours@enquiries');
Route::any('trade-enquiries','rometours@tradenquiry');
Route::any('rome-tours/{slug}','rometours@categoriespre');
Route::any('rome-tours/{pram}/{slug}','rometours@tour');

Route::any('roman-food-and-wine/{slug}','rometours@categoriespre');
Route::any('roman-food-and-wine/{pram}/{slug}','rometours@tour');

//Route::any('rome-tours/{pram}/{slug}/{prams}','rometours@tour');
 
Route::any('vatican-tours','rometours@categories');
Route::any('vatican-tours/{slug}','rometours@categoriespre');
Route::any('vatican-tours/{pram}/{slug}','rometours@tour');
 
Route::any('cruise-ship-tours','rometours@categories');
Route::any('cruise-ship-tours/{slug}','rometours@categoriespre');
Route::any('cruise-ship-tours/{pram}/{slug}','rometours@tour'); 


 
   
Route::any('all-tours','rometours@all_tour');     

Route::post('upload', 'rometours@upload');

Route::any('rome-tour-reviews-vatican-tour/{slug}','reviews@review');
Route::any('faqs','contactus@faqs');
Route::any('terms-and-conditions','contactus@termsconditions');
Route::any('about-when-in-rome-tours','contactus@about');
Route::any('rome-links','contactus@romelinks');
Route::any('rome-blog','contactus@blog');
Route::any('rome-blog/{slug}','contactus@blogdetails');
Route::any('blog-comment','contactus@blogcomments');
Route::any('tag/{slug}','rometours@tag');
$parent_category = DB::table('categories')->where('parent_id', '=', NULL)->get();
foreach($parent_category as $pc){
	Route::any($pc->slug,'rometours@categories');
	Route::any($pc->slug.'/{slug}','rometours@categoriespre');
	Route::any($pc->slug.'/{pram}/{slug}','rometours@tour');
	
}

 // Auth::routes(); 
// $allRoutes = Route::getRoutes()->get();

