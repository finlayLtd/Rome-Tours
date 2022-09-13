<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Session;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
class VendorLoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use AuthenticatesUsers;
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/rome/home';
	
    public function __construct()
    {
		
      //$this->middleware('guest')->except('logout');
	  $this->middleware('guest', ['except' => ['logout', 'getLogout']]);
    }
    public function guard()
    {
      return auth()->guard('vendor');
    }
    public function showLoginForm()
    {
		session(['link' => url()->previous()]);
        return view('auth.vendor-login');
    }
	
	
	public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if ($credentials) {
            // Authentication passed...
			//echo "Hello";
			return redirect('rome/home');
            
        }
    }
	
	
	
	
	protected function authenticated(Request $request, $user)
	{
		//dd(session('link'));
		if(session('link') != 'https://wheninrometours.com'){
			return redirect(session('link'));
		}
		
	}
	public function logout(Request $request)
    {
        Auth::logout();
		Session::flush();
		return redirect('https://google.com/');
	}
}