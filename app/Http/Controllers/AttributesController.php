<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Imports\AttributesImportClass;
use App\NewAttributesValue;
use App\Product;
use App\ProductProsCon;
use App\ProductsSku;
use Illuminate\Http\Request;
use TCG\Voyager\Models\User;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use Excel;

class AttributesController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
	public function getimport(){
		return view('import');
	}
	public function import(Request $request){
		$request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        // Process the Excel file
        \Maatwebsite\Excel\Facades\Excel::import(new AttributesImportClass, $file);
		
		return redirect()->back();
	}
	

}