<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Post;
use App\categories;
use App\Map;  
use App\Blog;  
use App\Slider;
use Session;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class Demo extends Controller
{

public function index(){
	return view('demo');
}


}
