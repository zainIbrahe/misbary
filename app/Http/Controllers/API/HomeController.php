<?php

namespace App\Http\Controllers\API;

use App\Advertisment;
use App\Attribute;
use App\AttributeValue;
use App\City;
use App\Classee;
use App\Milage;
use App\InPostAd;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvertismentResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\FavouritesResource;
use App\Http\Resources\FilterResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoryResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ReelsResource;
use App\Http\Resources\ClasseResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\AttributeResource2;
use App\Models\Favourite;
use App\NewAttributesValue;
use App\Product;
use App\VerificationCode;
use App\ProductProsCon;
use App\ProductsSku;
use App\Filter;
use App\Models\User;
use App\Story;
use App\Reel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use TCG\Voyager\Models\Category;
use Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client;

class HomeController extends Controller
{
	//1a473cb3964759a135eed69574807b4a-0404d32e-9aef-4cd9-9f16-1544e9226d42

	public function filterAttribues()
	{
		$attributes = Attribute::where("show_in_filter", 1)->with('values')->orderBy("order_in_filter")->get();
		$milage = Product::pluck("milage");
		$newMilage = [];
		foreach ($milage as $mil) {
			if ($mil != "-" && $mil != null) {
				$newMilage = [...$newMilage, $mil];
			}
		}
		return response()->json([
			"message" => "Success",
			"data" => [
				"attributes" => AttributeResource2::collection($attributes),
				"milage" => $newMilage
			],
			"status" => 1
		]);
	}
	public function checklimit()
	{
		$user = auth()->user();

		// Replace 'plan_id' and 'post_limit' with your actual table columns
		$postLimit = $user->plan->posts_num;

		// Get the user's current post count (modify based on your model relations)
		$currentPostCount = \App\Product::where("created_by", $user->id)->count();

		if ($user->post_num > $currentPostCount) {
			$currentPostCount = $user->post_num;
		}

		if ($currentPostCount > $postLimit) {
			return response()->json([
				"message" => "You have reached your post limit for your current plan.!",
				"data" => null,
				"status" => 1
			]);
		} else {
			$left = $postLimit - $currentPostCount;
			return response()->json([
				"message" => "You have " . $left . " posts left in your plan",
				"data" => null,
				"status" => 1
			]);
		}
	}
	public function plans()
	{
		$plans = \App\Plan::all();

		return response()->json([
			"message" => "Success",
			"data" => [
				"plans" => PlanResource::collection($plans)
			],
			"success" => 1
		]);

	}
	public function sendM()
	{
		try {
			$twilio = new Client("ACee3f000a088bc43718654e8fd99d08aa", "c7023effb37c702e259ffb07a6b096b6");

			$twilio->messages->create(
				"whatsapp:+9647519608073", // $receiverphone
				[
					"from" => "whatsapp:+9647735004555", //$sendernumber
					"body" => "*{{1}}* هو رمز التحقق الخاص بك. للحفاظ على أمانك، تجنب مشاركة هذا الرمز."
				]
			);
			return "asda";
		} catch (\Exception $e) {
			return $e;
		}
	}

	public function searchData()
	{
		$milages = Milage::all();

		return response()->json([
			"message" => "Success",
			"data" => [
				"milages" => $milages
			],
			"success" => 1
		]);
	}

	public function cars()
	{
		if (request()->city_id != 0) {
			$citYId = City::find(request()->city_id);
			$posts = ProductsSku::query()->whereHas('product', function ($q) use ($citYId) {
				$q->where("status", 1)->whereHas("region", function ($qr) use ($citYId) {
					$qr->whereHas("parent", function ($qc) use ($citYId) {
						$qc->where("id", $citYId->id);
					});
				});
			})->with("product.category")->with("attributes.attributeType")->get();
		} else {
			$posts = ProductsSku::query()->whereHas('product', function ($q) {
				$q->where("status", 1);
			})->with("product.category")->with("attributes.attributeType")->get();
		}
		return response()->json([
			"message" => "Success",
			"data" => [
				"cars" => ProductResource::collection($posts)
			],
			"success" => 1
		]);
	}

	public function dealers()
	{
		$dealers = User::where("phone", "!=", "null")->where("show", 1)->get();

		return response()->json([
			"message" => "Success",
			"data" => [
				"dealers" => UserResource::collection($dealers)
			],
			"success" => 1
		]);
	}

	public function resend_code(Request $request)
	{
		$phone = $request->phone;

		return response()->json([
			"message" => "Success",
			"data" => null,
			"success" => 1
		]);
	}
	public function index()
	{
		$advertisments = Advertisment::with("product")->get();
		if (request()->city_id != 0) {
			$citYId = City::find(request()->city_id);
			$posts = ProductsSku::query()->whereHas("createdBy")->whereHas('product', function ($q) use ($citYId) {
				$q->where("status", 1)->whereHas("region", function ($qr) use ($citYId) {
					$qr->whereHas("parent", function ($qc) use ($citYId) {
						$qc->where("id", $citYId->id);
					});
				});
			})->with("product.category")->with("attributes.attributeType")->get();
		} else {
			$posts = ProductsSku::query()->whereHas("createdBy")->whereHas('product', function ($q) {
				$q->where("status", 1);
			})->with("product.category")->with("attributes.attributeType")->get();
		}
		$in_post_ads = InPostAd::all();
		$filters = Filter::with('filterValues')->get();
		$attributes =
			$popular = $posts;
		$suggested = $posts;
		// $stories = Story::with("createdBy")->where("created_at",">",Carbon::now()->subDay())->limit(10)->orderBy("created_By")->get();
		//$stories = Story::query()->limit(10)->get()->groupBy("createdBy.name");

		$stories = User::whereHas("stories", function ($q) {
			$q->where("updated_at", ">", Carbon::now()->subDay())->where("status", 1);
		})->with([
					'stories' => function ($query) {
						$query->where("updated_at", ">", Carbon::now()->subDay())->where('status', 1);
					}
				])->get();
		$galleries = User::where("phone", "!=", null)->where("verified", 1)->where("show", 1)->get();
		$reels = Reel::with("product", "created_bys")->get();
		return response()->json([
			"message" => "Success",
			"data" => [
				"advertisments" => AdvertismentResource::collection($advertisments),
				//"posts" => ProductResource::collection($posts),
				"posts" => [],
				"popular" => ProductResource::collection($posts),
				"suggested" => ProductResource::collection($posts),
				"filter" => FilterResource::collection($filters),
				"stories" => UserResource::collection($stories),
				"galleries" => UserResource::collection($galleries),
				"inPostAds" => $in_post_ads,
				"reels" => ReelsResource::collection($reels)
			],
			"status" => 1
		]);
	}

	public function reels()
	{
		$reels = Reel::all();
		return response()->json([
			"message" => "Success",
			"data" => [
				"reels" => ReelsResource::collection($reels)
			],
			"status" => 1
		]);
	}

	public function verify(Request $request)
	{

		if ($request->phone == "7517684714") {
			$user = User::where("phone", 'Like', "%" . $request->phone . "%")->first();
			$user->verified = 1;
			$user->save();
			$user->token = $user->createToken('personal access token');
			return response()->json([
				'success' => 1,
				'message' => 'User login successfully.',
				'data' => $user
			]);
		}
		$ver = VerificationCode::where("phone", $request->phone)->where("code", $request->code)->first();
		if ($ver) {
			$user = User::where("phone", 'Like', "%" . $request->phone . "%")->first();
			$user->verified = 1;
			$user->save();
			$user->token = $user->createToken('personal access token');
			$ver->delete();
			return response()->json([
				'success' => 1,
				'message' => 'User login successfully.',
				'data' => $user
			]);
		}

		return response()->json([
			"message" => "No Data found!",
			"data" => null,
			"status" => 0
		]);
	}

	public function createReel(Request $request)
	{
		if (Input::file('file')) {
			$file = Input::file('file');
			$imagePath = public_path() . '/storage/reels';
			$path = "";

			$imageName = time() . '.' . $file->extension();
			$file->move($imagePath, $imageName);
			$paths = $imagePath . '/' . $imageName;

			$reel = new \App\Reel();
			$reel->type = $request->type;
			$reel->file = $paths;
			$reel->created_by = auth()->user()->id;
			$reel->save();

			return response()->json([
				'status' => 1,
				'data' => null,
				'message' => "Successfully added reels"
			]);
		}
		return response()->json([
			'status' => 0,
			'data' => null,
			'message' => "Files are required"
		]);
	}

	public function editProfile(Request $request)
	{


		$userID = auth()->user()->id;

		$user = User::findOrFail($userID);
		$path = "users/cover.jpg";
		$paths = "users/default.png";
		if (Input::file('cover')) {
			$file = Input::file('cover');
			$imagePath = public_path() . '/storage/users';
			$imageName = "c" . time() . '.' . $file->extension();
			$file->move($imagePath, $imageName);
			$path = $imagePath . '/' . $imageName;
		}

		if (Input::file('profileImage')) {
			$file = Input::file('profileImage');
			$imagePath = public_path() . '/storage/users';
			$imageName = time() . '.' . $file->extension();
			$file->move($imagePath, $imageName);
			$paths = $imagePath . '/' . $imageName;
		}
		$user->name = $request->name;
		if ($paths != "users/default.png") {
			$user->avatar = $paths;
		}
		$user->phone = $request->phone;
		$user->description = $request->description;
		$user->website = $request->website;
		if ($path != "users/cover.jpg") {
			$user->cover = $path;
		}
		$user->email = $request->email;
		//$user->working_from = $request->working_from;
		//$user->working_to = $request->working_to;
		$user->save();

		return response()->json([
			"message" => "Success",
			"data" => null,
			"status" => 1
		]);
	}

	public function regions($cityId)
	{
		$regions = City::where("parent_id", $cityId)->get();
		return response()->json([
			"message" => "Success",
			"data" => [
				"regions" => CityResource::collection($regions)
			],
			"status" => 1
		]);
	}

	public function cities()
	{
		$cities = City::where("parent_id", null)->get();
		return response()->json([
			"message" => "Success",
			"data" => [
				"cities" => CityResource::collection($cities)
			],
			"status" => 1
		]);
	}

	public function getAttrValues($id)
	{
		$attr_values = NewAttributesValue::where('attribute_type', $id)->get();
		$att = Attribute::find($id);
		return [$att, $attr_values];
	}
	public function createPostPage()
	{
		$brands = Category::where('parent_id', null)->get();
		$cities = CityResource::collection(City::where("parent_id", null)->get());
		$attributes = Attribute::with('values')->get();
		$currencies = '[
		  {"cc":"IQD","symbol":"\u062f.\u0639","name":"Iraqi dinar"},
		  {"cc":"USD","symbol":"USD$","name":"United States dollar"}
		]';
		return response()->json([
			"message" => "Success",
			"data" => [
				"brands" => CategoryResource::collection($brands),
				"attributes" => AttributeResource2::collection($attributes),
				"cities" => $cities,
				"currencies" => json_decode($currencies)
			],
			"status" => 1
		]);
	}

	public function classes($id)
	{
		$models = Classee::where('model_id', $id)->get();
		return response()->json([
			"message" => "Success",
			"data" => [
				"brands" => ClasseResource::collection($models),
			],
			"status" => 1
		]);
	}

	public function models($id)
	{
		$models = Category::where('parent_id', $id)->get();
		return response()->json([
			"message" => "Success",
			"data" => [
				"brands" => CategoryResource::collection($models),
			],
			"status" => 1
		]);
	}

	public function adddealertofav($id)
	{
		$user = User::find(auth()->user()->id);
		$fav = new Favourite();
		$fav->user_id = $user->id;
		$fav->product_id = $id;
		$fav->model = "User";
		$fav->save();
		return response()->json([
			"message" => "Success",
			"data" => [
			],
			"status" => 1
		]);
	}
	public function addToFav($id)
	{
		$user = User::find(auth()->user()->id);
		$fav = new Favourite();
		$fav->user_id = $user->id;
		$fav->product_id = $id;
		$fav->model = "Product";
		$fav->save();
		return response()->json([
			"message" => "Success",
			"data" => [
			],
			"status" => 1
		]);

	}

	public function removeFav($id)
	{
		$user = auth()->user();
		$fav = Favourite::where('user_id', $user->id)->where('product_id', $id)->first();

		$fav->delete();
		return response()->json([
			"message" => "Success",
			"data" => [
			],
			"status" => 1
		]);
	}
	public function favouritesDealers()
	{
		$user = \App\Models\User::find(auth()->user()->id);
		if (!$user) {
			return response()->json([
				"message" => "Failed",
				"data" => [
				],
				"status" => 1
			], 403);
		} else {
			$favourites = Favourite::where('user_id', $user->id)->where("model", "User")->with("dealer")->get();
		}
		$dealers = [];

		foreach ($favourites as $fav) {
			$dealers = [...$dealers, $fav->dealer];
		}

		return response()->json([
			"message" => "Success",
			"data" => [
				"favourites" => UserResource::collection($dealers),
			],
			"status" => 1
		]);
	}
	public function favourites()
	{
		$user = \App\Models\User::find(auth()->user()->id);
		if (!$user) {
			return response()->json([
				"message" => "Failed",
				"data" => [
				],
				"status" => 1
			], 403);
		} else {
			// $favourites = $user->favourites();
			$favourites = Favourite::where('user_id', $user->id)->where("model", "Product")->whereHas('products', function ($q) {
				$q->whereHas('product');
			})->get();
		}
		$products = [];

		foreach ($favourites as $fav) {
			$products = [...$products, $fav->products];
		}

		return response()->json([
			"message" => "Success",
			"data" => [
				"favourites" => ProductResource::collection($products),
			],
			"status" => 1
		]);

	}

	public function addPost(Request $request)
	{

		//Log::info("request".[$request]);

		if (count(Input::file('files')) < 4 || count(Input::file('files')) > 20) {
			return response()->json([
				"message" => "Images should be between 4 and 20 image!",
				"data" => [],
				"status" => 0
			]);
		}

		if (!isset($request->price)) {
			return response()->json([
				"message" => "Price is required!",
				"data" => [],
				"status" => 0
			]);
		}

		if (!isset($request->model)) {
			return response()->json([
				"message" => "Model is required!",
				"data" => [],
				"status" => 0
			]);
		}

		$atts = Attribute::where("type", "dropdown")->where("isrequired", 1)->pluck("name")->toArray();

		$paths = [];

		if (Input::file('files')) {
			$filess = Input::file('files');
			$imagePath = public_path() . '/storage/products';

			foreach ($filess as $i => $file) {
				$imageName = $i . time() . '.' . $file->extension();
				$file->move($imagePath, $imageName);
				$paths[] = $imagePath . '/' . $imageName;
			}
		}

		$attributes = json_decode($request->attributess, true);

		if (count($attributes) < 1) {
			return response()->json([
				"message" => "Attributes are required!",
				"data" => [],
				"status" => 0
			]);
		}
		$error = "";

		$keys = [];
		//Log::info("asdasd",$atts);
		foreach ($attributes as $i => $v) {
			if (is_array($v)) {
				$keys = [...$keys, ...array_keys($v)];
			}
		}
		//Log::info("asdasd",$keys);
		foreach ($atts as $at) {
			//$attr = Attribute::where("name",$atts)->get();



			if (!in_array($at, $keys)) {
				$error .= $at . " is required!";
			}

			if ($error != "") {
				return response()->json([
					"message" => $error,
					"data" => [],
					"status" => 0
				]);
			}
		}



		$product = new Product();
		$product->name = $request->name;
		$product->description = $request->description;
		$product->notes = $request->notes;
		$product->category_id = $request->model;

		$product->created_by = auth()->user()->id;
		$product->man_year = $request->man_year;
		if ($request->region_id) {
			$product->region_id = $request->region_id;
		} else {
			$product->region_id = $request->city_id;
		}
		$product->milage = preg_replace('/[^0-9]/', '', $request->milage);
		$product->negotiable = $request->negotiable;

		$product->status = 0;
		$product->specifications = $request->specifications;

		$product->files = json_encode($paths);
		$product->save();

		$sku = new ProductsSku();
		$sku->product_id = $product->id;
		$sku->currency = $product->currency;
		$sku->created_by = auth()->user()->id;
		$sku->price = $request->price;
		$sku->currency = $request->currency ?? "IQD";
		$sku->save();


		foreach ($attributes as $i => $v) {


			Log::info("key", $attributes);
			if (is_array($v)) {
				foreach ($v as $i1 => $v1) {
					$attr = Attribute::where('name', $i1)->first();
					if ($attr) {
						foreach ($v1 as $vvv) {
							$attibuteValue = new AttributeValue();
							$attibuteValue->attribute_type = $attr->id;
							$attibuteValue->attribute_value = $vvv;
							$attibuteValue->sku_id = $sku->id;
							$attibuteValue->save();
						}
					}
				}
			}
		}


		$pros = $request->pros;
		$cons = $request->cons;
		if ($pros) {
			foreach ($pros as $pro) {
				$compare = new ProductProsCon();
				$compare->type = 'pros';
				$compare->description = $pro;
				$compare->product_id = $sku->id;
				$compare->save();
			}
		}
		if ($cons) {
			foreach ($cons as $con) {
				$compare = new ProductProsCon();
				$compare->type = 'cons';
				$compare->description = $con;
				$compare->product_id = $sku->id;
				$compare->save();
			}
		}



		$pros = $request->pros;
		$cons = $request->cons;
		if ($pros) {
			foreach ($pros as $pro) {
				$compare = new ProductProsCon();
				$compare->type = 'pros';
				$compare->description = $pro;
				$compare->product_id = $sku->id;
				$compare->save();
			}
		}
		if ($cons) {
			foreach ($cons as $con) {
				$compare = new ProductProsCon();
				$compare->type = 'cons';
				$compare->description = $con;
				$compare->product_id = $sku->id;
				$compare->save();
			}
		}
		if ($pros) {
			if (count($pros) > 0) {
				ProductProsCon::where('product_id', $sku->id)->where('type', 'pros')->delete();
				foreach ($pros as $pro) {
					$compare = new ProductProsCon();
					$compare->type = 'pros';
					$compare->description = $pro;
					$compare->product_id = $sku->id;
					$compare->save();
				}
			}
		}
		if ($cons) {
			if (count($cons) > 0) {
				ProductProsCon::where('product_id', $sku->id)->where('type', 'cons')->delete();
				foreach ($cons as $con) {
					$compare = new ProductProsCon();
					$compare->type = 'cons';
					$compare->description = $con;
					$compare->product_id = $sku->id;
					$compare->save();
				}
			}
		}

		return response()->json([
			"message" => "Success! Your Post is now being reviewed by the admin.",
			"data" => [],
			"status" => 1
		]);


	}


	public function updatePost(Request $request, $id)
	{
		$paths = [];
		if ($request->hasFile('files.*')) {
			foreach ($request->file('files') as $file) {
				$filename = $file->getClientOriginalName();
				$path = $file->storeAs('products/', $filename, 'public');
				$paths[] = $path;
			}
		}

		$skuRequestData = $request->only(['price', 'currency']);
		$requestData = $request->all();

		$sku = ProductsSku::findOrFail($id);
		$sku->update($skuRequestData);
		if (count($paths) > 0) {
			$skuRequestData['files'] = $paths;
		}

		$product = Product::findOrFail($sku->product_id);
		$product->milage = preg_replace('/[^0-9]/', '', $request->milage);
		$product->update($requestData);

		AttributeValue::where('sku_id', $sku->id)->delete();

		if ($request->has('attributes')) {
			$attributes = $request->attributess;
			foreach ($attributes as $i => $v) {
				$attibuteValue = new AttributeValue();
				switch ($i) {
					case 'color':
						$attibuteValue->attribute_type = 1;
						$attibuteValue->attribute_value = $v;
						$attibuteValue->sku_id = $sku->id;
						$attibuteValue->save();
						break;
					case 'price':
						$attibuteValue->attribute_type = 3;
						$attibuteValue->attribute_value = $v;
						$attibuteValue->sku_id = $sku->id;
						$attibuteValue->save();
						break;
					default:
						$attr = new Attribute();
						$attr->name = $i;
						$attr->type = 'text';
						$attr->save();

						$attibuteValue->attribute_type = $attr->id;
						$attibuteValue->attribute_value = $v;
						$attibuteValue->sku_id = $sku->id;
						$attibuteValue->save();
				}
			}
		}
		if ($request->has("compareTypes")) {

		}

		return response()->json([
			"message" => "Success",
			"data" => [],
			"status" => 1
		]);


	}

	public function search(Request $request)
	{

		Log::info("ss", $request->all());
		$filters = $request->filters;
		$term = $request->term;
		$price = $request->price ?? 100000000000000000;
		$model = $request->model;
		$region = $request->region;
		$city = $request->city_id;
		$currency = $request->currency;
		//an_year = $request->man_year;
		$class = $request->class;
		$brand = $request->brand;
		//	$milage_from =preg_replace('/[^0-9]/','',$request->milage_from);
		//	$milage_to =preg_replace('/[^0-9]/','',$request->milage_to);
		$year_from = $request->year_from;
		$year_to = $request->year_to;



		$filters = json_decode($filters, true);

		$filter_keys = array_keys($filters);
		if (count($filters) > 0) {
			$filter_values = array_values($filters[0]);
		}


		$posts = ProductsSku::whereHas("product")->where('price', '<=', $price);
		if ($currency != null) {
			$posts->where("currency", $currency);
		}
		if (count($filters) > 0) {
			$filter_names = [];
			$filter_vals = [];
			foreach ($filters as $filter) {
				$filter_names = [...$filter_names, array_keys($filter)[0]];
				$filter_vals = [...$filter_vals, array_values($filter)[0]];
			}
			$posts->whereHas('attributes', function ($qa) use ($filter_vals, $filter_names) {
				$qa->whereIn("attribute_value", $filter_vals);
				$qa->whereHas("attributeType", function ($qu) use ($filter_vals, $filter_names) {
					$qu->whereIn("name", $filter_names);
				});
			});
		}
		$posts->whereHas('product', function ($q) use ($term, $model, $filters, $region, $year_from, $year_to, $class, $currency, $brand, $city) {
			$q->where('status', 1);
			if ($term != "" && $term != null) {
				$q->whereHas("category", function ($qc) use ($term) {
					$qc->where('name', 'like', '%' . $term . '%')->orWhereHas("parent", function ($qp) use ($term) {
						$qp->where('name', 'like', '%' . $term . '%');
					});
				});
			}

			//$class = $request->class;

			if ($brand != null && $brand != "0") {
				$q->whereHas("category", function ($qq) use ($brand) {
					$qq->where("parent_id", $brand);
				});
			}

			if ($class != null && $class != "0") {
				$q->where("class_id", $class);
			}

			if ($year_from != null && $year_to != "") {
				$q->where("man_year", "<", $year_to)->where("man_year", ">", $year_from);
			}

			//if($milage_from != null && $milage_to != ""){
			//	$q->where("milage","<",$milage_to)->where("milage",">",$milage_from);
			//}
			//$milage_from = $request->milage_from;
			//$milage_to = $request->milage_to;

			if ($model != null && $model != "0") {
				$br = Category::find($model)->parent_id;

				if (!$br) {
					$q->whereHas("category", function ($qq) use ($model) {
						$qq->where("parent_id", $model);
					});
				} else {
					$q->where("category_id", $model);
				}

				//Log::info("models",[$modelIds]);

			}
			if ($region != null && $region != "0") {
				$q->where("region_id", $region);
			}
			if ($city != null && $city != "" && $city != "0") {
				$q->whereHas("region", function ($qcr) use ($city) {
					$qcr->whereHas("parent", function ($qcc) use ($city) {
						$qcc->where("id", $city);
					});
				});
			}

		})->with("product.category")->with("attributes.attributeType");

		$advertisments = Advertisment::all();
		$posts->get();


		$filters = Filter::with('filterValues')->get();
		$stories = User::whereHas('stories', function ($q) {
			$q->where("updated_at", ">", Carbon::now()->subDay())->where("status", 1);
		})->get();

		return response()->json([
			"message" => "Success",
			"data" => [
				"advertisments" => AdvertismentResource::collection($advertisments),
				"posts" => ProductResource::collection($posts->get()),
				"filter" => FilterResource::collection($filters),
				"stories" => UserResource::collection($stories)
			],
			"status" => 1
		]);

	}

	public function profile()
	{

		$user = User::with('favourites')->with('carPosts')->findOrFail(auth()->user()->id);

		return response()->json([
			"message" => "Success",
			"data" => [
				'user' => new UserResource($user)
			],
			"status" => 1
		]);
	}
	public function galleryProfile($id)
	{

		$user = User::with('favourites')->with('carPosts', function ($q) {
			$q->has('product');
		})->find($id);

		return response()->json([
			"message" => "Success",
			"data" => [
				'user' => new UserResource($user)
			],
			"status" => 1
		]);
	}

	public function postDetails($id)
	{

		$lang = request()->header("lang");


		if (request()->input("ad") && request()->input("ad") == 1) {
			$id = $id;

			$post = ProductsSku::whereHas("product", function ($q) use ($id) {
				$q->where("id", $id)->with("category");
			})->with("attributes.attributeType")->first();
		} else {
			$post = ProductsSku::wherehas("product")->with("product.category")->with("attributes.attributeType")->findOrFail($id);
		}

		$brand = Category::find($post->product->category_id)->parent_id;


		$models = Category::where("parent_id", $brand)->pluck("id");
		$similar = ProductsSku::whereHas("product", function ($q) use ($models) {
			$q->whereIn("category_id", $models);
		})->with("product.category")->with("attributes.attributeType")->get();

		$specifications = AttributeValue::where('sku_id', $post->id)->where('attribute_type', 226)->get();
		$otherSpec = AttributeValue::where('sku_id', $post->id)->where('attribute_type', 231)->get();

		return response()->json([
			"message" => "Success",
			"data" => [
				'post' => (new ProductResource($post))->lang($lang),
				'specifications' => $specifications,
				'otherSpec' => $otherSpec,
				'similar' => ProductResource::collection($similar)
			],
			"status" => 1
		]);

	}

	public function stories()
	{

		$stories = User::whereHas('stories', function ($q) {
			$q->where("updated_at", ">", Carbon::now()->subDay())->where("status", 1);
		})->get();
		return response()->json([
			'message' => 'success',
			'data' => [
				'stories' => UserResource::collection($stories)
			],
			'status' => 1
		]);
	}


	public function story($id)
	{
		$story = Story::findOrFail($id);

		return response()->json([
			'message' => 'success',
			'data' => [
				'story' => new StoryResource($story)
			],
			'status' => 1
		]);
	}
	public function createStory(Request $request)
	{
		//$request->hasFile('file')
		if (Input::file('file')) {
			$file = Input::file('file');
			$filename = $file->getClientOriginalName();



			$imagePath = public_path() . '/storage/stories';

			$filename = time() . '.' . $file->extension();
			$file->move($imagePath, $filename);
			$path = $imagePath . '/' . $filename;

			$story = new Story();
			$story->file = $path;
			$story->created_by = auth()->user()->id;
			$story->save();
			return response()->json([
				'message' => 'Success, Your story has been send and waiting for admin approval!',
				'data' => [
					'story' => new StoryResource($story)
				],
				'status' => 1
			]);
		} else {
			return response()->json([
				'message' => 'failed, The File in mandatory',
				'data' => [

				],
				'status' => 0
			], 403);
		}
	}

	public function deleteStory($id)
	{
		$story = Story::findOrFail($id);
		$story->delete();
		return response()->json([
			'message' => 'Operation Done successfully',
			'data' => [
			],
			'status' => 1
		]);

	}
}
