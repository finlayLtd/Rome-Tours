<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Post;
use App\categories;
use App\Map;
use App\Slider;  
use Session;
use Illuminate\Support\Facades\Input;

class reviews extends Controller
{
	
	public function __construct( ){

    $Cate = new categories(); 
    $this->data['header'] = $Cate->all();
    $this->data['headers'] = $Cate->all();
	$aut = new Slider();
	$this->data['slides'] = $aut->firstOrFail();

}


	
	public function review($slug){

	//$this->data['review_comment'] = DB::select("select * from review_comments where status ='PUBLISHED'");
	$this->data['review_comment'] = DB::select("select * from review_comments where status ='1'");
	
	foreach($this->data['review_comment'] as $year){
		
		$years[] = substr($year->created_at,0,4);
	}

	 $this->data['years'] =array_unique($years);
	 
	$this->data['view'] = 'totalreviews';
	$this->data['tab_title'] = "100% TRUSTED REVIEWS - WHEN IN ROME TOURS";
	
	return view('template' ,  ['data' => $this->data] ); 
	
	}  
	

}
