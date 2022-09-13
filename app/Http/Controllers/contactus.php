<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Post;
use App\categories;
use App\Blog;  
use App\Slider;  
use Session;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class contactus extends Controller
{
	
	public function __construct( ){

    $Cate = new categories(); 
	
	$aut = new Slider();
	$this->data['slides'] = $aut->firstOrFail();
   $this->data['headers'] = $Cate->all();
 	$results = Categories::all()->sortBy("name");
	
	foreach($results as $result){
		$new_header[] = (object) $result ;
	}
	
	 $this->data['header']=$new_header;
}


    public function index(){
		
	$auth = new Post();
	$this->data['allpost'] = $auth->where('featured', '=', '1')->get();
	$auth_map = new Map();
	$this->data['map_locations'] = $auth_map->all();
	//echo "<pre>"; print_r($this->data['map_locations']); die('here');
	$this->data['view'] = 'home';
	return view('template' ,  ['data' => $this->data] );
	
	}
	
	public function termsconditions(){		
	

	$this->data['view'] = 'terms_conditions';
	$this->data['tab_title'] = "TERMS AND CONDITIONS - ".setting('site.title');
	#######Breadcrumb####### 
	#-----------------------#
	
	return view('template' ,  ['data' => $this->data] );
	
	}
	public function faqs(){		
	
	$this->data['view'] = 'faqs';
	$this->data['tab_title'] = "FAQs - ".setting('site.title');
	#######Breadcrumb####### 
	#-----------------------#
	
	return view('template' ,  ['data' => $this->data] );
	
	}
	public function about(){		

	$this->data['view'] = 'about_us';
	$page_data = DB::table('about_us')->first();
	///echo "<pre>"; print_r($mydata); die('here');
	$this->data['tab_title'] = "ABOUT US - ".setting('site.title');
	$this->data['page_data'] = $page_data;
	return view('template' ,  ['data' => $this->data] );
	
	}
	public function blog(){		
	
	$this->data['view'] = 'blog_listing';
	$page_data = DB::table('about_us')->first();
	///echo "<pre>"; print_r($mydata); die('here');
	$this->data['tab_title'] = "ROME BLOG - ".setting('site.title');
	$this->data['page_data'] = $page_data;
	return view('template' ,  ['data' => $this->data] );
	
	}
	public function blogdetails(){
	$currentURL = \Request::segment(2);
	
	$post = DB::table('blog')
					->where('slug', '=', $currentURL)
                    ->get();
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
	
	$this->data['view'] = 'blog_details';
	//echo "<pre>"; print_r($post); die('here');
	$this->data['tab_title'] = $post[0]->blog_title." - ".setting('site.title');
	$this->data['page_data'] = $post;
	
	return view('template' ,  ['data' => $this->data] );
	}
	public function blogcomments(Request $request){
		
		if ($request->isMethod('post')) {
	 $enquiry = array(
				'name'=> $request->all()['name'],
				'email'=> $request->all()['email'],
				'comment'=> $request->all()['comment'],
				'created_at'=> date('Y-m-d H:i:s'),
				'blog_id'=> $request->all()['blog_id'],
				'blog_slug'=> $request->all()['slug'],
				'status'=> 'PENDING'
				);
				//echo "<pre>"; print_r($enquiry); die('dinesh');
/* 		$maildata = array(
				'name'=> $request->all()['name'],
				'email'=> $request->all()['email'],
				'mymessage'=> $request->all()['message']
				); */
				
			if(DB::table('blog_comments')->insert($enquiry)){
				
			} else { 
			
			}
				/* {
					Mail::send('emails.tour_enquiry', $maildata, function ($message) use ($maildata) {
						$message->from('tour-enquiries@wheninrometours.com',$maildata['name']);
						$message->subject('Tour Enquiries');
						$message->to('test91171@gmail.com');
				});
					Session::flash('success', 'Your query successfully sent !');
				} else {
					Session::flash('success', 'Some error occured please try again !');
				} */
				
				$message = "this is the test message! hello Laravel ";
	} 
	return redirect(url('rome-blog/'.$request->all()['slug']));
	}
	public function romelinks(){
		
	$this->data['view'] = 'rome_links';
	$this->data['tab_title'] = "ROME LINKS - ".setting('site.title');
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
	
		$slug = \Request::segment(1);

	######check view type######
	if($request->all() != NUll ){
		$this->data['view_type'] = $request->all()['view'];
	}else{
		$this->data['view_type'] ='list';
	}
	######--------------######
	
	$auth = new Post(); 
	$Cate = new categories(); 
	$this->data['view'] = 'categories_post';
	$categories = $Cate->where('slug', '=', $slug)->firstOrFail();
	if($categories->parent_id == null){
	$id =  array_keys(array_column($this->data['header'], 'parent_id'), $categories->id);
	}else{	
		$id[] = $categories->id;
	}
	$this->data['tab_title'] = $categories['name']." - ".setting('site.title');
	$this->data['allpost'] = $auth->all()->toArray();
	$this->data['categories'] = $categories;
	$this->data['posts'] = $auth->whereIn('category_id', $id)->get();
	return view('template' ,  ['data' => $this->data] );
	
	}
	
		
	

}
