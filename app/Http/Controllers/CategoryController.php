<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Imports\ProductImportClass;
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

class CategoryController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
    public function store(Request $request)
    {
        try{
            // $this->validate($request,[
            //     "slug"=> "unique:categories,slug"
            // ]);
            $slug = $this->getSlug($request);

    
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
            
            // Check permission
            $this->authorize('add', app($dataType->model_name));
            $request->merge([
                'slug' => $request->slug.$this->randomString(3)
            ]);
            // return $request;
            // Validate fields with ajax
            $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
            $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());
    
            event(new BreadDataAdded($dataType, $data));
    
            if (!$request->has('_tagging')) {
                if (auth()->user()->can('browse', $data)) {
                    $redirect = redirect()->route("voyager.{$dataType->slug}.index");
                } else {
                    $redirect = redirect()->back();
                }
    
                return $redirect->with([
                    'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                    'alert-type' => 'success',
                ]);
            } else {
                return response()->json(['success' => true, 'data' => $data]);
            }
        }
        catch(\Exception $e){
            $redirect = redirect()->back();
            return $e->getMessage();
            return  $redirect->with([
                'message'    => __('voyager::generic.internal_error')." {$e->getMessage()} ",
                'alert-type' => 'danger',
            ]);
        }
       
    }




}
