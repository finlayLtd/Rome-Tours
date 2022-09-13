<?php
namespace App\Http\Middleware;
use Closure;
class RedirectIfNotVendor
{
    
    public function handle($request, Closure $next, $guard="vendor")
    {
        if(!auth()->guard($guard)->check()) {
			
            return redirect(route('vendor.login'));
        }
        return $next($request);
    }
}