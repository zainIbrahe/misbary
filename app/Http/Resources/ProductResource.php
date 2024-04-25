<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Models\Category;
use Carbon\Carbon;
use DateTime;
use App\Http\Resources\AttributeResourceCollection;
class ProductResource extends JsonResource
{
	
	protected $lang;

    public function lang($value){
		if(!$value){
			$this->lang = "ar";
		}
		else{
			$this->lang = $value;
		}
        
        return $this;
    }
	
	
	
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {	
	
		$isFav = $this->resource->product ? $this->resource->product->isFav() : 0;
		$brand =$this->resource->product ?  $this->resource->product->category ? Category::find($this->resource->product->category->parent_id) : null : null;
		if($this->resource->product->main_image != null){
			$files = [$this->resource->product->main_image];
		}
		else{
			$files = [];
		}
		if($this->resource->product != null && json_decode($this->resource->product->files,true) != null){
		
		foreach(json_decode($this->resource->product->files,true) as $file){
			$file = str_replace('/var/www/vhosts/corline.store/misbary.corline.store/public/storage/','',$file);
			$file = str_replace('/var/www/vhosts/misbary.app/httpdocs/public/storage/','',$file);
			$files = [...$files,str_replace('//', '/', $file)];
		}
		}
		if($this->resource->product != null){
		
		$desc = $this->lang != "ar" ? $this->resource->product->{"{$this->lang}_description"} : $this->resource->product->description;
		}
		$date =$this->resource->product != null ?  $this->resource->product->man_year : "2000";
		
		if (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== false) {
		  $date = Carbon::createFromFormat('Y-m-d H:i:s', $date)->year;
		}
		if($this->resource->product != null){
			return [
            'id' => $this->resource->id,
            'product_id' => $this->resource->product_id,
            'price' => (string)$this->resource->price,
            'currency' => $this->resource->currency,
			'isFav' => $isFav,
            'product_name' => $this->lang != "ar" ? $this->resource->product->{"{$this->lang}_name"} : $this->resource->product->name,
            'description' => $desc != null ? $desc : "",
            'files' => $files,
			'brand' => (new CategoryResource($brand))->lang($this->lang),
            'model' => $this->resource->product->category ? (new CategoryResource($this->resource->product->category))->lang($this->lang) : null,
            'attributes' => (new AttributeResourceCollection($this->resource->attributes))->lang($this->lang),
			'sale_status' => $this->resource->sale_status ?? "available",
            'pros' => ProsConsReource::collection($this->resource->pros()),
            'cons' => ProsConsReource::collection($this->resource->cons()),
			'whatsnew' => ProsConsReource::collection($this->resource->whatsnew()),
            'created_by' => new NewUserResource($this->resource->createdBy),
			'man_year'=>(int)$date,
			'milage'=>$this->resource->product->milage,
			'region'=>$this->resource->product->region ? $this->lang != "ar" ? $this->resource->product->region->name : $this->resource->product->region->{"{$this->lang}_name"}  : "",
			'governet'=>$this->resource->product->region && $this->resource->product->region->parent  ? $this->lang != "ar" ? $this->resource->product->region->parent->{"{$this->lang}_name"} : $this->resource->product->region->parent->name : "",
			'negotiable'=>$this->resource->negotiable == 1 ? "Yes" : "No",
			'spec'=>$this->resource->specifications,
			'status' => $this->resource->product->status,
			'createdAt' => date('Y-m-d', strtotime($this->resource->product->created_at)),
        ];
		}
		else{
			return [];
		}
        
    }
}
