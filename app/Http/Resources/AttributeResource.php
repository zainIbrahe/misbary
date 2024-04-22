<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AttributeResourceCollection;
use App\NewAttributesValue;
class AttributeResource extends JsonResource
{
	

   	protected $lang;

    public function lang($value){
        $this->lang = $value;
        return $this;
    }
   
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
		$attValue = NewAttributesValue::where("name",$this->resource->attribute_value)->first();
		 $name =$attValue ? $this->lang != null  && $this->lang != "ar" ? $attValue->{"{$this->lang}_name"} : $attValue->name : $this->resource->attribute_value;
        return [
            'id' => $this->resource->id,
            'attribute_value' =>   $this->lang != null && $this->lang != "ar" ? $attValue->{"{$this->lang}_name"} : $name ,
            'sku_id' => $this->resource->sku_id,
			'att_id' => $this->resource->attributeType !=null  ?   $this->resource->attributeType->id : "",
			'type'=>	$this->resource->attributeType !=null  ?   $this->resource->attributeType->type : "",
			'attribute_type' => $this->resource->attributeType  ? $this->lang != null && $this->lang != "en" ? $this->resource->attributeType->{"{$this->lang}_name"} : $this->resource->attributeType->name : ""
        ];
    }
	public static function collection($resource){
        return new AttributeResourceCollection($resource);
    }
}
