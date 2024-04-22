<?php

namespace App\Http\Resources;

use App\Product;
use App\ProductsSku;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewUserResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $posts = ProductsSku::where('created_by', 1)->count();
		$avatar = str_replace("/var/www/vhosts/misbary.app/httpdocs/public/storage/","",$this->resource->avatar);
		$avatar = str_replace("/var/www/vhosts/corline.store/misbary.corline.store/public/storage/","",$avatar);
		$pendingPosts = [];
		$posts = [];
		$cover = str_replace("/var/www/vhosts/corline.store/misbary.corline.store/public/storage/","",$this->resource->cover);
		$cover = str_replace("/var/www/vhosts/misbary.app/httpdocs/public/storage/","",$cover);
		

		$userPosts = $this->resource->carPosts;
		if($userPosts != null){
			foreach($userPosts as $post){
				if($post->status == 1){
					array_push($posts,$post);
				}
				else{
					array_push($pendingPosts,$post);
				}
			}
		}			
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'avatar' => $avatar,
			'phone' => $this->resource->phone,
			'website' => $this->resource->website,
			'avatar' => $avatar,
			'cover'=> $cover,
			'working_to' => $this->resource->working_to,
			'working_from' => $this->resource->working_from,
			'description' => $this->resource->description,
            'plan' => new PlanResource($this->resource->plan),
            'posts_left' => $this->resource->plan != null ? $this->resource->plan->posts_num - $this->resource->posts : 0,
			
			'stories' => StoryNewResource::collection($this->resource->stories)
        ];
    }
}
