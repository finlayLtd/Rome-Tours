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

class rometours extends Controller
{
	
	public function __construct( ){
		
	$this->data['facilities'] = DB::table('facilities')
						   ->orderBy('id', 'ASC')
						   ->get();	 
    $Cate = new categories(); 
	$this->data['meta'] = "";
	
   $this->data['headers'] = $Cate->all()->sortBy("order");
	
	$results = Categories::all()->sortBy("order");
	$aut = new Slider();
	$this->data['slides'] = $aut->firstOrFail();
	
	foreach($results as $result){
		$new_header[] = (object) $result ;
	}
	 $this->data['header']=$new_header;
	//echo "<Pre>"; print_r($new_header); die('here') ;
}


    public function index( Request $request ){
		
		$this->data['whichFunction'] = "index";
		$auth = new Post();
		$query = $request->all();
		if(!empty($query)){
		$Cate = new categories(); 
		$this->data['categorie'] = $Cate->all()->toArray();
		$search_keyword = $request['s'];
		$this->data['search_item'] = $request['s'];
		$this->data['tab_title'] = 'Search Result for "'.$search_keyword.'" - '. setting('site.title');
		if(!empty($search_keyword)){
			$searchValues = preg_split('/\s+/', $search_keyword, -1, PREG_SPLIT_NO_EMPTY); 

			$this->data['blogs'] = DB::table('blog')->where(function ($q) use ($searchValues) {

				foreach ($searchValues as $value) {
					$q->orWhere('blog_title', 'like', "%{$value}%");
					$q->orWhere('blog_content', 'like', "%{$value}%");
				}

			})->latest()->get();
		} else {
			$this->data['blogs'] = DB::table('blog')->latest()->get();
		}
			$this->data['view'] = 'search';
		} else { 
			$this->data['view'] = 'home';
		}
		$this->data['allpost'] = $auth->where('featured', '=', '1')->get();
		$auth_map = new Map();
		$this->data['map_locations'] = $auth_map->all();
		$this->data['clinet_reviews'] = DB::select("select * from review_comments where status = 1 order by created_at DESC limit 3 ");
		$Cate = new categories();
		$featuredCategory = $Cate->where('homepage_category','=',1)->orderBy('order', 'asc')->get();
		$i = 0;		
		foreach($featuredCategory as $fc){
			$id = $fc['id'];
			$checkTours =  DB::table('posts')
							->join('categories', 'posts.category_id', '=', 'categories.id')
							->where('posts.status', '=', 'PUBLISHED')
							->where('categories.id', '=', $id)
							->get();
			if(count($checkTours)>0){
				$featuredCategory[$i]['alltours'] = $checkTours;
			} else
				{
					$featuredCategory[$i]['alltours'] =  DB::table('posts')
					->join('categories', 'posts.category_id', '=', 'categories.id')
					->where('posts.status', '=', 'PUBLISHED')
					->whereIn( 'categories.id', function ( $query ) use ($id) {
						$query->select( 'id' )
						->from( 'categories' )
						->whereRaw("categories.parent_id = $id");
					})
					->get();
				}	
			$i++;
		}
		$this->data['homepage_categories'] = $featuredCategory;
		return view('template' ,  ['data' => $this->data] );
	}
	
	public function tour($pram ,$slug, Request $request){	
		$this->data['whichFunction'] = "tour";
		$method = $request->method();

		if ($request->isMethod('post')) {

		
		$rate = (isset($request->all()['rating']))? $request->all()['rating']:'0';
		$review = array(
						'name'=> $request->all()['name'],
						'review_title'=> $request->all()['review_title'],
						'review_star'=> $rate,
						'review'=> $request->all()['review'],
						'tour_id'=> $request->all()['tour_id'],
						'created_at'=> date('Y-m-d H:i:s')
					);
					DB::table('review_comments')->insert($review);	
					
					
		}
		$auth = new Post(); 
		$Cate = new categories(); 
		$this->data['categorie'] = $Cate->all()->toArray();
		
		$this->data['postdetils'] = $auth->where('slug', '=', $slug)->firstOrFail();
		///echo "<pre>"; print_r($this->data['postdetils']->title); die('here');
		$this->data['tab_title'] = $this->data['postdetils']->title." - WHEN IN ROME TOURS";
		
		$gallery_images = DB::select("select * from pics where post_id = ?", [$this->data['postdetils']->id]);
		if(count($gallery_images)>0){
			$this->data['gallery_images'] = json_decode($gallery_images[0]->Images);
		} else {
			$this->data['gallery_images'] = array();
		}
		
		$this->data['tour_galleries'] = DB::select("select * from tour_galleries where status ='PUBLISHED' AND tour_id = ?", [$this->data['postdetils']->id]);
		$this->data['review_comment'] = DB::select("select * from review_comments where status ='PUBLISHED' AND tour_id = ?", [$this->data['postdetils']->id]);
		
		$this->data['video_slides'] = (array) DB::select("select * from tour_slider_videos where tour_name = ?", [$this->data['postdetils']->id]);
		$this->data['sidebar_data'] = DB::select("select * from sidebars where status = 'PUBLISHED' ORDER BY `order` ASC ");
		$this->data['tripadvisor_image'] = DB::select("select * from tripadvisor_reviews where status = 1 order by created_at DESC ");
		$this->data['view'] = 'post_detail';
		$this->data['seo_title'] = $this->data['postdetils']->seo_title;
		$this->data['meta_description'] = $this->data['postdetils']->meta_description;
		$this->data['meta'] = $this->data['postdetils']->meta_description;
		#######Breadcrumb#######
		$Cate = new categories(); 
		$segment2 = \Request::segment(2);
		//$categories = $Cate->where('id', '=', $this->data['postdetils']->category_id)->firstOrFail();
		$categories = $Cate->where('slug', '=', $segment2)->firstOrFail();
		$categoriespr = $Cate->where('id', '=', $categories->parent_id)->firstOrFail();
		$this->data['categories'] = $categories;
		$this->data['categoriespr'] = $categoriespr;
		#-----------------------#
		
		return view('template' ,  ['data' => $this->data] );
	
	}
	
	public function upload(Request $request){
		if(Input::hasFile('file')){
			$file = Input::file('file');
			$file->move('storage/app/public', $file->getClientOriginalName());
			$tour_gallery = array(
			'url'=> $file->getClientOriginalName(),
			'tour_id'=> $request->all()['tour_id'],
			'tour_name'=> $request->all()['title'],
			'created_at'=> date('Y-m-d H:i:s')
			);
			DB::table('tour_galleries')->insert($tour_gallery);	
			return redirect(url($request->all()['segment1']));
		}
		return redirect(url($request->all()['segment1']));
	}  
	
	
	public function categories( Request $request){ 
		$this->data['whichFunction'] = "categories";
		$slug = \Request::segment(1);
		######check view type######
		if(isset($request->all()['view']) ){
		$this->data['view_type'] = $request->all()['view'];
	}else{
		$this->data['view_type'] ='list';
	} 
	######--------------######
	
	$auth = new Post(); 
	$Cate = new categories(); 
	$this->data['parentBySug'] = $Cate->select('id')->where('slug', '=', $slug)->first();
	$temp = $Cate->select('meta_description','seo_title')->where('slug', '=', $slug)->first();
	$this->data['meta'] = $temp->meta_description;
	$this->data['isSubCategoryPage'] = false;
	$this->data['view'] = 'categories_post';
	$this->data['pageInfo'] = "categories";
	$categories = $Cate->where('slug', '=', $slug)->firstOrFail();
	
	if($categories->parent_id == null){
		
		$ids =  array_keys(array_column($this->data['headers']->toArray(), 'parent_id'), $categories->id);
		$results = $Cate->where('parent_id','=',$categories->id)->get()->toArray();
		if($results!= null){
			foreach($results as $idss){
				///$allcats = $this->data['headers']->toArray();	
				$id[] = $idss['id']; 
			}
		} else  {
					$id[] = $categories->id;
		}
	} else {	
	
	
		$id[] = $categories->id;
	}
	$this->data['tab_title'] = $categories['name'].'" - '. setting('site.title');
	$this->data['allpost'] = $auth->all()->where('status', '=', "PUBLISHED")->toArray();
	$this->data['categories'] = $categories;
	
    $this->data['posts'] = $this->filters($auth,$id,$request->all());  //////////////////////////////////////////////////////////////////////////////////////////////////////////
	return view('template' ,  ['data' => $this->data] );
	
	}
	


	public function categoriespre($slug, Request $request){

		$this->data['whichFunction'] = "categoriespre";
		######check view type######
		if(isset($request->all()['view']) ){
			$this->data['view_type'] = $request->all()['view'];
		}else{
			$this->data['view_type'] ='list';
		} 
		######--------------######
		
		$auth = new Post(); 
		$Cate = new categories(); 
		$this->data['view'] = 'categories_post';
		$this->data['pageInfo'] = "categoriespre";
		$this->data['parentBySug'] = $Cate->select('parent_id','seo_title')->where('slug', '=', $slug)->first();
		$temp = $Cate->select('meta_description')->where('slug', '=', $slug)->first();
		$this->data['meta'] = $temp->meta_description;
		$categories = $Cate->where('slug', '=', $slug)->firstOrFail();
		$this->data['isSubCategoryPage'] = true;
		if($categories->parent_id == null){
		$id =  array_keys(array_column($this->data['header']->toArray(), 'parent_id'), $categories->id);
		}else{
			$parent_c = $Cate->where('id', '=',$categories->parent_id)->firstOrFail();
			$this->data['parentcategory'] = $parent_c;
			$id[] = $categories->id;
			
		}
		$this->data['allpost'] = $auth->all()->where('status', '=', "PUBLISHED")->toArray();
		$this->data['categories'] = $categories;
		//echo "<pre>"; print_r($categories['name']); die('here');
		$this->data['tab_title'] = $categories['name']." - ".setting('site.title');
		$this->data['posts'] = $this->filters($auth,$id,$request->all());
		// exit(json_encode($id));///////////////////////////////////////////////////////////////////////////////////////////////////////////
		return view('template' ,  ['data' => $this->data] );
		
	}
	
	
	public function all_tour( Request $request){ 
		$this->data['whichFunction'] = "all_tour";
		$this->data['meta'] = "Popular tours in Rome by Eternal Tours Rome that include underground Rome tours, Colosseum tours & St. Peter's Dome Climb | info@eternaltoursrome.com";
		$auth = new Post(); 
		$Cate = new categories();
		$currentURL = \Request::segment(1);
		// echo "<pre>"; print_r($currentURL); die;

		$this->data['posts'] = $auth->all()->where('status', '=', "PUBLISHED")->sortBy("order_number"); 
		$this->data['parentBySug'] = 100;
		$this->data['isSubCategoryPage'] = false;
		######Start search######
		if(isset($request->all()['s']) AND $request->all()['tour_types'] == 'all_tour'){	
			$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->where('title', 'like', '%'.$request->all()['s'].'%')->orderBy('order_number')->get(); 	
		} elseif(isset($request->all()['s']) AND $request->all()['tour_types'] != 'all_tour' AND $request->all()['tour_types'] != 'price_tour' AND $request->all()['tour_types'] != 'ratting_tour'){
			$categories = $Cate->where('slug', '=', $request->all()['tour_types'])->firstOrFail();
				if($categories->parent_id == null){
						$id =  array_keys(array_column($this->data['header'], 'parent_id'), $categories->id);
						$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->where('title', 'like', '%'.$request->all()['s'].'%')->whereIn('category_id', $id)->orderBy('order_number')->get(); 
				} else{	
						$id[] = $categories->id;
				}
			$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->where('title', 'like', '%'.$request->all()['s'].'%')->whereIn('category_id', $id)->orderBy('order_number')->get();
		} elseif(isset($request->all()['s']) AND $request->all()['tour_types'] == 'price_tour'){		
			if($request->all()['s'] == 'ASC'){
				
				$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->orderBy('price1', 'ASC')->get();
				
			} else{
				$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->orderBy('price1', 'DESC')->get();
			}
		} elseif(isset($request->all()['s']) AND $request->all()['tour_types'] == 'ratting_tour'){		
			if($request->all()['s'] == 'ASC'){
				
				$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->orderBy('reviews_count', 'ASC')->get();
				
			}else{
				$this->data['posts'] = $auth->where('status', '=', "PUBLISHED")->orderBy('reviews_count', 'DESC')->get();
			}
	}
	
	
	
	######check view type######
	if($request->all() != NUll && isset($request->all()['view'])){
		$this->data['view_type'] = $request->all()['view'];
	}else{
		$this->data['view_type'] ='list';
	}
	

	$this->data['view'] = 'categories_post';
	$categories = (object) array('name' => $currentURL , "slug"=>$currentURL ,"parent_id"=> NULL);
	$this->data['pageInfo'] = "allPage";
	$this->data['allpost'] = $auth->all()->where('status', '=', "PUBLISHED")->toArray();
	$this->data['categories'] = $categories;	
	$this->data['alltourspage'] = "ALL TOURS";
	$this->data['tab_title'] = "ALL TOURS - ".setting('site.title');	
	$this->data['getprem'] = (object) $request->all();
	return view('template' ,  ['data' => $this->data] );
	
	}
	public function enquiries(Request $request){
		$this->data['whichFunction'] = "enquiries";
		$method = $request->method();
			$a=mt_rand(5, 9);
			$b=mt_rand(1, 5);
			$myArray = array('+','-');
			shuffle($myArray);
			$operator = $myArray[0];
			switch($operator) {
			case '+':
				$result= $a + $b;
				break;
			case '-':
				$result= $a - $b;
				break;
				}
		$this->data['num1'] = $a;
		$this->data['num2'] = $b;
		$this->data['operator'] = $operator;
		$this->data['res'] = $result;
		$this->data['tab_title'] = "TOUR ENQUIRIES - ".setting('site.title');
		if ($request->isMethod('post')) {

		
			$enquiry = array(
				'name'=> $request->all()['name'],
				'email'=> $request->all()['email'],
				'message'=> $request->all()['message'],
				'created_at'=> date('Y-m-d H:i:s')
				);
			$maildata = array(
				'name'=> $request->all()['name'],
				'email'=> $request->all()['email'],
				'mymessage'=> $request->all()['message']
				);
			if(DB::table('tour_enquiries')->insert($enquiry))
				{
					Mail::send('emails.tour_enquiry', $maildata, function ($message) use ($maildata) {
						$message->from('tour-enquiries@wheninrometours.com',$maildata['name']);
						$message->subject('Tour Enquiries');
						$message->to(setting('site.enquiry_email'));
				});
					Session::flash('success', 'Message sent!');
				} else {
					Session::flash('errors', 'There was an error trying to send your message, please try again later.');
				}
				
				$message = "this is the test message! hello Laravel ";
		} 

		$this->data['page_title'] = "TOUR ENQUIRIES";
		$this->data['view'] = 'tour_enquiries';
		return view('template' ,  ['data' => $this->data] );
	
	}
	public function tradenquiry(Request $request){
		$this->data['whichFunction'] = "tradenquiry";
	$method = $request->method();
		$a=mt_rand(5, 9);
		$b=mt_rand(1, 5);
		$myArray = array('+','-');
		shuffle($myArray);
		$operator = $myArray[0];
		  switch($operator) {
		 case '+':
			$result= $a + $b;
			break;
		 case '-':
			$result= $a - $b;
			break;
			}
	$this->data['num1'] = $a;
	$this->data['num2'] = $b;
	$this->data['operator'] = $operator;
	$this->data['res'] = $result;
	$this->data['tab_title'] = "TRADE ENQUIRIES - ".setting('site.title');
	 if ($request->isMethod('post')) {

	
	 $enquiry = array(
				'name'=> $request->all()['name'],
				'email'=> $request->all()['email'],
				'message'=> $request->all()['message'],
				'company'=> $request->all()['company'],
				'created_at'=> date('Y-m-d H:i:s')
				);
				$maildata = array(
				'name'=> $request->all()['name'],
				'email'=> $request->all()['email'],
				'mymessage'=> $request->all()['message'],
				'company'=> $request->all()['company']
				);
	 	if(DB::table('trade_enquiries')->insert($enquiry)){

				Mail::send('emails.trade_enquiry', $maildata, function ($message) use ($maildata) {
						$message->from('tour-enquiries@wheninrometours.com',$maildata['name']);
						$message->subject('Trade Enquiries');
						$message->to(setting('site.enquiry_email'));
				});
					
					Session::flash('success', 'Message sent!');
		} else {
					Session::flash('errors', 'There was an error trying to send your message, please try again later.');
		}
				
				$message = "this is the test message! hello Laravel ";  
	}
	
	
	$this->data['page_title'] = "TRADE ENQUIRIES";
	$this->data['view'] = 'trade_enquiry';
	return view('template' ,  ['data' => $this->data] );
	
	}
	public function tag(Request $request){
	
		$auth = new Post(); 
	$Cate = new categories();
	$currentURL = \Request::segment(1);
	$search_term = \Request::segment(2);
	$this->data['posts'] = $auth->all(); 
	
	######Start search######
	if(isset($request->all()['s']) AND $request->all()['tour_types'] == 'all_tour'){	
	$this->data['posts'] = $auth->where('title', 'like', '%'.$request->all()['s'].'%')->get(); 	
			
		
	}elseif(isset($request->all()['s']) AND $request->all()['tour_types'] != 'all_tour' AND $request->all()['tour_types'] != 'price_tour' AND $request->all()['tour_types'] != 'ratting_tour'){
	$categories = $Cate->where('slug', '=', $request->all()['tour_types'])->firstOrFail();
		if($categories->parent_id == null){
				$id =  array_keys(array_column($this->data['header']->toArray(), 'parent_id'), $categories->id);
				$this->data['posts'] = $auth->where('title', 'like', '%'.$request->all()['s'].'%')->whereIn('category_id', $id)->get(); 
		}else{	
				$id[] = $categories->id;
		}
		$this->data['posts'] = $auth->where('title', 'like', '%'.$request->all()['s'].'%')->whereIn('category_id', $id)->get(); 
	}elseif(isset($request->all()['s']) AND $request->all()['tour_types'] == 'price_tour'){		
		if($request->all()['s'] == 'ASC'){
			
			$this->data['posts'] = $auth->orderBy('price1', 'ASC')->get();
			
		}else{
			$this->data['posts'] = $auth->orderBy('price1', 'DESC')->get();
		}
	}elseif(isset($request->all()['s']) AND $request->all()['tour_types'] == 'ratting_tour'){		
		if($request->all()['s'] == 'ASC'){
			
			$this->data['posts'] = $auth->orderBy('reviews_count', 'ASC')->get();
			
		}else{
			$this->data['posts'] = $auth->orderBy('reviews_count', 'DESC')->get();
		}
	}
	
	
	
	######check view type######
	if($request->all() != NUll && isset($request->all()['view'])){
		$this->data['view_type'] = $request->all()['view'];
	}else{
		$this->data['view_type'] ='list';
	}
	

	$this->data['view'] = 'tag_filter';
	$categories = (object) array('name' => $currentURL , "slug"=>$currentURL ,"parent_id"=> NULL);

	$this->data['allpost'] = $auth->all()->toArray();
	$this->data['categories'] = $categories;	
	$this->data['alltourspage'] = $search_term;
	$this->data['tab_title'] = $search_term." - ".setting('site.title');	
	$this->data['getprem'] = (object) $request->all();
	

	return view('template' ,  ['data' => $this->data] );
	}
	
	public function filters($auth,$id,$getdata){
		
		$sarchData = array();
		if(isset($getdata['price_filter'])){
			$sarchData = array()  ;
			if(in_array( 0 ,$getdata['price_filter'])){
				$where1[] = array('price1', '>', 0);
				$where1[] = array('price1', '<', 50);
				$results = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where1)->get();
				if(isset($getdata['facility_filter'])){
		
						for($i=0; $i< count($_GET['facility_filter']); $i++){
						$ID = array_keys(array_column($this->data['facilities']->toArray(), 'id'), $_GET['facility_filter'][$i]);
						$arryfacilities[] = $this->data['facilities']->toArray()[$ID[0]]->f_title;

						}
						
						$sarchData = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where1)->where(function ($q) use ($arryfacilities) {
						foreach ($arryfacilities as $value) {
						$q->Where('top_instruction', 'like', "%{$value}%");
						}
						})->get();		
						
						
						
				}else{
					$sarchData = $results  ;
				}
				}
			if(in_array( 1 ,$getdata['price_filter'])){
				$where2[]= array('price1', '>', 50);
				$where2[]= array('price1', '<', 80);
				$results = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where2)->get();
				
				if(isset($getdata['facility_filter'])){
		
						for($i=0; $i< count($_GET['facility_filter']); $i++){
						$ID = array_keys(array_column($this->data['facilities']->toArray(), 'id'), $_GET['facility_filter'][$i]);
						$arryfacilities[] = $this->data['facilities']->toArray()[$ID[0]]->f_title;

						}
						
						$sarchData = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where2)->where(function ($q) use ($arryfacilities) {
						foreach ($arryfacilities as $value) {
						$q->Where('top_instruction', 'like', "%{$value}%");
						}
						})->get();		
						
						
						
				}else{
					$sarchData = $results  ;
				}
				}
				
			if(in_array( 2 ,$getdata['price_filter'])){
				$where3[]= array('price1', '>', 80);
				$where3[]= array('price1', '<', 100);
				$results = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where3)->get();
				
				if(isset($getdata['facility_filter'])){
		
						for($i=0; $i< count($_GET['facility_filter']); $i++){
						$ID = array_keys(array_column($this->data['facilities']->toArray(), 'id'), $_GET['facility_filter'][$i]);
						$arryfacilities[] = $this->data['facilities']->toArray()[$ID[0]]->f_title;

						}
						
						$sarchData = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where3)->where(function ($q) use ($arryfacilities) {
						foreach ($arryfacilities as $value) {
						$q->Where('top_instruction', 'like', "%{$value}%");
						}
						})->get();		
						
						
						
				}else{
					$sarchData = $results  ;
				}
	
	
				}
				
				
			if(in_array( 3 ,$getdata['price_filter'])){
				$where4[]= array('price1', '>', 100);
				$results = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where3)->get();
				
				if(isset($getdata['facility_filter'])){
		
						for($i=0; $i< count($_GET['facility_filter']); $i++){
						$ID = array_keys(array_column($this->data['facilities']->toArray(), 'id'), $_GET['facility_filter'][$i]);
						$arryfacilities[] = $this->data['facilities']->toArray()[$ID[0]]->f_title;

						}
						
						$sarchData = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where($where4)->where(function ($q) use ($arryfacilities) {
						foreach ($arryfacilities as $value) {
						$q->Where('top_instruction', 'like', "%{$value}%");
						}
						})->get();		
						
						
						
				}else{
					$sarchData = $results ;
				}
	
				}
				
				
			
			
		}else{
			 	
			if(isset($getdata['facility_filter'])){
		
						for($i=0; $i< count($_GET['facility_filter']); $i++){
						$ID = array_keys(array_column($this->data['facilities']->toArray(), 'id'), $_GET['facility_filter'][$i]);
						$arryfacilities[] = $this->data['facilities']->toArray()[$ID[0]]->f_title;

						}
						
						$sarchData = $auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->where(function ($q) use ($arryfacilities) {
						foreach ($arryfacilities as $value) {
						$q->Where('top_instruction', 'like', "%{$value}%");
						}
						})->get();		
						
						
						
				}else{
					$laraarry = true;
					$sarchData =$auth->whereIn('category_id', $id)->where('status', '=', "PUBLISHED")->orderBy('order_number','asc')->get();
				}}
	
			//////////ratting filter////////////
		   if(isset($getdata['rating_filter'])){
			if(isset($laraarry)){
			$ids = array_column($sarchData->toArray(), 'id');
			}else{
				$ids = array_column($sarchData->toArray(), 'id');
			}
			$sarchData = array()  ;
			$this->data['review_comment'] = DB::table('review_comments')
			->select('id', 'review_star', 'tour_id')
			->whereIn('tour_id', $ids) 
			->get()->toArray();			
			
			for($i=0;  $i<count($ids); $i++){

			$keys = array_keys(array_combine(array_keys($this->data['review_comment']), array_column($this->data['review_comment'], 'tour_id')),$ids[$i]);


				if($keys != null){
					for($l=0;  $l<count($keys); $l++){
						$mainary[] = $this->data['review_comment'][$keys[$l]];
					}

					$countrt = array_count_values(array_column($mainary, 'review_star'));

					for($z=1;  $z <= 5; $z++){				
					if(in_array( $z ,$getdata['rating_filter']) && array_key_exists($z, $countrt) && $countrt[$z]== max($countrt) ){
						
							$sarchData[] = (object) $auth->where('id', $ids[$i])->get()->toArray()[0];
							
						}
					}
				}
			}
		}   
		return $sarchData;
	}	
	

}
