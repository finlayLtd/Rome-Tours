<?php

namespace App\Http\Controllers\Vendor;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Post;
use App\categories;
use App\Map;  
use App\Blog;  
use App\Slider;
use Session;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function __construct( ){
		$Cate = new categories(); 
		$this->data['headers'] = $Cate->all()->sortBy("order");
		$results1 = DB::table('categories')
							   ->select('id')
							   ->orderBy('id', 'ASC')
							   ->get();
			foreach($results1 as $res){
				$categoreis_list[] = $res->id;
			}		
		$results = Categories::all()->sortBy("name");
		$aut = new Slider();
		$this->data['slides'] = $aut->firstOrFail();
		foreach($results as $key => $result){			
			$parent_id = $result->id;
			$toursCount =	 DB::table('category_post')
									->select(DB::raw('COUNT(category_post.category_id) as tours'))
									->whereIn( 'category_post.category_id', function ( $query ) use ($parent_id) {
											$query->select( 'id' )
												->from( 'categories' )
												->whereRaw("categories.parent_id = $parent_id");
										} )
									->orWhereRaw("category_post.category_id = $parent_id")
									->get();
			$results[$key]['toursCount'] = $toursCount[0]->tours;		
			$new_header[] = (object) $result;
		} 
		 $this->data['header']=$new_header;
		 $this->data['mycat']=$categoreis_list;
	}
	public function index( Request $request ){

		//$cat = auth()->guard('vendor')->user()->category;
		$cat = 28;
		$Cates = new categories();
		$categories = $Cates->where('id', '=', $cat)->firstOrFail();
		return redirect($categories['slug']);
	}
	
	public function vendor_categories( Request $request){
		//$cat = auth()->guard('vendor')->user()->category;
		$cat = 38;
		$slug = \Request::segment(1);
		$auth = new Post(); 
		$Cate = new categories(); 
		$categories = $Cate->where('slug', '=', $slug)->firstOrFail();
		if($categories->id != $cat){
			abort(404);
		}
		$this->data['view_type'] ='list';
		$this->data['view'] = 'vendor_categories_post';
		if($categories->parent_id == null){
			$ids =  array_keys(array_column($this->data['headers']->toArray(), 'parent_id'), $categories->id);
			$results = $Cate->where('parent_id','=',$categories->id)->get()->toArray();
			if($results!= null){
				foreach($results as $idss){
					$id[] = $idss['id']; 
				}
			} else
			{
				$id[] = $categories->id;
			}
		} else {
				$id[] = $categories->id;
		}
		$this->data['seo_title'] = $categories['seo_title'];
		$this->data['meta_description'] = $categories['meta_description'];
		$this->data['tab_title'] = $categories['name']." - WHEN IN ROME TOURS";
		$this->data['allpost'] = $auth->all()->where('status', '=', "PUBLISHED")->toArray();
		$this->data['categories'] = $categories;
		$this->data['posts'] = $this->tourFilters($auth,$id,$request->all());
		return view('vendor-template' ,  ['data' => $this->data] );
	 
	}
	public function tourFilters($auth,$id,$filtersArr){
		if(array_key_exists('tour_types', $filtersArr)){
		
			$column = 'price1';
			$order = $_GET['s'];
		} else {

			$column = 'tour_order';
			$order = 'asc';
		}
		$queryBuilder = 	DB::table('posts')
									->join('category_post', 'posts.id', '=', 'category_post.post_id')
									->where('posts.status', '=', 'PUBLISHED')
									->whereIn('category_post.category_id', $id)
									->orderBy($column, $order)					
									->get();						

		if(array_key_exists('price_filter', $filtersArr))
		{
			$priceFilterArr = $filtersArr['price_filter'];       
			$queryBuilder = $queryBuilder->when(in_array('0', $priceFilterArr), function($query){
				
				return $query->where('price1', '<=', '50');
			
			})
			->when(in_array('1', $priceFilterArr), function($query){
				
				return $query->where('price1', '>=', '50')
				->Where('price1', '<=', '80');
			
			})
			->when(in_array('2', $priceFilterArr), function($query){
				
				return $query->where('price1', '>=', '80')
				->Where('price1', '<=', '100');
				
			})
			->when(in_array('3', $priceFilterArr), function($query){
				
				return $query->where('price1', '>=', '100');
			
			});
		}

	   if(array_key_exists('rating_filter', $filtersArr))
	   {
			$ratingFilterArr = $filtersArr['rating_filter'];
			$queryBuilder = $queryBuilder->when(in_array('5', $ratingFilterArr), function($query){
					return $query->where('rating', 5);
				})
				->when(in_array('4', $ratingFilterArr), function($query){
					return $query->where('rating', 4);
				})
				->when(in_array('3', $ratingFilterArr), function($query){
					return $query->where('rating', 3);
				})
				->when(in_array('2', $ratingFilterArr), function($query){
					return $query->where('rating', 2);
				})
				->when(in_array('1', $ratingFilterArr), function($query){
					return $query->where('rating', 1);
				});
	   }
   
	   if(array_key_exists('facility_filter', $filtersArr))
	   {
			for($i=0; $i< count($_GET['facility_filter']); $i++){
			 $ID = array_keys(array_column($this->data['facilities']->toArray(), 'id'), $_GET['facility_filter'][$i]);
			 $arryfacilities[] = $this->data['facilities']->toArray()[$ID[0]]->f_title;

			}
		 
			foreach($arryfacilities as $facility){
				$postIds = Post::select('id')->where('status', '=', "PUBLISHED")->where('top_instruction', 'LIKE', '%'.$facility.'%')->pluck('id')->toArray();
			}
			
			$queryBuilder = $queryBuilder->whereIn('id', $postIds);

		}
	return $queryBuilder;
	}
 	public function vendor_parent_cat_tour($slug, Request $request)
	{
		//$cat = auth()->guard('vendor')->user()->category;
		$cat = 38;
		$catSlug = \Request::segment(1);
		$Cates = new categories(); 
		$categoriess = $Cates->where('slug', '=', $catSlug)->firstOrFail();
		if($categoriess->id != $cat){
			abort(404);
		} 
		
		$auth = new Post(); 
		$Cate = new categories(); 
		$this->data['categorie'] = $Cate->all()->toArray();
		$this->data['postdetils'] = $auth->where('slug', '=', $slug)->firstOrFail();
		$this->data['tab_title'] = $this->data['postdetils']->title." - WHEN IN ROME TOURS";
		$gallery_images = DB::select("select * from pics where post_id = ?", [$this->data['postdetils']->id]);
		if(count($gallery_images)>0)
		{
			$this->data['gallery_images'] = json_decode($gallery_images[0]->Images);
			$this->data['gallery_images_titles'] = array();
			$this->data['gallery_images_alt'] = array();
			
		} else
		{
			$this->data['gallery_images'] = array();
			$this->data['gallery_images_titles'] = array(); 
			$this->data['gallery_images_alt'] = array();
		}
		$this->data['tour_galleries'] = DB::select("select * from tour_galleries where status ='PUBLISHED' AND tour_id = ?", [$this->data['postdetils']->id]);
		$this->data['review_comment'] = DB::select("select * from review_comments where status ='PUBLISHED' AND tour_id = ?", [$this->data['postdetils']->id]);
		$this->data['video_slides'] = DB::select("select * from tour_slider_videos where tour_name = ?", [$this->data['postdetils']->id]);
		$this->data['sidebar_data'] = DB::select("select * from sidebars where status = 'PUBLISHED' ORDER BY `order` ASC ");
		$this->data['tripadvisor_image'] = DB::select("select * from tripadvisor_reviews where status = 1 order by created_at DESC ");
		$this->data['seo_title'] = $this->data['postdetils']->seo_title;
		$this->data['meta_description'] = $this->data['postdetils']->meta_description;
		$this->data['view'] = 'vendor_post_detail';
		#######Breadcrumb#######
		$Cate = new categories(); 
		$segment1 = \Request::segment(1);
		$categories = $Cate->where('slug', '=', $segment1)->firstOrFail();
		if($categories->parent_id != NULL){
			$categoriespr = $Cate->where('id', '=', $categories->parent_id)->firstOrFail();
			$this->data['categoriespr'] = $categoriespr;
		}
		$this->data['categories'] = $categories;
		#-----------------------#
		return view('vendor-template' ,  ['data' => $this->data] );
	}
	
	public function vendor_tour($pram ,$slug, Request $request)
	{	
		$auth = new Post(); 
		$Cate = new categories(); 
		$this->data['categorie'] = $Cate->all()->toArray();
		$this->data['postdetils'] = $auth->where('slug', '=', $slug)->firstOrFail();
		$this->data['tab_title'] = $this->data['postdetils']->title." - WHEN IN ROME TOURS";
		$gallery_images = DB::select("select * from pics where post_id = ?", [$this->data['postdetils']->id]);
		if(count($gallery_images)>0){
			$this->data['gallery_images'] = json_decode($gallery_images[0]->Images);
			$this->data['gallery_images_titles'] = json_decode($gallery_images[0]->image_title);
			$this->data['gallery_images_alt'] = json_decode($gallery_images[0]->image_alt);
		} else {
			$this->data['gallery_images'] = array();
			$this->data['gallery_images_titles'] = array(); 
			$this->data['gallery_images_alt'] = array();
		}
		$this->data['tour_galleries'] = DB::select("select * from tour_galleries where status ='PUBLISHED' AND tour_id = ?", [$this->data['postdetils']->id]);
		$this->data['review_comment'] = DB::select("select * from review_comments where status ='PUBLISHED' AND tour_id = ?", [$this->data['postdetils']->id]);
		
		$this->data['video_slides'] = (array) DB::select("select * from tour_slider_videos where tour_name = ?", [$this->data['postdetils']->id]);
		$this->data['sidebar_data'] = DB::select("select * from sidebars where status = 'PUBLISHED' ORDER BY `order` ASC ");
		$this->data['tripadvisor_image'] = DB::select("select * from tripadvisor_reviews where status = 1 order by created_at DESC ");
		$this->data['view'] = 'vendor_post_detail';
		$this->data['seo_title'] = $this->data['postdetils']->seo_title;
		$this->data['meta_description'] = $this->data['postdetils']->meta_description;
		#######Breadcrumb#######
		$Cate = new categories(); 
		$segment2 = \Request::segment(2);
		//$categories = $Cate->where('id', '=', $this->data['postdetils']->category_id)->firstOrFail();
		$categories = $Cate->where('slug', '=', $segment2)->firstOrFail();
		$categoriespr = $Cate->where('id', '=', $categories->parent_id)->firstOrFail();
		$this->data['categories'] = $categories;
		$this->data['categoriespr'] = $categoriespr;
		#-----------------------#
		return view('vendor-template' ,  ['data' => $this->data] );
	
	}
	
	
}
