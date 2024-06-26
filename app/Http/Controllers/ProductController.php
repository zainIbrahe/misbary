<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\AttributeValue;
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

class ProductController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
    public function get()
    {
        $product = ProductsSku::query()->with("product")->with("attributes.attributeType:id,type")->find(4);
        return $product;
    }

    public function getPending(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = 'products';

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::where("status", "!=", 1)->select($dataType->name . '.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%' . $search->value . '%';

                $searchField = $dataType->name . '.' . $search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name . '.*',
                        'joined.' . $row->details->label . ' as ' . $orderBy,
                    ])->leftJoin(
                            $row->details->table . ' as joined',
                            $dataType->name . '.' . $row->details->column,
                            'joined.' . $row->details->key
                        );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        return Voyager::view(
            $view,
            compact(
                'actions',
                'dataType',
                'dataTypeContent',
                'isModelTranslatable',
                'search',
                'orderBy',
                'orderColumn',
                'sortableColumns',
                'sortOrder',
                'searchNames',
                'isServerSide',
                'defaultSearchKey',
                'usesSoftDeletes',
                'showSoftDeleted',
                'showCheckboxColumn'
            )
        );
    }

    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name . '.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%' . $search->value . '%';

                $searchField = $dataType->name . '.' . $search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name . '.*',
                        'joined.' . $row->details->label . ' as ' . $orderBy,
                    ])->leftJoin(
                            $row->details->table . ' as joined',
                            $dataType->name . '.' . $row->details->column,
                            'joined.' . $row->details->key
                        );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        return Voyager::view(
            $view,
            compact(
                'actions',
                'dataType',
                'dataTypeContent',
                'isModelTranslatable',
                'search',
                'orderBy',
                'orderColumn',
                'sortableColumns',
                'sortOrder',
                'searchNames',
                'isServerSide',
                'defaultSearchKey',
                'usesSoftDeletes',
                'showSoftDeleted',
                'showCheckboxColumn'
            )
        );
    }

    public function create(Request $request)
    {
        $currencies = '[
  
            {"cc":"IQD","symbol":"\u062f.\u0639","name":"Iraqi dinar"},
            
            {"cc":"USD","symbol":"USD","name":"United States dollar"}
            
        ]';
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
            ? new $dataType->model_name()
            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }
        $attributes = Attribute::where("name", "!=", "specifications")->get();
        $attr_values = \App\NewAttributesValue::where('attribute_type', 226)->get();
        $att = Attribute::find(226);
        $specValues = $attr_values;
        return Voyager::view($view, compact('dataType', 'specValues', 'dataTypeContent', 'isModelTranslatable', 'attributes', 'currencies'));
    }


    public function edit(Request $request, $id)
    {
        $currencies = '[
  
  {"cc":"IQD","symbol":"\u062f.\u0639","name":"Iraqi dinar"},
 
  {"cc":"USD","symbol":"USD","name":"United States dollar"}
]';
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = Product::with("sku.pro", "sku.attributes.attributeType")->findOrFail($id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }
        $attributes = Attribute::where("name", "!=", "specifications")->get();
        $attr_values = \App\NewAttributesValue::where('attribute_type', 226)->get();
        $att = Attribute::find(226);
        $specValues = $attr_values;
        $sku = ProductsSku::where("product_id", $id)->first();
        $proSpecVallues = AttributeValue::where(
            [
                ["attribute_type", 226],
                ["sku_id", $sku->id],

            ]
        )->pluck("attribute_value");
        $productspecsvals = [];
        foreach ($proSpecVallues as $aa) {
            array_push($productspecsvals, $aa);
        }

        return Voyager::view($view, compact('dataType', 'productspecsvals', 'specValues', 'dataTypeContent', 'isModelTranslatable', 'attributes', 'currencies'));


    }

    public function store(Request $request)
    {
        //return $request;
        ini_set('max_execution_time', 0);

        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));


        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());
        $attributeTypes = $request->types;
        $attributeKeys = $request->keys;
        $attributeValues = $request->values;
        if (!$request->createdBy) {
            $pro = Product::find($data->id);
            $pro->created_by = 1;
            $pro->save();
        }

        $sku = new ProductsSku();
        $sku->product_id = $data->id;
        $sku->price = $request->price;
        //  TODO add user from token
        $sku->created_by = $request->created_by ?? 1;
        $sku->currency = $request->currency ?? "USD";
        $sku->save();
        $compareTypes = $request->compareTypes;
        $compareValues = $request->compareValues;
        if ($compareTypes) {

            foreach ($compareTypes as $ind => $compareType) {
                $compare = new ProductProsCon();
                $compare->type = $compareType;
                $compare->description = $compareValues[$ind];
                $compare->product_id = $sku->id;
                $compare->save();
            }
        }
        if ($request->spes) {
            foreach ($request->spes as $spec) {
                $attributeVal = new AttributeValue();
                $attributeVal->attribute_type = 226;
                $attributeVal->attribute_value = $spec;
                $attributeVal->sku_id = $sku->id;
                $attributeVal->save();
            }
        }
        if ($attributeValues) {

            foreach ($attributeValues as $index => $attVal) {
                $attributeVal = new AttributeValue();
                $ke = \App\NewAttributesValue::where("en_name", $attVal)->first();



                if (!$ke) {
                    if ($index < count($attributeTypes)) {
                        $attrKey = $attributeTypes[$index];
                        $attr = Attribute::find($attrKey);
                    }
                } else {
                    $attrKey = $ke->attribute_type;
                    $attr = Attribute::find($attrKey);
                }
                $attributeVal->attribute_type = $attr->id;
                $attributeVal->attribute_value = $attVal;
                $attributeVal->sku_id = $sku->id;
                $attributeVal->save();

                $attributeVal->save();
            }
        }


        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message' => __('voyager::generic.successfully_added_new') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        // Process the Excel file
        \Maatwebsite\Excel\Facades\Excel::import(new ProductImportClass, $file);
    }

    public function update(Request $request, $id)
    {
        // return $request;
        ini_set('max_execution_time', 0);
        $attributeTypes = $request->types;
        $attributeKeys = $request->keys;
        $attributeValues = $request->values;

        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        $query = $model->query();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $query->withTrashed();
        }

        $data = $query->findOrFail($id);

        // Check permission
        $this->authorize('edit', $data);

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        // Get fields with images to remove before updating and make a copy of $data
        $to_remove = $dataType->editRows->where('type', 'image')
            ->filter(function ($item, $key) use ($request) {
                return $request->hasFile($item->field);
            });
        $original_data = clone ($data);

        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);
        $sku = ProductsSku::where("product_id", $id)->first();
        if ($request->created_by) {
            $sku->created_by = $request->created_by;
        }
        $sku->currency = $request->currency ?? "USD";
        $sku->save();
        $attrsss = AttributeValue::where("sku_id", $sku->id)->get();
        foreach ($attrsss as $ar) {
            $ar->delete();
        }
        $conss = ProductProsCon::where("product_id", $sku->id)->get();

        foreach ($conss as $co) {
            $co->delete();
        }
        $compareTypes = $request->compareTypes;
        $compareValues = $request->compareValues;
        if ($compareTypes) {
            foreach ($compareTypes as $ind => $compareType) {
                $compare = new ProductProsCon();
                $compare->type = $compareType;
                $compare->description = $compareValues[$ind];
                $compare->product_id = $sku->id;
                $compare->save();
            }
        }
        if ($attributeValues) {
            //return [$attributeKeys,$attributeValues,$attributeTypes];
            if ($request->spes) {
                foreach ($request->spes as $spec) {
                    $attributeVal = new AttributeValue();
                    $attributeVal->attribute_type = 226;
                    $attributeVal->attribute_value = $spec;
                    $attributeVal->sku_id = $sku->id;
                    $attributeVal->save();
                }
            }
            foreach ($attributeValues as $index => $attVal) {
                $attributeVal = new AttributeValue();
                $ke = \App\NewAttributesValue::where("en_name", $attVal)->first();
                if ($ke) {
                    $attrKey = $ke->attribute_type;
                    $attr = Attribute::find($attrKey);
                } else {
                    if ($index < count($attributeTypes)) {
                        $attrKey = $attributeTypes[$index];
                        $attr = Attribute::find($attrKey);
                    }
                }

                if ($attr) {
                    $attributeVal->attribute_type = $attr->id;
                    $attributeVal->attribute_value = $attVal;
                    $attributeVal->sku_id = $sku->id;
                    $attributeVal->save();

                    $attributeVal->save();
                }
            }
        }

        // Delete Images
        $this->deleteBreadImages($original_data, $to_remove);

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message' => __('voyager::generic.successfully_updated') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }


}
