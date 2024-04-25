<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\ProductsSku;
class ReelsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
		
		$file = str_replace("/var/www/vhosts/corline.store/misbary.corline.store/public/storage/","",$this->resource->file);
		$proSku = ProductsSku::whereHas("product")->where("product_id",$this->resource->product)->first();
        return [
					'id' => 1,
					'name'=>$this->resource->name? $this->resource->name : "",
					'description'=>$this->resource->description ? $this->resource->description : "",
					'file'=> is_array(json_decode($file)) ? json_decode($file)[0] : "", 
					'product'=>  new ProductResource($proSku),
					'createdBy' => new UserResource($this->resource->created_bys),
					'likes'=>15,
					
					'comments'=>[
						[
							'id'=>'1',
							'comment'=>'Comment Content',
							'written_by'=> ""
						],
						[
							'id'=>'1',
							'comment'=>'Comment Content',
							'written_by'=> ""
						]	
					]
				];
    }
}