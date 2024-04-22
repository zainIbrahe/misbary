<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPostLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
			
            // Replace 'plan_id' and 'post_limit' with your actual table columns
            $postLimit = $user->plan->posts_num;

            // Get the user's current post count (modify based on your model relations)
            $currentPostCount = \App\Product::where("created_by",$user->id)->count();
			
			if($user->post_num > $currentPostCount){
				$currentPostCount = $user->post_num;
			}
			
			

            if ($currentPostCount > $postLimit) {
				return response()->json([
					"message" => "You have reached your post limit for your current plan.!",
					"data" => null,
					"status" => 0
				]);
            }
        }
		

        return $next($request);
    }
}