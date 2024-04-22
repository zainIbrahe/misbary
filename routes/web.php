<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AttributesController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('admin/reports', [ReportsController::class, "index"])->middleware('admin.user');

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
Route::get("clear",function(){
	\Artisan::call("config:clear");
});
Route::get("/admin/galleries",[UserController::class,"index"]);
Route::get("/admin/pending-posts",[ProductController::class,"getPending"]);

Route::get('/getPending', [ProductController::class, "getPending"])->name("pending");
Route::get('/getvar', [ProductController::class, "get"]);
Route::post('/products/import', [ProductController::class, "import"]);
Route::post('/attributes/import', [AttributesController::class, "import"])->name("import");
Route::get('/import',[AttributesController::class, "getimport"]);
Route::get("/storagess",function(){
	Artisan::call("storage:link");
	
	return "asd";
	
});

Route::get("sendmsg",[HomeController::class,"sendM"]);