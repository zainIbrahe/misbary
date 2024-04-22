<?php

namespace App\Imports;

use App\Product;
use App\NewAttributesValue;
use App\Attribute;
use App\City;
use App\ProductsSku;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use TCG\Voyager\Models\Category;

class AttributesImportClass implements ToCollection
{

    public function collection(Collection $rows)
    {
		
		 foreach ($rows as $index => $row) 
        {
				if($row[3] != ""){
					$parent = City::where("en_name",$row[3])->first();
					if($parent){
					
					$attr = new \App\City();
					$attr->name = $row[0];
					$attr->en_name = $row[1];
					$attr->ku_name = $row[2];
					$attr->parent_id = $parent->id;
						
					$attr->save();					
					}
				}
			}
			 
    	}
	}
