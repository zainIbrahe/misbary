<?php

use App\Http\Controllers\API\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\HomeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/home', [HomeController::class, 'index']);
Route::get('/filter-attributes', [HomeController::class, 'filterAttribues']);
Route::get('/search-data', [HomeController::class, 'searchData']);
Route::get('/createPostPage', [HomeController::class, 'createPostPage']);
Route::get('/profile/{id}', [HomeController::class, 'galleryProfile']);
Route::get('/sendM', [HomeController::class, 'sendM']);
Route::get("/asd",[HomeController::class, 'verify'])->middleware("auth:sanctum");
Route::get('/post/{id}', [HomeController::class, 'postDetails']);
Route::post("/verify",[HomeController::class, 'verify']);
Route::post("/resend_code",[HomeController::class, 'resend_code']);
Route::get('/reels', [HomeController::class, 'reels']);
Route::get('/cars', [HomeController::class, 'cars']);
Route::get('/dealers', [HomeController::class, 'dealers']);
Route::get('/plans', [HomeController::class, 'plans']);

Route::group([
    'middleware' => ['auth:sanctum'],
], function () {
	 Route::get("asdss",function(){
		return "asdasd";
	})->middleware("checkPostLimit");
	Route::post("/createReel",[HomeController::class,"createReel"]);  
	Route::get("/check-limit",[HomeController::class,"checklimit"]);
    Route::get('/favourites', [HomeController::class, 'favourites']);
	Route::get('/favourites-dealers', [HomeController::class, 'favouritesDealers']);
    Route::post('/createPost', [HomeController::class, 'addPost'])->middleware("checkPostLimit");
   	Route::post("/editProfile",[HomeController::class,'editProfile']);
    Route::patch('/updatePost/{id}', [HomeController::class, 'updatePost']);
    Route::get('/addToFavourite/{id}', [HomeController::class, 'addToFav']);
	Route::get('/addDealerToFavourite/{id}', [HomeController::class, 'adddealertofav']);
	Route::get('/removeFromFav/{id}',[HomeController::class, 'removeFav']);
    Route::get('/profile', [HomeController::class, 'profile']);
    
	
	
    Route::post('/story', [HomeController::class, 'createStory']);
    Route::post('/deleteStory/{id}', [HomeController::class, 'deleteStory']);
});

Route::get('/cities', [HomeController::class, 'cities']);
Route::get('/region/{cityId}', [HomeController::class, 'regions']);
Route::get('/models/{id}', [HomeController::class, 'models']);
Route::get('/classes/{id}', [HomeController::class, 'classes']);
Route::get('/getAttrValues/{id}', [HomeController::class, 'getAttrValues']);

Route::post('/search', [HomeController::class, 'search']);


Route::get('/stories', [HomeController::class, 'stories']);
Route::get('/story/{id}', [HomeController::class, 'story']);


